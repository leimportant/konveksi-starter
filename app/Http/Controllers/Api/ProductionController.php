<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\ProductionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $activityRole = $request->input('activity_role_id');
            $activityRoleId = [3,4,5,8,9];
            if ($activityRole == "CUTTING") {
                $activityRoleId = [1];
            }
            if ($activityRole == "SEWING") {
                $activityRoleId = [2,6, 10];
            }
             if ($activityRole == "FINISHING") {
                $activityRoleId = [3,4,5,8,9];
            }
            $userId = Auth::id();
            $search = $request->input('search');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            $query = Production::with(['items', 'model', 'activityRole'])
                ->latest();

            if ($activityRoleId) {
                $query->whereIn('activity_role_id', $activityRoleId);
            }

            if ($userId) {
                $query->where('created_by', $userId);
            }

            if ($search) {
                $query->whereHas('model', function ($q) use ($search) {
                    $q->where('description', 'like', '%' . $search . '%');
                });
            }

            if ($dateFrom && $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }

            $models = $query->paginate($request->input('per_page', 10));

            return response()->json([
                'data' => $models
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // 1. Terima activity_role_id sebagai number atau array
            $activityRoleIds = is_array($request->activity_role_id)
                ? $request->activity_role_id
                : [$request->activity_role_id];

            // 2. Validasi Konflik Role (Hanya Boleh Pilih Salah Satu)
            $conflictingRoles = [2, 6, 10]; // JAHIT, OBRAS, OBRAS DAN JAHIT
            $conflictingRolesName = ['JAHIT', 'OBRAS', 'OBRAS DAN JAHIT'];
            
            // Cari irisan antara ID yang dikirim dan ID yang berkonflik
            $activeConflictingRoles = array_intersect($activityRoleIds, $conflictingRoles);
            
            // Jika lebih dari satu role yang berkonflik dipilih, kembalikan error
            if (count($activeConflictingRoles) > 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pilih salah 1. Aktivitas yang dikirim tidak boleh lebih dari satu di antara: ' . implode(', ', $conflictingRolesName)
                ], 422);
            }

            // 3. Basic Validation (Laravel Validator)
            $validator = Validator::make($request->all(), [
                'model_id' => 'required|exists:tr_model,id',
                'employee_id'=> 'required|exists:users,id',
                'activity_role_id' => 'required', // validasi untuk array ditangani secara manual di bawah
                'remark' => 'nullable|string|max:100',
                'items' => 'required|array|min:1',
                'items.*.size_id' => 'required|exists:mst_size,id',
                'items.*.variant' => 'required',
                // 'items.*.qty' => 'nullable|integer|min:1' // Validasi min:1 dipindahkan ke cek $validItems
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // 4. Validate all activity_role_ids exist manually (Since default validation is hard for array)
            $invalidRoles = array_filter($activityRoleIds, function ($roleId) {
                return !DB::table('mst_activity_role')->where('id', $roleId)->exists();
            });
            if (count($invalidRoles) > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid activity_role_id(s): ' . implode(',', $invalidRoles)
                ], 422);
            }

            // 5. Filter Item yang memiliki Quantity (Qty > 0 atau tidak null)
            $validItems = array_filter($request->items, function ($item) {
                // Tambahkan validasi min:1 di sini
                return isset($item['qty']) && is_numeric($item['qty']) && $item['qty'] >= 1;
            });

            if (count($validItems) === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Minimal 1 item harus diisi dengan kuantitas (quantity) minimal 1'
                ], 422);
            }
            
            // 6. Role Mapping: { current => [previous_1, previous_2, ...] }
            $previousRoleMap = [
                // SEWING (2) dapat dari CUTTING (1) atau OBRAS DAN JAHIT (10)
                2 => [1], 
                3 => [2, 10], // FINISHING -> SEWING
                4 => [2, 10], // QUALITY_CHECK -> SEWING (Asumsi: Role 4 & 5 mengikuti Role 2)
                5 => [2, 10], 
                6 => [1, 10], // OBRAS -> CUTTING
                7 => [2, 10],
                8 => [2, 10],
                9 => [2, 10],
                10 => [1], // OBRAS DAN JAHIT -> CUTTING
            ];

            $productions = [];

            // 7. Proses dan Validasi untuk Setiap Activity Role
            foreach ($activityRoleIds as $activityRoleId) {
                // Validasi Qty hanya jika BUKAN role CUTTING (ID 1)
                if ($activityRoleId != 1) {
                    if (!isset($previousRoleMap[$activityRoleId])) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => "Tidak bisa menentukan proses sebelumnya untuk activity_role_id: $activityRoleId"
                        ], 422);
                    }

                    $previousRoleIds = $previousRoleMap[$activityRoleId]; 
                    
                    $availableQty = [];
                    $usedQty = [];

                    // a. Hitung total available qty dari SEMUA proses sebelumnya yang diizinkan
                    foreach ($previousRoleIds as $previousRoleId) {
                        $previousProductions = Production::with('items')
                            ->where('model_id', $request->model_id)
                            ->where('activity_role_id', $previousRoleId)
                            ->get();
                        
                        foreach ($previousProductions as $prod) {
                            foreach ($prod->items as $item) {
                                $key = $item->size_id . '-' . $item->variant;
                                $availableQty[$key] = ($availableQty[$key] ?? 0) + $item->qty;
                            }
                        }
                    }
                    
                    // Cek apakah ada data yang tersedia SAMA SEKALI dari semua proses sebelumnya
                    if (empty($availableQty)) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Data produksi sebelumnya belum tersedia untuk model ini dari semua role yang diizinkan.'
                        ], 422);
                    }
                    
                    // b. Hitung total used qty di role saat ini (dari data yang sudah tersimpan)
                    $existingProductions = Production::with('items')
                        ->where('model_id', $request->model_id)
                        ->where('activity_role_id', $activityRoleId)
                        ->get();

                    foreach ($existingProductions as $prod) {
                        foreach ($prod->items as $item) {
                            $key = $item->size_id . '-' . $item->variant;
                            $usedQty[$key] = ($usedQty[$key] ?? 0) + $item->qty;
                        }
                    }
                    
                    // c. Validasi request qty
                    foreach ($validItems as $item) {
                        $key = $item['size_id'] . '-' . $item['variant'];
                        $sizeId = $item['size_id'];
                        $variant = $item['variant'];
                        $requestedQty = $item['qty'];
                        
                        // Qty Max yang Tersedia = (Total Available dari semua Pre-Role) - (Total Used di Current Role)
                        $maxAvailable = ($availableQty[$key] ?? 0) - ($usedQty[$key] ?? 0);

                        if ($requestedQty > $maxAvailable) {
                            DB::rollBack();
                            return response()->json([
                                'status' => 'error',
                                'message' => "Qty untuk size $sizeId variant $variant melebihi sisa yang tersedia ($maxAvailable) dari proses sebelumnya"
                            ], 422);
                        }
                    }
                }

                // 8. Simpan Produksi (Jika validasi Qty lolos atau merupakan CUTTING)
                $production = Production::create([
                    'id' => 'PRD-' . uniqid(),
                    'model_id' => $request->model_id,
                    'employee_id' => $request->employee_id,
                    'activity_role_id' => $activityRoleId,
                    'remark' => $request->remark,
                    'status' => 'waiting',
                    'created_by' => Auth::id()
                ]);

                // 9. Simpan Item Produksi
                $production->items()->createMany(
                    array_map(function ($item) {
                        return [
                            'id' => 'PRI-' . uniqid(),
                            'size_id' => $item['size_id'],
                            'qty' => $item['qty'],
                            'variant' => $item['variant'],
                            'created_by' => Auth::id()
                        ];
                    }, $validItems)
                );

                $productions[] = $production->load(['model', 'activityRole', 'items.size']);
            }

            // 10. Commit Transaksi
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Production created successfully',
                'data' => count($productions) === 1 ? $productions[0] : $productions
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create production',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store2(Request $request)
    {
        DB::beginTransaction();
        try {
            // Accept activity_role_id as number or array
            $activityRoleIds = is_array($request->activity_role_id)
                ? $request->activity_role_id
                : [$request->activity_role_id];
            // jika activity_role_id : 2,6,10 active maka muncul error, Pilih salah 1
            $conflictingRoles = [2, 6, 10];
            $conflictingRolesName = ['JAHIT', 'OBRAS', 'OBRAS DAN JAHIT'];
            // Find the intersection between the submitted IDs and the conflicting IDs
            $activeConflictingRoles = array_intersect($activityRoleIds, $conflictingRoles);
            
            // If more than one conflicting role is selected, return an error
            if (count($activeConflictingRoles) > 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pilih salah 1. aktivitas yang dikirim tidak boleh lebih dari satu di antara: ' . implode(', ', $conflictingRolesName)
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'model_id' => 'required|exists:tr_model,id',
                'employee_id'=> 'required|exists:users,id',
                'activity_role_id' => 'required', // validation for array handled below
                'remark' => 'nullable|string|max:100',
                'items' => 'required|array|min:1',
                'items.*.size_id' => 'required|exists:mst_size,id',
                'items.*.variant' => 'required',
                'items.*.qty' => 'nullable|integer|min:1'
            ]);


            

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate all activity_role_ids exist
            $invalidRoles = array_filter($activityRoleIds, function ($roleId) {
                return !DB::table('mst_activity_role')->where('id', $roleId)->exists();
            });
            if (count($invalidRoles) > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid activity_role_id(s): ' . implode(',', $invalidRoles)
                ], 422);
            }

            $validItems = array_filter($request->items, function ($item) {
                return $item['qty'] !== null;
            });

            if (count($validItems) === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Minimal 1 item dan quantity 1'
                ], 422);
            }

            // Role Mapping: { current => previous }
            $previousRoleMap = [
                2 => 1, // SEWING -> CUTTING
                3 => 2, // FINISHING -> SEWING
                4 => 2, // QUALITY_CHECK -> FINISHING (or SEWING)
                5 => 2,
                6 => 1, // OBRAS -> CUTTING
                7 => 2,
                8 => 2,
                9 => 2,
                10 => 1, // OBRAS DAN JAHIT -> CUTTING
            ];

            $productions = [];

            foreach ($activityRoleIds as $activityRoleId) {
                // Validasi qty jika bukan cutting
                if ($activityRoleId != 1) {
                    if (!isset($previousRoleMap[$activityRoleId])) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => "Tidak bisa menentukan proses sebelumnya untuk activity_role_id: $activityRoleId"
                        ], 422);
                    }

                    $previousRoleId = $previousRoleMap[$activityRoleId];

                    // Get total available qty from previous process
                    $previousProductions = Production::with('items')
                        ->where('model_id', $request->model_id)
                        ->where('activity_role_id', $previousRoleId)
                        ->get();

                    if ($previousProductions->isEmpty()) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Data produksi sebelumnya belum tersedia'
                        ], 422);
                    }

                    $availableQty = [];
                    foreach ($previousProductions as $prod) {
                        foreach ($prod->items as $item) {
                            $key = $item->size_id . '-' . $item->variant;
                            $availableQty[$key] = ($availableQty[$key] ?? 0) + $item->qty;
                        }
                    }

                    // Get total used qty in current role
                    $existingProductions = Production::with('items')
                        ->where('model_id', $request->model_id)
                        ->where('activity_role_id', $activityRoleId)
                        ->get();

                    $usedQty = [];
                    foreach ($existingProductions as $prod) {
                        foreach ($prod->items as $item) {
                            $key = $item->size_id . '-' . $item->variant;
                            $usedQty[$key] = ($usedQty[$key] ?? 0) + $item->qty;
                        }
                    }
                    // Validate request qty
                    foreach ($validItems as $item) {
                        $key = $item['size_id'] . '-' . $item['variant'];
                        $sizeId = $item['size_id'];
                        $variant = $item['variant'];
                        $requestedQty = $item['qty'];
                        $maxAvailable = ($availableQty[$key] ?? 0) - ($usedQty[$key] ?? 0);

                        if ($requestedQty > $maxAvailable) {
                            DB::rollBack();
                            return response()->json([
                                'status' => 'error',
                                'message' => "Qty untuk size $sizeId variant $variant melebihi sisa yang tersedia ($maxAvailable) dari proses sebelumnya"
                            ], 422);
                        }
                    }
                }
                // Simpan produksi
                $production = Production::create([
                    'id' => 'PRD-' . uniqid(),
                    'model_id' => $request->model_id,
                    'employee_id' => $request->employee_id,
                    'activity_role_id' => $activityRoleId,
                    'remark' => $request->remark,
                    'status' => 'waiting',
                    'created_by' => Auth::id()
                ]);

                $production->items()->createMany(
                    array_map(function ($item) {
                        return [
                            'id' => 'PRI-' . uniqid(),
                            'size_id' => $item['size_id'],
                            'qty' => $item['qty'],
                            'variant' => $item['variant'],
                            'created_by' => Auth::id()
                        ];
                    }, $validItems)
                );

                $productions[] = $production->load(['model', 'activityRole', 'items.size']);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Production created successfully',
                'data' => count($productions) === 1 ? $productions[0] : $productions
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create production',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            // 1. Validator
            $validator = Validator::make($request->all(), [
                'model_id' => 'required|exists:tr_model,id',
                'activity_role_id' => 'required|exists:mst_activity_role,id',
                'remark' => 'nullable|string|max:100',
                'items' => 'required|array|min:1',
                'items.*.size_id' => 'required|exists:mst_size,id',
                'items.*.variant' => 'required',
                'items.*.qty' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // 2. Filter item yang valid (qty >= 1)
            $validItems = array_filter($request->items, fn($item) => $item['qty'] >= 1);

            // 3. Role Mapping: { current => [previous_1, previous_2, ...] }
            $previousRoleMap = [
                // SEWING (2) dapat dari CUTTING (1) atau OBRAS DAN JAHIT (10)
                2 => [1], 
                3 => [2, 10], 
                4 => [2, 10], // Sesuaikan ke [2] untuk konsistensi flow
                5 => [2, 10],
                6 => [1, 10], // Sesuaikan ke [1] untuk konsistensi flow
                7 => [2, 10],
                8 => [2, 10],
                9 => [2, 10],
                10 => [1],
            ];

            $activityRoleId = $request->activity_role_id;

            // 4. Validasi Qty (Jika BUKAN CUTTING / ID 1)
            if ($activityRoleId != 1) {
                if (!isset($previousRoleMap[$activityRoleId])) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => "Tidak bisa menentukan proses sebelumnya untuk activity_role_id: $activityRoleId"
                    ], 422);
                }

                // Ambil array dari ID proses sebelumnya
                $previousRoleIds = $previousRoleMap[$activityRoleId];

                $availableQty = [];

                // a. Hitung total available qty dari SEMUA proses sebelumnya yang diizinkan
                foreach ($previousRoleIds as $previousRoleId) {
                    $previousProductions = Production::with('items')
                        ->where('model_id', $request->model_id)
                        ->where('activity_role_id', $previousRoleId)
                        ->get();
                    
                    foreach ($previousProductions as $prod) {
                        foreach ($prod->items as $item) {
                            $key = $item->size_id . '-' . $item->variant;
                            $availableQty[$key] = ($availableQty[$key] ?? 0) + $item->qty;
                        }
                    }
                }
                
                // Cek apakah ada data yang tersedia SAMA SEKALI
                if (empty($availableQty)) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Data produksi sebelumnya belum tersedia untuk model ini dari semua role yang diizinkan.'
                    ], 422);
                }

                // b. Ambil qty yang sudah terpakai (selain current record $id)
                $existingProductions = Production::with('items')
                    ->where('model_id', $request->model_id)
                    ->where('activity_role_id', $activityRoleId)
                    ->where('id', '!=', $id) // PENTING: Mengecualikan record yang sedang di-update
                    ->get();

                $usedQty = [];
                foreach ($existingProductions as $prod) {
                    foreach ($prod->items as $item) {
                        $key = $item->size_id . '-' . $item->variant;
                        $usedQty[$key] = ($usedQty[$key] ?? 0) + $item->qty;
                    }
                }

                // c. Validasi qty untuk update
                foreach ($validItems as $item) {
                    $key = $item['size_id'] . '-' . $item['variant'];
                    $sizeId = $item['size_id'];
                    $variant = $item['variant'];
                    $requestedQty = $item['qty'];

                    // Max tersedia = (Total Available dari semua Pre-Role) - (Total Used di Current Role oleh record lain)
                    $maxAvailable = ($availableQty[$key] ?? 0) - ($usedQty[$key] ?? 0);

                    if ($requestedQty > $maxAvailable) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => "Qty untuk size $sizeId variant $variant melebihi sisa yang tersedia ($maxAvailable) dari proses sebelumnya"
                        ], 422);
                    }
                }
            }

            // 5. Update production
            $production = Production::findOrFail($id);
            $production->fill($request->only(['model_id', 'activity_role_id', 'remark']));
            $production->updated_by = Auth::id();
            $production->save();

            // 6. Sinkronisasi Item Produksi (Hapus dan Tambah)
            ProductionItem::where('production_id', $id)->delete();

            // Tambah item baru
            $production->items()->createMany(
                array_map(function ($item) use ($production) {
                    return [
                        'id' => 'PRI-' . uniqid(),
                        'size_id' => $item['size_id'],
                        'qty' => $item['qty'],
                        'variant' => $item['variant'],
                        'created_by' => Auth::id(),
                    ];
                }, $validItems)
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Production updated successfully',
                'data' => $production->load(['model', 'activityRole', 'items.size'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update production',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $production = Production::with(['model', 'activityRole', 'items.size'])
                ->where('id',$id)
                ->firstOrFail();

            return response()->json([
                'status' => 'success',
                'data' => $production
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Production not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function destroy(Production $production)
    {
        $production->deleted_by = Auth::id();
        $production->save();
        $production->delete();
        return response()->json(null, 204);
    }

}
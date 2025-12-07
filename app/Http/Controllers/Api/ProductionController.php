<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\ProductionItem;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Services\TransferStockService;


class ProductionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $activityRole = $request->input('activity_role_id');
            $user = Auth::user();
            $userId = $user->id;
            $employee_status = $user->employee_status ?? "staff";
            $status = $request->input('status') ?? 1;


            \Log::info($employee_status);

            $search = $request->input('search');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $perPage = $request->input('per_page', 50);

            $activityRoleId = match ($activityRole) {
                "CUTTING" => [1],
                "SEWING" => [2, 6, 10],
                "PENGIRIMAN" => [12],
                "QUALITY_CHECK" => [11],
                "FINISHING" => [3, 8, 9],
                "PAKING" => [5],
                "LUBANG_KANCING" => [4],
                default => [],
            };

            $query = Production::with(['items', 'model', 'activityRole', 'employee'])
                ->when(!empty($activityRoleId), function ($q) use ($activityRoleId) {
                    $q->whereIn('activity_role_id', $activityRoleId);
                })
                ->when(!empty($status), function ($q) use ($status) {
                    $q->where('status', $status);
                })
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'DESC');

            if ($employee_status !== "owner") {
                $query->where(function ($q) use ($userId) {
                    $q->where('created_by', $userId)
                        ->orWhere('employee_id', $userId);
                });
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {

                    // 🔍 Cari di relasi model
                    $q->orWhereHas('model', function ($q2) use ($search) {
                        $q2->where('description', 'like', "%{$search}%");
                    });

                    // 🔍 Cari di relasi activityRole
                    $q->orWhereHas('activityRole', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });

                    // 🔍 Cari di relasi employee
                    $q->orWhereHas('employee', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });

                    // 🔍 Cari di relasi items
                    $q->orWhereHas('items', function ($q2) use ($search) {
                        $q2->where('variant', 'like', "%{$search}%")
                            ->orWhere('qty', 'like', "%{$search}%");
                    });
                });
            }


            if (!empty($dateFrom) && !empty($dateTo)) {
                $start = Carbon::parse($dateFrom)->startOfDay();
                $end = Carbon::parse($dateTo)->endOfDay();
                $query->whereBetween('created_at', [$start, $end]);
            }

            \Log::info('SQL', [$query->toSql(), $query->getBindings()]);

            $productions = $query->paginate($perPage);

            $productions->getCollection()->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'model_id' => $item->model_id,
                    'group_key' => $item->employee?->name . '- Model : ' . $item->model_id,
                    'activity_role_id' => $item->activity_role_id,
                    'employee_id' => $item->employee_id,
                    'employee_name' => $item->employee?->name, // ✅ ambil dari relasi users
                    'price' => $item->price,
                    'price_per_pcs' => $item->price_per_pcs,
                    'total_price' => $item->total_price,
                    'remark' => $item->remark,
                    'status' => $item->status,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'model' => $item->model,
                    'items' => $item->items,
                    'activity_role' => $item->activityRole,
                ];
            });


            return response()->json([
                'data' => $productions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data produksi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Helper: normalisasi variant
            $normalizeVariant = function ($v) {
                return ucfirst(strtolower(trim($v)));
            };

            // 1. Terima activity_role_id sebagai number atau array
            $activityRoleIds = is_array($request->activity_role_id)
                ? $request->activity_role_id
                : [$request->activity_role_id];

            // 2. Validasi Konflik Role
            $conflictingRoles = [2, 6, 10];
            $conflictingRolesName = ['JAHIT', 'OBRAS', 'OBRAS DAN JAHIT'];
            $activeConflictingRoles = array_intersect($activityRoleIds, $conflictingRoles);
            if (count($activeConflictingRoles) > 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pilih salah 1 dari: ' . implode(', ', $conflictingRolesName)
                ], 422);
            }

            // 3. Basic Validation
            $validator = Validator::make($request->all(), [
                'model_id' => 'required|exists:tr_model,id',
                'employee_id' => 'required|exists:users,id',
                'activity_role_id' => 'required',
                'remark' => 'nullable|string|max:100',
                'items' => 'required|array|min:1',
                'items.*.size_id' => 'required',
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

            // 4. Filter Item valid
            $validItems = collect($request->items)
                ->filter(fn($item) => isset($item['qty']) && $item['qty'] > 0)
                ->map(function ($item) use ($normalizeVariant) {
                    $item['variant'] = $normalizeVariant($item['variant']);
                    return $item;
                })
                ->values()
                ->all();

            // 5. Mapping hubungan antar proses
            $previousRoleMap = [
                1 => [],
                2 => [1],
                10 => [1],
                3 => [2, 10],
                4 => [2, 10],
                5 => [2, 10],
                6 => [1, 10],
                7 => [2, 10],
                8 => [2, 10],
                9 => [2, 10],
                11 => [2, 10],
                12 => [2, 10],
            ];

            $productions = [];
            $pendingTransfers = [];

            foreach ($activityRoleIds as $activityRoleId) {
                // === VALIDASI QTY ANTAR PROSES ===
                if ($activityRoleId != 1) {

                    $availableQty = [];
                    $usedQty = [];

                    if (in_array($activityRoleId, [2, 10])) {
                        // Special case: sewing group
                        // Ambil total dari CUTTING (1)
                        $previousProductions = Production::with('items')
                            ->where('model_id', $request->model_id)
                            ->where('activity_role_id', 1)
                            ->get();

                        foreach ($previousProductions as $prod) {
                            foreach ($prod->items as $item) {
                                $key = $item->size_id . '-' . $normalizeVariant($item->variant);
                                $availableQty[$key] = ($availableQty[$key] ?? 0) + $item->qty;
                            }
                        }

                        // Ambil total yang sudah dipakai di jahit + obras (2,10)
                        $existingProductions = Production::with('items')
                            ->where('model_id', $request->model_id)
                            ->whereIn('activity_role_id', [2, 10])
                            ->get();

                        foreach ($existingProductions as $prod) {
                            foreach ($prod->items as $item) {
                                $key = $item->size_id . '-' . $normalizeVariant($item->variant);
                                $usedQty[$key] = ($usedQty[$key] ?? 0) + $item->qty;
                            }
                        }
                    } else {
                        // Default case (pakai mapping previousRoleMap)
                        $previousRoleIds = $previousRoleMap[$activityRoleId] ?? null;
                        if (!$previousRoleIds) {
                            DB::rollBack();
                            return response()->json([
                                'status' => 'error',
                                'message' => "Tidak bisa menentukan proses sebelumnya ($activityRoleId)"
                            ], 422);
                        }

                        // Ambil qty dari proses sebelumnya
                        foreach ($previousRoleIds as $previousRoleId) {
                            $roleIdsToCheck = in_array($previousRoleId, [2, 10]) ? [2, 10] : [$previousRoleId];
                            $previousProductions = Production::with('items')
                                ->where('model_id', $request->model_id)
                                ->whereIn('activity_role_id', $roleIdsToCheck)
                                ->get();

                            foreach ($previousProductions as $prod) {
                                foreach ($prod->items as $item) {
                                    $key = $item->size_id . '-' . $normalizeVariant($item->variant);
                                    $availableQty[$key] = ($availableQty[$key] ?? 0) + $item->qty;
                                }
                            }
                        }

                        // Ambil qty dari proses saat ini (sudah digunakan)
                        $roleIdsToCheck = [$activityRoleId];
                        $existingProductions = Production::with('items')
                            ->where('model_id', $request->model_id)
                            ->whereIn('activity_role_id', $roleIdsToCheck)
                            ->get();

                        foreach ($existingProductions as $prod) {
                            foreach ($prod->items as $item) {
                                $key = $item->size_id . '-' . $normalizeVariant($item->variant);
                                $usedQty[$key] = ($usedQty[$key] ?? 0) + $item->qty;
                            }
                        }
                    }

                    // Validasi payload qty
                    if (empty($availableQty)) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Belum ada data produksi sebelumnya untuk model ini.'
                        ], 422);
                    }

                    foreach ($validItems as $item) {
                        $key = $item['size_id'] . '-' . $normalizeVariant($item['variant']);
                        $sizeId = $item['size_id'];
                        $variant = $item['variant'];
                        $requestedQty = $item['qty'];
                        $available = $availableQty[$key] ?? 0;
                        $used = $usedQty[$key] ?? 0;

                        $totalAfterInsert = $used + $requestedQty;

                        if ($totalAfterInsert > $available) {
                            DB::rollBack();
                            return response()->json([
                                'status' => 'error',
                                'message' => "Qty untuk size $sizeId variant $variant melebihi sisa dari proses sebelumnya (tersisa " . max($available - $used, 0) . ")"
                            ], 422);
                        }
                    }
                }

                // === SIMPAN DATA PRODUKSI ===
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
                            'variant' => ucfirst(strtolower(trim($item['variant']))),
                            'created_by' => Auth::id()
                        ];
                    }, $validItems)
                );

                $productions[] = $production->load(['model', 'activityRole', 'items.size']);

                // jika pengiriman, tunda run TransferStock sampai setelah commit
                if ($activityRoleId == 12) {
                    $reqCopy = clone $request;
                    $reqCopy->merge(['location_id' => $request->location_id ?? 1]); // default
                    $pendingTransfers[] = [
                        'production' => $production,
                        'items' => $validItems,
                        'request' => $reqCopy
                    ];
                }

            }

            DB::commit();

            // Jalankan transfer stock di luar transaction utama untuk menghindari race/relation error
            foreach ($pendingTransfers as $pt) {
                try {
                    TransferStockService::createTransfer($pt['production'], $pt['items'], $pt['request']);
                } catch (\Throwable $t) {
                    \Log::error('TransferStockService failed: ' . $t->getMessage());
                    throw $t;
                    // tidak me-rollback karena produksi sudah committed; kamu bisa tentukan policy (notify user/admin)
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil di buat',
                'data' => count($productions) === 1 ? $productions[0] : $productions
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saat buat : ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error saat buat :' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

             $normalizeVariant = function ($v) {
                return ucfirst(strtolower(trim($v)));
            };

            // =======================
            // 1. VALIDATION
            // =======================
            $validator = Validator::make($request->all(), [
                'model_id' => 'required|exists:tr_model,id',
                'activity_role_id' => 'required|exists:mst_activity_role,id',
                'remark' => 'nullable|string|max:100',
                'items' => 'required|array|min:1',
                'items.*.size_id' => 'required|exists:mst_size,id',
                'items.*.variant' => 'required|string',
                'items.*.qty' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Filter only valid items
            $items = collect($request->items)
                ->filter(fn($i) => $i['qty'] >= 1)
                ->values();

            $activityRoleId = $request->activity_role_id;
            
             // 4. Filter Item valid
            $validItems = collect($request->items)
                ->filter(fn($item) => isset($item['qty']) && $item['qty'] > 0)
                ->map(function ($item) use ($normalizeVariant) {
                    $item['variant'] = $normalizeVariant($item['variant']);
                    return $item;
                })
                ->values()
                ->all();
            // =======================
            // 2. LIST PROSES SEBELUMNYA
            // =======================
            $roleMap = [
                1 => [],        // Cutting
                2 => [1],       // Jahit
                10 => [1],      // Obras + Jahit
                3 => [2, 10],   // Finishing
                4 => [2, 10],   // QC
                5 => [2, 10],   // Packing
                6 => [1, 10],   // Obras
                7 => [2, 10],   // Lubang Kancing
                8 => [2, 10],   // Bartex
                9 => [2, 10],   // Steam
                11 => [2, 10],
                12 => [2, 10]
            ];

             $pendingTransfers = [];
            // =======================
            // 3. VALIDASI QTY JIKA BUKAN CUTTING (1)
            // =======================
            if ($activityRoleId != 1) {

                if (!isset($roleMap[$activityRoleId])) {
                    throw new \Exception("Tidak ada proses sebelumnya untuk role: $activityRoleId");
                }

                $prevRoles = $roleMap[$activityRoleId];

                // UTILITY: buat key size-variant
                $makeKey = fn($size, $variant) =>
                    $size . '-' . strtolower(trim($variant));

                // =======================
                // Ambil total qty proses sebelumnya
                // =======================
                $available = [];

                $previous = Production::with('items')
                    ->where('model_id', $request->model_id)
                    ->whereIn('activity_role_id', $prevRoles)
                    ->get();

                foreach ($previous as $prod) {
                    foreach ($prod->items as $itm) {
                        $key = $makeKey($itm->size_id, $itm->variant);
                        $available[$key] = ($available[$key] ?? 0) + $itm->qty;
                    }
                }

                // =======================
                // Ambil qty yang sudah dipakai role ini (kecuali record yang sedang diupdate)
                // =======================
                $used = [];

                $parallelRoles = in_array($activityRoleId, [2, 10]) ? [2, 10] : [$activityRoleId];

                 $otherProductions = Production::with('items')
                    ->where('model_id', $request->model_id)
                    ->whereIn('activity_role_id', $parallelRoles)
                    ->where('id', '!=', $id)
                    ->get();

                foreach ($otherProductions as $prod) {
                    foreach ($prod->items as $itm) {
                        $key = $makeKey($itm->size_id, $itm->variant);
                        $used[$key] = ($used[$key] ?? 0) + $itm->qty;
                    }
                }

                // =======================
                // Ambil qty lama record ini (supaya update tidak error)
                // =======================
                $oldQtyMap = [];

                $current = Production::with('items')->find($id);

                foreach ($current->items as $itm) {
                    $key = $makeKey($itm->size_id, $itm->variant);
                    $oldQtyMap[$key] = ($oldQtyMap[$key] ?? 0) + $itm->qty;
                }

                // =======================
                // VALIDASI SETIAP ITEM BARU
                // =======================
                foreach ($items as $item) {

                    $key = $makeKey($item['size_id'], $item['variant']);
                    $req = $item['qty'];

                    $prevQty = array_key_exists($key, $available)
                        ? $available[$key]
                        : null;

                    $alreadyUsed = $used[$key] ?? 0;
                    $oldQty = $oldQtyMap[$key] ?? 0;

                    // CASE A → Tidak ada di proses sebelumnya
                    if ($prevQty === null) {
                        if ($alreadyUsed > 0) {
                            throw new \Exception(
                                "Variant {$item['variant']} size {$item['size_id']} sudah pernah diproses tetapi tidak ditemukan pada proses sebelumnya."
                            );
                        }
                        // Jika baru benar-benar variant baru → allowed
                        continue;
                    }

                    // CASE B → qty proses sebelumnya = 0 → tidak boleh diteruskan
                    if ($prevQty === 0) {

                        return response()->json([
                                'status' => 'error',
                                'message' => "Proses sebelumnya qty size {$item['size_id']} variant {$item['variant']} adalah 0."
                            ], 422);                      
                    }

                     \Log::info("prevQty");
                    \Log::info($prevQty);

                      \Log::info("alreadyUsed");
                    \Log::info($alreadyUsed);

                       \Log::info("oldQty");
                    \Log::info($oldQty);

                    // CASE C → Hitung sisa qty yang boleh dipakai
                    $remaining = $prevQty - $alreadyUsed + $oldQty;

                    \Log::info($req);

                    if ($remaining < 0)
                        $remaining = 0;

                    if ($remaining > $prevQty) {
                        $remaining = $prevQty;
                    }

                    if ($req > $remaining) {
                        return response()->json([
                                'status' => 'error',
                                'message' => "Qty size {$item['size_id']} variant {$item['variant']} melebihi sisa tersedia ($remaining) dari proses sebelumnya."
                          ], 422);     
                    }
                }
            }

            // =======================
            // 4. UPDATE DATA PRODUCTION
            // =======================
            $production = Production::findOrFail($id);
            $production->fill($request->only(['model_id', 'activity_role_id', 'remark']));
            $production->updated_by = Auth::id();
            $production->save();

            // Replace items
            ProductionItem::where('production_id', $id)->forceDelete();

            $production->items()->createMany(
                    array_map(function ($item) {
                        return [
                            'id' => 'PRI-' . uniqid(),
                            'size_id' => $item['size_id'],
                            'qty' => $item['qty'],
                            'variant' => ucfirst(strtolower(trim($item['variant']))),
                            'created_by' => Auth::id()
                        ];
                    }, $validItems)
                );

            $productions[] = $production->load(['model', 'activityRole', 'items.size']);

            // jika pengiriman, tunda run TransferStock sampai setelah commit
            if ($activityRoleId == 12) {
                $reqCopy = clone $request;
                $reqCopy->merge(['location_id' => $request->location_id ?? 1]); // default
                $pendingTransfers[] = [
                    'production' => $production,
                    'items' => $validItems,
                    'request' => $reqCopy
                ];
            }

            DB::commit();

             // Jalankan transfer stock di luar transaction utama untuk menghindari race/relation error
            foreach ($pendingTransfers as $pt) {
                try {
                   $tf = TransferStockService::createTransfer($pt['production'], $pt['items'], $pt['request']);
                    
                   \Log::info($tf);
                } catch (\Throwable $t) {
                    throw $t;
                }
            }

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
                ->where('id', $id)
                ->whereNull('deleted_at')
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

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Ambil data produksi
            $production = Production::with('items')->findOrFail($id);

            // Hapus semua item produksi terkait
            ProductionItem::where('production_id', $production->id)->delete();

            // Hapus produksi secara permanen
            $production->forceDelete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Production deleted permanently'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete production',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
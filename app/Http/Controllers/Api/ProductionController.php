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

                // jika pengiriman store data ke table transfer_stock
                // jika user yang input dan tanggal sama, update saja jika ada
                // dan item nya disesuaikan

                if ($activityRoleId == 12) {
                    $request->merge(['location_id' => $request->location_id ?? 1]); // default location_id 1 jika tidak ada
                    TransferStockService::createTransfer($production, $validItems, $request);
                }


            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Production created successfully',
                'data' => count($productions) === 1 ? $productions[0] : $productions
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating production: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create production' . $e->getMessage(),
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

            // Only items with qty >= 1
            $validItems = array_filter($request->items, fn($item) => $item['qty'] >= 1);

            // ============================
            //  MAPPING ROLE PROSES SEBELUMNYA
            // ============================
            $previousRoleMap = [
                1 => [],        // Cutting
                2 => [1],       // Jahit dari Cutting
                10 => [1],      // Obras+Jahit dari Cutting
                3 => [2, 10],   // Finishing
                4 => [2, 10],   // QC
                5 => [2, 10],   // Packing
                6 => [1, 10],   // Obras
                7 => [2, 10],   // Lubang Kancing
                8 => [2, 10],   // Bartex
                9 => [2, 10],   // Steam
                11 => [2, 10],  // (Baru ditambah)
                12 => [2, 10],  // (Baru ditambah)
            ];

            $activityRoleId = $request->activity_role_id;

            // ============================
            // VALIDASI QTY (Sama seperti STORE)
            // ============================
            if ($activityRoleId != 1) {

                if (!isset($previousRoleMap[$activityRoleId])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Tidak bisa menentukan proses sebelumnya untuk activity_role_id: $activityRoleId"
                    ], 422);
                }

                $previousRoleIds = $previousRoleMap[$activityRoleId];

                $availableQty = [];
                $usedQty = [];

                // -----------------------------
                // A. TOTAL QTY PROSES SEBELUMNYA
                // -----------------------------
                foreach ($previousRoleIds as $previousRoleId) {

                    $roleIdsToCheck = in_array($previousRoleId, [2, 10])
                        ? [2, 10]
                        : [$previousRoleId];

                    $previousProductions = Production::with('items')
                        ->where('model_id', $request->model_id)
                        ->whereIn('activity_role_id', $roleIdsToCheck)
                        ->get();

                    foreach ($previousProductions as $prod) {
                        foreach ($prod->items as $item) {
                            $key = $item->size_id . '-' . ucfirst(strtolower(trim($item->variant)));
                            $availableQty[$key] = ($availableQty[$key] ?? 0) + $item->qty;
                        }
                    }
                }

                // -----------------------------
                // B. QTY YANG SUDAH DIPAKAI (record lain)
                // -----------------------------
                $roleIdsCurrent = in_array($activityRoleId, [2, 10])
                    ? [2, 10]
                    : [$activityRoleId];

                $existingProductions = Production::with('items')
                    ->where('model_id', $request->model_id)
                    ->whereIn('activity_role_id', $roleIdsCurrent)
                    ->where('id', '!=', $id)
                    ->get();

                foreach ($existingProductions as $prod) {
                    foreach ($prod->items as $item) {
                        $key = $item->size_id . '-' . ucfirst(strtolower(trim($item->variant)));
                        $usedQty[$key] = ($usedQty[$key] ?? 0) + $item->qty;
                    }
                }

                // -----------------------------
                // C. QTY LAMA dari record ini (bug fix penting)
                // -----------------------------
                $currentProduction = Production::with('items')->find($id);

                foreach ($currentProduction->items as $oldItem) {
                    $key = $oldItem->size_id . '-' . ucfirst(strtolower(trim($oldItem->variant)));
                    $usedQty[$key] = ($usedQty[$key] ?? 0) + $oldItem->qty;
                }

                // -----------------------------
                // D. VALIDASI PER ITEM
                // -----------------------------
                foreach ($validItems as $item) {

                    $key = $item['size_id'] . '-' . ucfirst(strtolower(trim($item['variant'])));
                    $sizeId = $item['size_id'];
                    $variant = $item['variant'];
                    $requestedQty = $item['qty'];

                    $available = $availableQty[$key] ?? null;
                    $used = $usedQty[$key] ?? 0;

                    // Jika belum ada di proses sebelumnya
                    if (is_null($available)) {
                        // Tapi sudah pernah diproses → tidak boleh
                        if ($used > 0) {
                            return response()->json([
                                'status' => 'error',
                                'message' => "Variant $variant size $sizeId sudah pernah diproses, tetapi tidak ada di proses sebelumnya."
                            ], 422);
                        }
                        continue; // Variant baru → allowed
                    }

                    // Total lama yang ada di record ini
                    $oldQtyOnThisRecord = $currentProduction->items
                        ->where('size_id', $sizeId)
                        ->where('variant', $variant)
                        ->sum('qty');

                    $remaining = $available - ($used - $oldQtyOnThisRecord);

                    if ($requestedQty > $remaining) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Qty size $sizeId variant $variant melebihi sisa yang tersedia ($remaining) dari proses sebelumnya."
                        ], 422);
                    }
                }
            }

            // ============================
            // UPDATE PRODUCTION DATA
            // ============================
            $production = Production::findOrFail($id);
            $production->fill($request->only(['model_id', 'activity_role_id', 'remark']));
            $production->updated_by = Auth::id();
            $production->save();

            // Replace items
            ProductionItem::where('production_id', $id)->forceDelete();

            $production->items()->createMany(
                array_map(fn($item) => [
                    'id' => 'PRI-' . uniqid(),
                    'size_id' => $item['size_id'],
                    'variant' => $item['variant'],
                    'qty' => $item['qty'],
                    'created_by' => Auth::id(),
                ], $validItems)
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
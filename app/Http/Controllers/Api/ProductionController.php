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
            $activityRoleId = $request->input('activity_role_id');
            $userId = Auth::id();
            $search = $request->input('search');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            $query = Production::with(['items', 'model', 'activityRole'])
                ->latest();

            if ($activityRoleId) {
                $query->where('activity_role_id', $activityRoleId);
            }

            if ($userId) {
                $query->where('created_by', $userId);
            }

            if ($search) {
                $query->whereHas('model', function($q) use ($search) {
                    $q->where('description', 'like', '%'.$search.'%');
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
            $validator = Validator::make($request->all(), [
                'model_id' => 'required|exists:tr_model,id',
                'activity_role_id' => 'required|exists:mst_activity_role,id',
                'remark' => 'nullable|string|max:100',
                'items' => 'required|array|min:1',
                'items.*.size_id' => 'required|exists:mst_size,id',
                'items.*.qty' => 'nullable|integer|min:1' // allow null qty
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
    
            // Filter valid items (qty not null)
            $validItems = array_filter($request->items, function ($item) {
                return $item['qty'] !== null;
            });
    
            if (count($validItems) === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Minimal 1 item dan quantity 1'
                ], 422);
            }
    
            // Validasi qty tidak melebihi qty cutting
            if ($request->activity_role_id != 1) { // Jika bukan cutting
                $cuttingProduction = Production::with('items')
                    ->where('model_id', $request->model_id)
                    ->where('activity_role_id', 1) // Cutting
                    ->first();
    
                if (!$cuttingProduction) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Data cutting belum tersedia'
                    ], 422);
                }
    
                foreach ($validItems as $item) {
                    $cuttingItem = $cuttingProduction->items
                        ->where('size_id', $item['size_id'])
                        ->first();
    
                    if (!$cuttingItem) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Size ' . $item['size_id'] . ' tidak ditemukan pada data cutting'
                        ], 422);
                    }
    
                    if ($item['qty'] > $cuttingItem->qty) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Qty untuk size ' . $item['size_id'] . ' tidak boleh melebihi qty cutting (' . $cuttingItem->qty . ')'
                        ], 422);
                    }
                }
            }
    
            $production = Production::create([
                'id' => 'PRD-' . uniqid(),
                'model_id' => $request->model_id,
                'activity_role_id' => $request->activity_role_id,
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
                        'created_by' => Auth::id()
                    ];
                }, $validItems)
            );
    
            DB::commit();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Production created successfully',
                'data' => $production->load(['model', 'activityRole', 'items.size'])
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
            $validator = Validator::make($request->all(), [
                'model_id' => 'required|exists:tr_model,id',
                'activity_role_id' => 'required|exists:mst_activity_role,id',
                'remark' => 'nullable|string|max:100',
                'items' => 'required|array',
                'items.*.size_id' => 'required|exists:mst_size,id',
                'items.*.qty' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validasi qty tidak melebihi qty cutting
            if ($request->activity_role_id != 1) { // Jika bukan cutting
                $cuttingProduction = Production::with('items')
                    ->where('model_id', $request->model_id)
                    ->where('activity_role_id', 1) // Cutting
                    ->first();

                if (!$cuttingProduction) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Data cutting belum tersedia'
                    ], 422);
                }

                foreach ($request->items as $item) {
                    $cuttingItem = $cuttingProduction->items
                        ->where('size_id', $item['size_id'])
                        ->first();

                    if (!$cuttingItem) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Size ' . $item['size_id'] . ' tidak ditemukan pada data cutting'
                        ], 422);
                    }

                    if ($item['qty'] > $cuttingItem->qty) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Qty untuk size ' . $item['size_id'] . ' tidak boleh melebihi qty cutting (' . $cuttingItem->qty . ')'
                        ], 422);
                    }
                }
            }

            DB::beginTransaction();

            $production = Production::findOrFail($id);
            $production->fill($request->only(['model_id', 'activity_role_id', 'qty', 'remark']));
            $production->updated_by = Auth::id();
            $production->save();

            // Delete existing items
            ProductionItem::where('production_id', $id)->delete();

            // Create new items
            foreach ($request->items as $item) {
                $productionItem = new ProductionItem();
                $productionItem->id = 'PRI-' . uniqid();
                $productionItem->production_id = $production->id;
                $productionItem->size_id = $item['size_id'];
                $productionItem->qty = $item['qty'];
                $productionItem->created_by = Auth::id();
                $productionItem->save();
            }

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
                ->findOrFail($id);

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
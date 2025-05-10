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
            $query = Production::with(['model', 'activityRole'])
                ->when($request->search, function ($q) use ($request) {
                    return $q->where('id', 'like', "%{$request->search}%")
                        ->orWhere('remark', 'like', "%{$request->search}%");
                })
                ->when($request->sort_field, function ($q) use ($request) {
                    return $q->orderBy($request->sort_field, $request->sort_order ?? 'asc');
                }, function ($q) {
                    return $q->latest();
                });

            $data = $query->paginate($request->per_page ?? 10);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch productions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
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

            DB::beginTransaction();

            // Create production
            $production = new Production();
            $production->id = 'PRD-' . uniqid();
            $production->model_id = $request->model_id;
            $production->activity_role_id = $request->activity_role_id;
            $production->remark = $request->remark;
            $production->created_by = Auth::id();
            $production->save();

            // Create production items
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
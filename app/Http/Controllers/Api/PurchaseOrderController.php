<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $purchaseOrders = PurchaseOrder::with('items.product', 'items.uom')
                ->orderBy('purchase_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($request->limit);

            return response()->json($purchaseOrders, 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'purchase_date' => 'required|date',
            'supplier' => 'required|string|max:100',
            'status' => 'string|max:100',
            'notes' => 'string|max:100',
            'nota_number' => 'required|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:mst_product,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.uom_id' => 'required|exists:mst_uom,id',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $purchaseOrder = PurchaseOrder::create([
                'id' => Str::uuid(),
                'purchase_date' => $request->purchase_date,
                'supplier' => $request->supplier,
                'notes' => $request->notes,
                'status' => $request->status ?? "Draft",
                'nota_number' => $request->nota_number,
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                $purchaseOrder->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'uom_id' => $item['uom_id'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order created successfully',
                'data' => $purchaseOrder->load('items')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $purchaseOrder = PurchaseOrder::with('items.product', 'items.uom')->find($id);

            if (!$purchaseOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase Order not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $purchaseOrder
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'purchase_date' => 'requirqed|date',
            'supplier' => 'required|string|max:100',
            'status' => 'string|max:100',
            'notes' => 'string|max:100',
            'nota_number' => 'required|string|max:100|unique:purchase_order,nota_number,' . $id . ',id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:mst_product,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.uom_id' => 'required|exists:mst_uom,id',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $purchaseOrder = PurchaseOrder::find($id);

            if (!$purchaseOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase Order not found'
                ], 404);
            }

            $purchaseOrder->update([
                'purchase_date' => $request->purchase_date,
                'supplier' => $request->supplier,
                'status' => $request->status,
                'notes' => $request->notes,
                'nota_number' => $request->nota_number,
                'updated_by' => Auth::id(),
            ]);

            $purchaseOrder->items()->delete();
            foreach ($request->items as $item) {
                $purchaseOrder->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'uom_id' => $item['uom_id'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order updated successfully',
                'data' => $purchaseOrder->load('items')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $purchaseOrder = PurchaseOrder::find($id);

            if (!$purchaseOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase Order not found'
                ], 404);
            }

            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

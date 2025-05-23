<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    /**
     * Display a listing of the inventory.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Inventory::with(['product', 'location', 'sloc', 'uom']);

        if ($request->has('location')) {
            $query->where('location_id', $request->input('location'));
        }

        if ($request->has('sloc')) {
            $query->where('sloc_id', $request->input('sloc'));
        }

        if ($request->has('product')) {
            $query->where('product_id', $request->input('product'));
        }

        $inventory = $query->paginate(10);

        return response()->json($inventory->load(['product', 'location', 'sloc', 'uom']));
    }

    /**
     * Store a newly created inventory record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:mst_product,id',
                'location_id' => 'required|exists:mst_location,id',
                'uom_id' => 'required|exists:mst_uom,id',
                'sloc_id' => 'required|exists:mst_sloc,id',
                'qty' => 'required|numeric|min:0'
            ]);

            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();

            $inventory = Inventory::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory created successfully',
                'data' => $inventory->load(['product', 'location', 'sloc', 'uom'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create inventory',
                'errors' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update the specified inventory record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $inventory = Inventory::findOrFail($id);

            $validated = $request->validate([
                'qty' => 'required|numeric|min:0'
            ]);

            $validated['updated_by'] = Auth::id();
            $inventory->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory updated successfully',
                'data' => $inventory->load(['product', 'location', 'sloc', 'uom'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update inventory',
                'errors' => $e->getMessage()
            ], 422);
        }
    }
    public function save(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:mst_location,id',
            'sloc_id' => 'nullable|exists:mst_sloc,id',
            'product_id' => 'required|exists:mst_product,id',
            'uom_id' => 'required|exists:mst_uom,id',
            'qty' => 'required|numeric|min:0'
        ]);

        $inventory = Inventory::updateOrCreate(
            [
                'location_id' => $request->location_id,
                'sloc_id' => $request->sloc_id,
                'product_id' => $request->product_id,
                'uom_id' => $request->uom_id
            ],
            [
                'qty' => $request->qty
            ]
        );

        return response()->json($inventory, $inventory->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Display the specified inventory.
     *
     * @param  \App\Models\Inventory  $inventory
     * @return \Illuminate\Http\Response
     */
    public function show(Inventory $inventory)
    {
        return response()->json($inventory->load(['product', 'location', 'sloc', 'uom']));
    }

    /**
     * Remove the specified inventory from storage.
     *
     * @param  \App\Models\Inventory  $inventory
     * @return \Illuminate\Http\Response
     */
    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        return response()->noContent();
    }
}
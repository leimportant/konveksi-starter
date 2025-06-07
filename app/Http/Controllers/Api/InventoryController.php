<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
class InventoryController extends Controller
{
    /**
     * Display a listing of the inventory.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function getStock(Request $request): JsonResponse
{
    // Ensure the user is authenticated
    Auth::check();
    // Get the user's location ID   
    if (!Auth::user()->location_id) {
        return response()->json(['error' => 'Location not found'], 404);
    }
    $locationId = Auth::user()->location_id;
    $today = Carbon::today()->toDateString();

    $query = DB::table('tr_inventory as a')
        ->select([
            'a.product_id',
            'b.name as product_name',
            'a.uom_id',
            'a.size_id',
            'a.sloc_id',
            'a.qty as qty_stock',
            'a.status',
            'b.image_path',
            'c.name as category_name',
        ])
        ->join('mst_product as b', 'a.product_id', '=', 'b.id')
        ->leftJoin('mst_category as c', 'b.category_id', '=', 'c.id')
        ->where('a.location_id', $locationId)
        ->where('a.status', 'IN');

    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('a.product_id', $search)
              ->orWhere('b.name', 'like', "%{$search}%")
              ->orWhere('c.name', 'like', "%{$search}%");
        });
    }

    // paginate instead of get()
    $inventory = $query->paginate(10);

    // Add price info to each item
    $inventory->getCollection()->transform(function ($item) use ($today) {
        $price = DB::table('mst_product_price as a')
            ->join('mst_product_price_type as b', 'a.id', '=', 'b.price_id')
            ->select([
                'b.price',
                'b.size_id',
                'b.discount',
                'b.price_sell',
                'a.effective_date',
                'a.end_date',
                'a.is_active'
            ])
            ->where('a.product_id', $item->product_id)
            ->where('b.size_id', $item->size_id)
            ->whereDate('a.effective_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('a.end_date')
                      ->orWhereDate('a.end_date', '>=', $today);
            })
            ->where('a.is_active', 1)
            ->orderByDesc('a.effective_date')
            ->get();

        if ($price->isNotEmpty()) {
            $item->price_sell = floatval($price->first()->price_sell);
            $item->discount = floatval($price->first()->discount);
            $item->price = floatval($price->first()->price);
        } else {
            $item->price_sell = null;
            $item->discount = null;
            $item->price = null;
        }

        $item->image_path = $item->image_path ? $item->image_path : "not_available.png";

        return $item;
    });

    return response()->json($inventory);
}


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

        return response()->json($inventory);
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
                'size_id' => 'required|exists:mst_size,id',
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
                'uom_id' => $request->uom_id,
                'size_id' => $request->size_id, 
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
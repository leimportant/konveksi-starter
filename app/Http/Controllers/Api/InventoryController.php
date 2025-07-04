<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\InventoryService;
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
        $price_type_id = $request->price_type_id ?? 1;

        $query = DB::table('tr_inventory as a')
            ->select([
                'a.product_id',
                'b.name as product_name',
                'a.uom_id',
                'a.size_id',
                'a.sloc_id',
                'a.qty',
                'a.qty_reserved',
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
        $inventory->getCollection()->transform(function ($item) use ($today, $price_type_id) {
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
                ->where('b.price_type_id', $price_type_id)
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

            $imageGallery = DB::table('tr_document_attachment')
                                    ->where('doc_id', $item->product_id)
                                    ->whereNull('deleted_at')
                                    ->get();

            $item->gallery_images = $imageGallery;

            $item->image_path = $imageGallery->isNotEmpty() ? ($imageGallery->first()->path ? $imageGallery->first()->path : "not_available.png") : "not_available.png";

            $item->qty_stock = $item->qty - $item->qty_reserved;
            return $item;
        });

        return response()->json($inventory);
    }


    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);
        $search = $request->input('productName', '');

        $bindings = [];
        $location = Auth::user()->location_id ?? null;
        $where = "WHERE 1 = 1";
        $where .= " AND a.location_id = ?";
        $bindings[] = $location;

        if ($search) {
            $search = '%' . $search . '%';
            $where .= " AND (b.name LIKE ? OR a.product_id LIKE ? OR d.name LIKE ? OR c.name LIKE ?)";
            $bindings[] = $search;
            $bindings[] = $search;
            $bindings[] = $search;
            $bindings[] = $search;
        }

       
        $sql = "
        SELECT 
            a.product_id,
            b.name AS product_name,
            a.location_id,
            d.name AS location_name,
            a.uom_id,
            a.sloc_id,
            c.name AS sloc_name,
            a.size_id,
            COALESCE(qty_in_data.qty_in, 0) AS qty_in,
            COALESCE(qty_out_data.qty_out, 0) AS qty_out,
            (COALESCE(qty_in_data.qty_in, 0) - COALESCE(qty_in_data.qty_reserved, 0) - COALESCE(qty_out_data.qty_out, 0)) AS qty_available
        FROM tr_inventory a
        LEFT JOIN mst_product b ON a.product_id = b.id
        LEFT JOIN mst_sloc c ON a.sloc_id = c.id
        LEFT JOIN mst_location d ON a.location_id = d.id
        LEFT JOIN (
            SELECT 
                product_id, location_id, uom_id, sloc_id, size_id, 
                SUM(qty) AS qty_in,
                SUM(qty_reserved) AS qty_reserved
            FROM tr_inventory
            WHERE status = 'IN'
            GROUP BY product_id, location_id, uom_id, sloc_id, size_id
        ) qty_in_data
            ON a.product_id = qty_in_data.product_id
            AND a.location_id = qty_in_data.location_id
            AND a.uom_id = qty_in_data.uom_id
            AND a.sloc_id = qty_in_data.sloc_id
            AND a.size_id = qty_in_data.size_id
        LEFT JOIN (
            SELECT 
                product_id, location_id, uom_id, sloc_id, size_id, 
                SUM(qty) AS qty_out
            FROM tr_inventory
            WHERE status = 'OUT'
            GROUP BY product_id, location_id, uom_id, sloc_id, size_id
        ) qty_out_data
            ON a.product_id = qty_out_data.product_id
            AND a.location_id = qty_out_data.location_id
            AND a.uom_id = qty_out_data.uom_id
            AND a.sloc_id = qty_out_data.sloc_id
            AND a.size_id = qty_out_data.size_id
        $where
       GROUP BY 
        a.product_id,
        b.name,
        a.location_id,
        d.name,
        a.uom_id,
        a.sloc_id,
        c.name,
        a.size_id,
        qty_in_data.qty_in,
        qty_in_data.qty_reserved,
        qty_out_data.qty_out";

        // Execute the query
        $results = DB::select($sql, $bindings);

        // Convert to collection
        $collection = collect($results);

        // Manual pagination
        $paginated = new LengthAwarePaginator(
            $collection->forPage($page, $perPage),
            $collection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

         return response()->json($paginated);
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

            // ====== INVENTORY - IN (lokasi tujuan)
            app(InventoryService::class)->updateOrCreateInventory([
                'product_id' => $validated['product_id'],
                'location_id' => $validated['location_id'],
                'uom_id' => $validated['uom_id'],
                'sloc_id' => $validated['sloc_id'],
            ], [
                'size_id' => $validated['size_id'],
                'qty' => $validated['qty'],
                'qty_reserved' => 0, // Set reserved qty to 0 for new inventory
            ], 'IN');

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
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
use Illuminate\Support\Facades\Log;

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
        $locationId = $this->authenticateAndGetLocationId();
        if (is_null($locationId)) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        $today = Carbon::today()->toDateString();
        $price_type_id = $request->price_type_id ?? 1;

        $inventories = $this->buildInventoryQuery($request, $locationId)->get();

        $result = $this->processInventories($inventories, $today);

        // Optional: paginate result array manually
        $page = $request->input('page', 1);
        $perPage = 10;
        $paginated = new LengthAwarePaginator(
            collect($result)->forPage($page, $perPage),
            count($result),
            $perPage,
            $page
        );

        return response()->json($paginated);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('perPage', 10);

        $query = DB::table('tr_inventory as a')
            ->select('a.*', 'b.name AS product_name', 'c.name AS sloc_name', 'd.name AS location_name')
            ->leftJoin('mst_product as b', 'a.product_id', '=', 'b.id')
            ->leftJoin('mst_sloc as c', 'a.sloc_id', '=', 'c.id')
            ->leftJoin('mst_location as d', 'a.location_id', '=', 'd.id')
            ->where('a.status', 'IN');

        if ($request->has('location')) {
            $query->where('a.location_id', $request->input('location'));
        }

        if ($request->has('name')) {
            $name = $request->input('name');
            $query->where(function ($q) use ($name) {
                $q->where('b.name', 'like', '%' . $name . '%')
                    ->orWhere('c.name', 'like', '%' . $name . '%');
            });
        }

        if ($request->has('productName')) {
            $name = $request->input('productName');
            $query->where(function ($q) use ($name) {
                $q->where('b.name', 'like', '%' . $name . '%')
                    ->orWhere('c.name', 'like', '%' . $name . '%');
            });
        }
        

        $results = $query->paginate($perPage);

        return response()->json($results);
    }


public function stockMonitoring(Request $request)
{
    $query = DB::table('tr_inventory as i')
        ->join('mst_product as p', 'p.id', '=', 'i.product_id')
        ->join('mst_location as l', 'l.id', '=', 'i.location_id')
        ->select(
            'i.product_id',
            'p.name as product_name',
            'i.uom_id',
            'i.sloc_id',
            'l.name as location_name',
            DB::raw('SUM(i.qty) as qty')
        )
        ->where('i.status', 'IN')
        ->groupBy('i.product_id', 'p.name', 'i.uom_id', 'i.sloc_id', 'l.name');

    // Filter by product name or product id
    if ($request->has('productName')) {
        $query->where(function ($q) use ($request) {
            $q->where('p.name', 'like', '%' . $request->input('productName') . '%')
              ->orWhere('i.product_id', 'like', '%' . $request->input('productName') . '%');
        });
    }

    // Paginate result
    $rawResults = $query->get();

    // Group & transform result
    $grouped = [];

    foreach ($rawResults as $item) {
        $key = "{$item->product_id}_{$item->sloc_id}";

        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'uom_id' => $item->uom_id,
                'sloc_id' => $item->sloc_id,
            ];
        }

        $locationKey = $item->location_name;

        $grouped[$key][$locationKey] = [
            'qty' => (int) $item->qty,
        ];
    }

    // Manual pagination (optional if needed)
    $page = $request->input('page', 1);
    $perPage = $request->input('perPage', 10);
    $items = array_values($grouped);
    $offset = ($page - 1) * $perPage;

    $paginated = new LengthAwarePaginator(
        array_slice($items, $offset, $perPage),
        count($items),
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
                'qty' => 'required|numeric'
            ]);

            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();

            // $inventory = Inventory::create($validated);

            // ====== INVENTORY - IN (lokasi tujuan)
            app(InventoryService::class)->updateOrCreateInventory([
                'product_id' => $validated['product_id'],
                'location_id' => $validated['location_id'],
                'uom_id' => $validated['uom_id'],
                'sloc_id' => $validated['sloc_id'],
            ], [
                'size_id' => $validated['size_id'],
                'qty' => $validated['qty'],
            ], 'IN');

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory created successfully',
                'data' => Inventory::where([
                    'product_id' => $validated['product_id'],
                    'location_id' => $validated['location_id'],
                    'uom_id' => $validated['uom_id'],
                    'sloc_id' => $validated['sloc_id'],
                ])->first()
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
    public function update(Request $request, $product_id, $location_id, $sloc_id, $size_id): JsonResponse
    {
        try {
            $inventory = Inventory::where([
                'product_id' => $product_id,
                'location_id' => $location_id,
                'sloc_id' => $sloc_id,
                'size_id' => $size_id,
            ])->firstOrFail(); // ✅ fetch model

            $validated = $request->validate([
                'qty' => 'required|numeric|min:0'
            ]);

            $validated['updated_by'] = Auth::id();
            $inventory->update($validated);

            $inventory->load(['product', 'location', 'sloc', 'uom']); // ✅ now works

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory updated successfully',
                'data' => $inventory
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
    public function show(Inventory $inventory): JsonResponse    
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

    private function authenticateAndGetLocationId(): ?int
    {
        if (!Auth::user() || !Auth::user()->location_id) {
            return null;
        }
        return Auth::user()->location_id;
    }

    private function buildInventoryQuery(Request $request, int $locationId)
    {
        $query = DB::table('tr_inventory as a')
            ->select([
                'a.product_id',
                'a.location_id',
                'b.name as product_name',
                'b.descriptions as product_description',
                'a.uom_id',
                'a.size_id',
                'a.sloc_id',
                'a.variant',
                'a.qty',
                'a.status',
                'b.image_path',
                'c.name as category_name',
            ])
            ->join('mst_product as b', 'a.product_id', '=', 'b.id')
            ->leftJoin('mst_category as c', 'b.category_id', '=', 'c.id')
            ->where('a.location_id', $locationId)
            ->whereNull('b.deleted_at')
            ->where('a.qty', '>', 0)
            ->where('a.sloc_id', 'GS00')
            ->where('a.status', 'IN');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('a.product_id', $search)
                    ->orWhere('b.name', 'like', "%{$search}%")
                    ->orWhere('c.name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('b.created_at', 'DESC')->orderBy('a.created_at', 'DESC');
    }

    private function processInventories($inventories, $today): array
    {
        $grouped = $inventories->groupBy(['product_id', 'location_id']);
        $result = [];

        foreach ($grouped as $product_id => $locationGroups) {
            $itemsToProcess = $locationGroups->flatten();
            $firstItem = $itemsToProcess->first();

            $imageGallery = $this->getImageGalleries($product_id);

            $sizes = [];

            foreach ($itemsToProcess as $item) {
                $inCart = $this->getInCartQuantity($item);
                $availableQty = $item->qty - $inCart;

                $price_store = null;
                $price_grosir = null;
                $discount = null;

                if ($item->product_id) {
                    ['price_store' => $price_store, 'price_grosir' => $price_grosir, 'discount' => $discount] = $this->getProductPriceVariants(
                        $item->product_id,
                        $item->size_id,
                        $item->variant
                    );

                    $sizes[] = [
                        'size_id' => $item->size_id,
                        'variant' => $item->variant,
                        'qty_stock' => intval($item->qty),
                        'qty_in_cart' => intval($inCart),
                        'qty_available' => intval($availableQty),
                        'price' => $price_store ? floatval($price_store) : null,
                        'price_sell' => $price_store ? floatval($price_store - ($price_store * $discount / 100)) : null,
                        'discount' => $discount ? floatval($discount) : null,

                        'price_retail' => $price_store ? floatval($price_store) : null,
                        'price_sell_retail' => $price_store ? floatval($price_store - ($price_store * $discount / 100)) : null,
                        'discount_retail' => $discount ? floatval($discount) : null,

                        'price_grosir' => $price_grosir ? floatval($price_grosir) : null,
                        'price_sell_grosir' => $price_grosir ? floatval($price_grosir - ($price_grosir * $discount / 100)) : null,
                        'discount_grosir' => $discount ? floatval($discount) : null,
                    ];
                }
            }
            $result[] = [
                'product_id' => $product_id,
                'location_id' => $firstItem->location_id,
                'sloc_id' => $firstItem->sloc_id,
                'uom_id' => $firstItem->uom_id,
                'size_id' => $firstItem->size_id,
                'variant' => $firstItem->variant,
                'product_name' => $firstItem->product_name,
                'product_description' => $firstItem->product_description,
                'category_name' => $firstItem->category_name,
                'qty_available' => $firstItem->qty,
                'price' => isset($sizes[0]['price']) ? floatval($sizes[0]['price']) : null,
                'price_sell' => isset($sizes[0]['price_sell']) ? floatval($sizes[0]['price_sell']) : null,
                'discount' => isset($sizes[0]['discount']) ? floatval($sizes[0]['discount']) : null,
                'price_retail' => isset($sizes[0]['price_retail']) ? floatval($sizes[0]['price_retail']) : null,
                'price_sell_retail' => isset($sizes[0]['price_sell_retail']) ? floatval($sizes[0]['price_sell_retail']) : null,
                'discount_retail' => isset($sizes[0]['discount_retail']) ? floatval($sizes[0]['discount_retail']) : null,
                'price_grosir' => isset($sizes[0]['price_grosir']) ? floatval($sizes[0]['price_grosir']) : null,
                'price_sell_grosir' => isset($sizes[0]['price_sell_grosir']) ? floatval($sizes[0]['price_sell_grosir']) : null,
                'discount_grosir' => isset($sizes[0]['discount_grosir']) ? floatval($sizes[0]['discount_grosir']) : null,

                'image_path' => $imageGallery->isNotEmpty() ? ($imageGallery->first()->path ?? "not_available.png") : "not_available.png",
                'gallery_images' => $imageGallery,
                'sizes' => $sizes,
            ];
        }

        return $result;
    }

    private function getInCartQuantity($item): int
    {
        return DB::table('cart_items')
            ->where('product_id', $item->product_id)
            ->where('size_id', $item->size_id)
            ->where('uom_id', $item->uom_id)
            ->where('variant', $item->variant)
            ->where('sloc_id', $item->sloc_id)
            ->where('location_id', $item->location_id)
            ->sum('quantity');
    }

    private function getProductPriceVariants($productId, $sizeId, $variant): array
    {
        $priceVariant = DB::table('mst_product_price_variant')
            ->where('product_id', $productId)
            ->where('size_id', $sizeId)
            ->where(function ($query) use ($variant) {
                $query->whereNull('variant')
                    ->orWhere('variant', 'all')
                    ->orWhere('variant', '')
                    ->orWhere('variant', $variant);
            })
            ->first();

        $price_store = $priceVariant->price_store ?? null;
        $price_grosir = $priceVariant->price_grosir ?? null;
        $discount = $priceVariant->discount ?? null;

        return [
            'price_store' => $price_store,
            'price_grosir' => $price_grosir,
            'discount' => $discount
        ];
    }

    private function getImageGalleries($productId)
    {
        return DB::table('tr_document_attachment')
            ->where('doc_id', $productId)
            ->whereNull('deleted_at')
            ->get();
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductCatalogController extends Controller
{
    private function authenticateAndGetLocationId(): ?int
    {
        if (!Auth::user() || !Auth::user()->location_id) {
            return null;
        }
        return Auth::user()->location_id;
    }
    public function getCatalog(Request $request)
    {
        $locationId = $this->authenticateAndGetLocationId();
        if (is_null($locationId)) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        $today = Carbon::today()->toDateString();

        // Pagination parameters
        $page = max(1, (int) $request->input('page', 1));
        $limit = max(1, (int) $request->input('limit', 10));
        $offset = ($page - 1) * $limit;

        // Base query
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

        if ($request->filled('q') || $request->filled('keyword')) {
            $keyword = trim($request->input('q', $request->input('keyword')));
            $keywords = explode(' ', $keyword);

            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('b.name', 'like', "%{$word}%")
                    ->orWhere('b.descriptions', 'like', "%{$word}%")
                    ->orWhere('c.name', 'like', "%{$word}%");
                }
            });
        }


        // Fetch all inventories (with ordering)
        $inventories = $query->orderBy('b.created_at', 'DESC')
            ->orderBy('a.created_at', 'DESC')
            ->get();

        if ($inventories->isEmpty()) {
            return response()->json([
                'data' => [],
                'page' => $page,
                'limit' => $limit,
                'total' => 0
            ]);
        }

        // Group inventories
        $grouped = $inventories->groupBy(['product_id', 'location_id']);

        // Batch fetch images and prices
        $productIds = $inventories->pluck('product_id')->unique()->toArray();
        $imageGalleries = $this->getImageGalleriesForProducts($productIds);
       
        $allResults = [];

        foreach ($grouped as $product_id => $locationGroups) {
            foreach ($locationGroups as $location_id => $items) {
                $itemsToProcess = $items->values();
                $firstItem = $itemsToProcess->first();

                $sizes = [];
                $totalAvailableQty = 0;

                foreach ($itemsToProcess as $item) {
                    $inCart = $this->getInCartQuantity($item);
                    $booking = $this->getBookingQuantity($item);
                    $availableQty = max(0, $item->qty - $inCart - $booking);
                    $totalAvailableQty += $availableQty;

                     ['retailPrice' => $retailPrice, 'grosirPrice' => $grosirPrice] = $this->getProductPrices(
                            $item->product_id,
                            $item->size_id,
                            $item->variant,
                            $today
                        );
                

                    $sizes[] = [
                        'size_id' => $item->size_id,
                        'variant' => $item->variant,
                        'qty_stock' => intval($item->qty),
                        'qty_in_cart' => intval($inCart),
                        'qty_available' => intval($availableQty),
                        'price' => $retailPrice ? floatval($retailPrice->price) : null,
                        'price_sell' => $retailPrice ? floatval($retailPrice->price_sell) : null,
                        'discount' => $retailPrice ? floatval($retailPrice->discount) : null,
                        'price_retail' => $retailPrice ? floatval($retailPrice->price) : null,
                        'price_sell_retail' => $retailPrice ? floatval($retailPrice->price_sell) : null,
                        'discount_retail' => $retailPrice ? floatval($retailPrice->discount) : null,
                        'price_grosir' => $grosirPrice ? floatval($grosirPrice->price) : null,
                        'price_sell_grosir' => $grosirPrice ? floatval($grosirPrice->price_sell) : null,
                        'discount_grosir' => $grosirPrice ? floatval($grosirPrice->discount) : null,
                    ];
                }

                $imageGallery = $imageGalleries[$product_id] ?? collect();

                $allResults[] = [
                    'product_id' => $product_id,
                    'location_id' => $firstItem->location_id,
                    'sloc_id' => $firstItem->sloc_id,
                    'uom_id' => $firstItem->uom_id,
                    'size_id' => $firstItem->size_id,
                    'variant' => $firstItem->variant,
                    'product_name' => $firstItem->product_name,
                    'product_description' => $firstItem->product_description,
                    'category_name' => $firstItem->category_name,
                    'qty_available' => $totalAvailableQty,
                    'price' => $sizes[0]['price'] ?? null,
                    'price_sell' => $sizes[0]['price_sell'] ?? null,
                    'discount' => $sizes[0]['discount'] ?? null,
                    'price_retail' => $sizes[0]['price_retail'] ?? null,
                    'price_sell_retail' => $sizes[0]['price_sell_retail'] ?? null,
                    'discount_retail' => $sizes[0]['discount_retail'] ?? null,
                    'price_grosir' => $sizes[0]['price_grosir'] ?? null,
                    'price_sell_grosir' => $sizes[0]['price_sell_grosir'] ?? null,
                    'discount_grosir' => $sizes[0]['discount_grosir'] ?? null,
                    'image_path' => $imageGallery->isNotEmpty() ? ($imageGallery->first()->path ?? "not_available.png") : "not_available.png",
                    'gallery_images' => $imageGallery,
                    'sizes' => $sizes,
                ];
            }
        }

        // Apply pagination
        $paginatedResults = array_slice($allResults, $offset, $limit);

        return response()->json([
            'data' => $paginatedResults,
            'page' => $page,
            'limit' => $limit,
            'total' => count($allResults)
        ]);
    }

    protected function getImageGalleriesForProducts(array $productIds)
    {
        $galleries = DB::table('tr_document_attachment')
            ->whereIn('doc_id', $productIds)
            ->whereNull('deleted_at')
            ->whereIn('extension', ['jpg', 'jpeg', 'png', 'webp', 'avif'])
            ->orderBy('created_at', 'ASC')
            ->get()
            ->groupBy('doc_id');
            

        return $galleries; // Collection grouped by product_id
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

    private function getBookingQuantity($item): int
    {
        return DB::table('tr_transfer_stock as a')
            ->join('tr_transfer_stock_detail as b', 'a.id', '=', 'b.transfer_id')
            ->where('a.location_id', $item->location_id)
            ->where('a.status', 'Pending')
            ->where('b.product_id', $item->product_id)
            ->where('b.uom_id', $item->uom_id)
            ->where('b.size_id', $item->size_id)
            ->where('b.variant', $item->variant)
            ->sum('b.qty');
    }

     private function getProductPrices($productId, $sizeId, $variant, $today): array
    {
        $price = DB::table('mst_product_price as a')
            ->join('mst_product_price_type as b', 'a.id', '=', 'b.price_id')
            ->select([
                'b.price',
                'b.size_id',
                'b.price_type_id',
                'b.discount',
                'b.price_sell',
                'a.effective_date',
                'a.end_date',
                'a.is_active'
            ])
            ->where('a.product_id', $productId)
            ->where('b.size_id', $sizeId)
            ->whereDate('a.effective_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('a.end_date')
                    ->orWhereDate('a.end_date', '>=', $today);
            })
            ->whereIn('b.price_type_id', [1, 2])
            ->where(function ($query) use ($variant) {
                $query->whereNull('a.variant')
                    ->orWhere('a.variant', 'all')
                    ->orWhere('a.variant', '');
            })
            ->where('a.is_active', 1)
            ->orderByDesc('a.effective_date')
            ->get();

   
        $retailPrice = $price->firstWhere('price_type_id', 1);
        $grosirPrice = $price->firstWhere('price_type_id', 2);


        return ['retailPrice' => $retailPrice, 'grosirPrice' => $grosirPrice];
    }

    public function getUserOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orders = $user->orders()->with('items.product')
            ->select('id', 'order_number', 'status', 'created_at')
            ->get();

        return response()->json($orders);
    }

    public function getUserRoles(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $roles = $user->roles()->select('name')->get();

        return response()->json($roles);
    }
}
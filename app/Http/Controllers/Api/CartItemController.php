<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\InventoryService;


class CartItemController extends Controller
{
    public function index(Request $request)
    {
        $cartItems = CartItem::with('product')
            ->where('created_by', Auth::id())
            ->get();

        return response()->json($cartItems);
    }

    /**
     * Add a product to the cart or update its quantity.
     */
    public function addToCart(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:mst_product,id',
                'size_id' => 'required',
                'uom_id' => 'required',
                'location_id' => 'required',
                'variant' => 'required',
                'price' => 'required',
                'discount' => 'numeric',
                'quantity' => 'required|numeric',
            ]);

            $location_id = Auth::user()->location_id;
            $validated['location_id'] = $validated['location_id'] ? $validated['location_id'] : $validated['location_id'] ?? $location_id;
            $validated['sloc_id'] = 'GS00';
            // Check if the product already exists in the cart
            $cartItem = CartItem::where('product_id', $validated['product_id'])
                ->where('size_id', $validated['size_id'])
                ->where('sloc_id', $validated['sloc_id'])
                ->where('variant', $validated['variant'])
                ->where('uom_id', $validated['uom_id'])
                ->where('location_id', $validated['location_id'])
                ->where('created_by', Auth::id())
                ->first();

            $price_sell = ($validated['price'] - ($validated['discount'] ?? 0));

            
            // Jika Pembelian sudah ada dan qty lebih dari 1 maka ambil harga grosir
            $cartCount = CartItem::where('created_by', Auth::id())
                ->sum('quantity');

            $cartItemCount = $cartCount + $validated['quantity'];

            $price_sell_grosir = 0;
            $price_grosir = 0;
            $discount_grosir = 0;
            if ($cartItemCount > 1) {
                $grosirPrice = $this->getGrossirPrice($validated['product_id'], $validated['price'], $validated['discount'] ?? 0, $price_sell);
                $price_sell_grosir = $grosirPrice['price_sell'] ?? $price_sell;
                $price_grosir = $grosirPrice['price'] ?? 0;
                $discount_grosir = $grosirPrice['discount'] ?? 0;
            }

            if ($cartItem) {
                // If the product exists, update the quantity
                $cartItem->update([
                    'quantity' => $validated['quantity'],
                    'price' => $validated['price'],
                    'discount' => $validated['discount'],
                    'price_sell' => $validated['price_sell'] ?? $price_sell,
                    'price_grosir' => $price_grosir,
                    'discount_grosir' => $discount_grosir,
                    'price_sell_grosir' => $price_sell_grosir,
                    'size_id' => $validated['size_id'],
                    'uom_id' => $validated['uom_id'],
                    'updated_by' => Auth::id(),
                ]);
            } else {
                // If the product does not exist, create a new cart item
                $cartItem = CartItem::create([
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity'],
                    'size_id' => $validated['size_id'],
                    'location_id' => $validated['location_id'],
                    'variant' => $validated['variant'],
                    'sloc_id' => $validated['sloc_id'],
                    'uom_id' => $validated['uom_id'],
                    'price' => $validated['price'],
                    'discount' => $validated['discount'],
                    'price_grosir' => $price_grosir,
                    'discount_grosir' => $discount_grosir,
                    'price_sell_grosir' => $price_sell_grosir,
                    'price_sell' => $validated['price_sell'] ?? $price_sell,
                    'created_by' => Auth::id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $cartItem,
                'message' => 'Product added to cart successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add product to cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getItem()
    {
        $cartItem = CartItem::with('product')
            ->where('created_by', Auth::id())
            ->get();
        return response()->json($cartItem, 201);
    }

    /**
     * Remove a product from the cart.
     */
    public function removeFromCart($id)
    {
        try {
            $cartItem = CartItem::findOrFail($id);

            $cartItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product removed from cart successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove product from cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all products from the cart.
     */
    public function clearCart()
    {
        try {
            CartItem::where('created_by', Auth::id())->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getGrossirPrice($productId, $defaultPrice, $defaultDiscount, $defaultPriceSell)
    {
        $result = DB::table('mst_product_price as a')
            ->join('mst_product_price_type as b', 'a.id', '=', 'b.price_id')
            ->where('b.price_type_id', 2) // Grosir
            ->where('b.product_id', $productId)
            ->whereDate('a.effective_date', '<=', now())
            ->whereDate('a.end_date', '>=', now())
            ->where('b.price_sell', '>', 0)
            ->select('b.price', 'b.discount', 'b.price_sell')
            ->first();

        return [
            'price' => $result->price ?? $defaultPrice,
            'discount' => $result->discount ?? $defaultDiscount,
            'price_sell' => $result->price_sell ?? $defaultPriceSell,
        ];
    }


}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
                'price'=> 'required',
                'discount' => 'numeric',
                'quantity' => 'required|numeric',
            ]);

            // Check if the product already exists in the cart
            $cartItem = CartItem::where('product_id', $validated['product_id'])
                ->where('created_by', Auth::id())
                ->first();

            $price_sell = ($validated['price'] - ($validated['discount'] ?? 0));

            if ($cartItem) {
                
                // If the product exists, update the quantity
                $cartItem->update([
                    'quantity' => $cartItem->quantity + $validated['quantity'],
                    'price' => $validated['price'],
                    'discount' => $validated['discount'],
                    'price_sell' => $validated['price_sell'] ?? $price_sell,
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
                    'uom_id' => $validated['uom_id'],
                    'price'=> $validated['price'],
                    'discount' => $validated['discount'],
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

    /**
     * Remove a product from the cart.
     */
    public function removeFromCart($id)
    {
        try {
            $cartItem = CartItem::findOrFail($id);

            if ($cartItem->created_by !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to remove this cart item',
                ], 403);
            }

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

}
<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public static function createProduct($product, $items, $request)
    {
        // Delete existing price variants and inventory entries for this product
        DB::table('mst_product_price_variant')
            ->where('product_id', $product->id)
            ->delete();

        $productPriceVariants = [];
     // Assuming sloc_id can be null or fetched from user

        foreach ($items as $item) {
            $productPriceVariants[] = [
                'product_id' => $product->id,
                'size_id' => $item['size_id'],
                'variant' => $item['variant'],
                'uom_id' => $item['uom_id'] ?? 'PCS', // Use item UOM or default to PCS
                'price_grosir' => $item['price_grosir'] ?? 0,
                'price_store' => $item['price_store'] ?? 0,
                'discount' => $item['discount'] ?? 0,
                'qty' => $item['qty'] ?? 1, // Default qty to 1 if not provided
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

        }

        if (!empty($productPriceVariants)) {
            DB::table('mst_product_price_variant')->insert($productPriceVariants);
        }

    }
}

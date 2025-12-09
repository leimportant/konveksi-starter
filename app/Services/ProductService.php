<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public static function createProduct($product, $items, $request)
    {
        DB::table('mst_product_price_variant')
            ->where('product_id', $product->id)
            ->whereIn('size_id', collect($items)->pluck('size_id'))
            ->delete();

        foreach ($items as $item) {
            DB::table('mst_product_price_variant')->updateOrInsert(
                [
                    'product_id' => $product->id,
                    'size_id' => $item['size_id'],
                    'variant' => $item['variant']
                ],
                [
                    'uom_id' => $item['uom_id'] ?? 'PCS',
                    'price_grosir' => $item['price_grosir'] ?? 0,
                    'price_store' => $item['price_store'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'qty' => $item['qty'] ?? 1,
                    'updated_by' => Auth::id(),
                    'updated_at' => now()
                ]
            );
        }

    }
}

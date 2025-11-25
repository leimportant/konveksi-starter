<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public static function createProduct($model, $items, $request)
    {
        // Insert / Update Header Product
        DB::table('mst_product')->updateOrInsert(
            ['id' => $model->id],
            [
                'category_id'   => $request->category_id ?? 0,
                'uom_id'        => $request->uom_id ?? 'PCS',
                'name'          => $request->description ?? null,
                'descriptions'  => $request->remark ?? null,
                'created_by'    => Auth::id(),
                'updated_by'    => Auth::id(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        );

        // Hapus detail lama
        DB::table('mst_product_price_variant')
            ->where('product_id', $model->id)
            ->delete();

        // Insert detail
        $details = collect($items)->map(fn($item) => [
            'product_id' => $model->id,
            'size_id' => $item['size_id'] ?? "",
            'variant' => $item['variant'] ?? '',
            'uom_id' => $item['uom_id'] ?? 'PCS',
            'price_grosir' => $item['price_grosir'] ?? 0,
            'discount' => $item['discount'] ?? 0,
            'price_store' => $item['price_store'] ?? 0,
            'qty' => $item['qty'] ?? 1,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        DB::table('mst_product_price_variant')->insert($details);
    }
}

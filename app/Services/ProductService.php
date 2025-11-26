<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public static function createProduct($product, $items, $request)
    {
        // Insert / Update Header Product
        // The main product (mst_product) is already created in ProductController::saveCustom
        // We just need to handle its associated details (prices and inventory)

        // Hapus detail lama (jika ada update, untuk skenario create custom ini mungkin tidak relevan)
        DB::table('mst_product_price_variant')
            ->where('product_id', $product->id)
            ->delete();

        DB::table('tr_inventory')
            ->where('product_id', $product->id)
            ->delete();

        // Ambil default location_id dan sloc_id dari user yang login
        // TODO: Ini bisa diubah menjadi dynamic berdasarkan konfigurasi atau input user jika diperlukan
        $user = Auth::user();
        $locationId = $user->location_id ?? 'PL001'; // Default value
        $slocId = $user->sloc_id ?? 'SL001';       // Default value

        $productPriceVariants = [];
        $inventoryEntries = [];

        foreach ($items as $item) {
            $productPriceVariants[] = [
                'product_id' => $product->id,
                'size_id' => $item['size_id'],
                'variant' => $item['variant'],
                'uom_id' => 'PCS', // Assuming default UOM for custom products
                'price_grosir' => $item['price_grosir'],
                'price_store' => $item['price_store'],
                'discount' => 0, // Default discount for custom products
                'qty' => $item['qty'], // This qty might be for initial stock, not price rule
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $inventoryEntries[] = [
                'product_id' => $product->id,
                'location_id' => $locationId,
                'sloc_id' => $slocId,
                'size_id' => $item['size_id'],
                'variant' => $item['variant'],
                'uom_id' => $item['uom_id'] ?? "PCS",
                'qty' => $item['qty'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($productPriceVariants)) {
            DB::table('mst_product_price_variant')->insert($productPriceVariants);
        }

        if (!empty($inventoryEntries)) {
            DB::table('tr_inventory')->insert($inventoryEntries);
        }
    }
}

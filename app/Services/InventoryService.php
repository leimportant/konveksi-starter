<?php

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function updateOrCreateInventory(array $validated, array $item)
    {
        // Contoh akses size_id dari $item
        $sizeId = $item['size_id'];
    
        // Buat array data lengkap untuk updateOrCreate:
        $data = [
            'product_id' => $validated['product_id'],
            'location_id' => $validated['location_id'],
            'uom_id' => $validated['uom_id'],
            'sloc_id' => $validated['sloc_id'],
            'size_id' => $sizeId,
            'qty' => $item['qty_physical'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ];

        Log::info('Data Inventory yang akan diupdate atau dibuat:', $data);
    
        $inventory = Inventory::where('product_id', $data['product_id'])
            ->where('location_id', $data['location_id'])
            ->where('uom_id', $data['uom_id'])
            ->where('sloc_id', $data['sloc_id'])
            ->where('size_id', $data['size_id'])
            ->first();

            if ($inventory) {
                Inventory::where('product_id', $data['product_id'])
                ->where('location_id', $data['location_id'])
                ->where('uom_id', $data['uom_id'])
                ->where('sloc_id', $data['sloc_id'])
                ->where('size_id', $data['size_id'])
                ->update([
                    'qty' => $data['qty'],
                    'updated_by' => Auth::id()
                ]);
            } else {
                Inventory::create($data);
            }
    }
    
}
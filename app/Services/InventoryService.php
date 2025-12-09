<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryBooking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Update atau create inventory stok
     */
    public function updateOrCreateInventory(array $validated, array $item, $status = "IN")
    {
        // size_id & variant selalu dari $validated, BUKAN dari $item
        $sizeId  = $validated['size_id'] ?? null;
        $variant = $validated['variant'] ?? 'all';

        // Data yang akan disimpan
        $data = [
            'product_id' => $validated['product_id'],
            'location_id' => $validated['location_id'],
            'uom_id' => $validated['uom_id'],
            'sloc_id' => $validated['sloc_id'],
            'size_id' => $sizeId,
            'variant' => $variant,
            'qty' => $item['qty'],
            'status' => $status,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        Log::info('Data Inventory yang akan diupdate/dibuat:', $data);

        // Cek apakah stok sudah ada
        $inventory = Inventory::where('product_id', $data['product_id'])
            ->where('location_id', $data['location_id'])
            ->where('uom_id', $data['uom_id'])
            ->where('sloc_id', $data['sloc_id'])
            ->where('size_id', $sizeId)
            ->where('variant', $variant)
            ->where('status', $status)
            ->first();

        if ($inventory) {
            // UPDATE qty
            $inventory->update([
                'qty' => $inventory->qty + $data['qty'],
                'updated_by' => Auth::id()
            ]);
        } else {
            // CREATE record baru
            Inventory::create($data);
        }
    }


    /**
     * Update atau create booking stok
     */
    public function updateOrCreateInventoryBooking(array $validated, array $item, $status = "IN")
    {
        $sizeId  = $validated['size_id'] ?? null;
        $variant = $validated['variant'] ?? '-';

        $data = [
            'product_id' => $validated['product_id'],
            'location_id' => $validated['location_id'],
            'uom_id' => $validated['uom_id'],
            'sloc_id' => $validated['sloc_id'],
            'size_id' => $sizeId,
            'variant' => $variant,
            'qty' => $item['qty'],
            'status' => $status,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        Log::info('Data InventoryBooking yg akan diupdate/dibuat:', $data);

        $inventory = InventoryBooking::where('product_id', $data['product_id'])
            ->where('location_id', $data['location_id'])
            ->where('uom_id', $data['uom_id'])
            ->where('sloc_id', $data['sloc_id'])
            ->where('size_id', $sizeId)
            ->where('variant', $variant)
            ->where('status', $status)
            ->first();

        if ($inventory) {
            $inventory->update([
                'qty' => $inventory->qty + $data['qty'],
                'updated_by' => Auth::id(),
            ]);
        } else {
            InventoryBooking::create($data);
        }
    }
}

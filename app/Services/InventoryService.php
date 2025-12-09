<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryBooking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Update or create inventory safely.
     */
    public function updateOrCreateInventory(array $validated, array $item, $status = "IN")
    {
        try {

            // Ambil dari $validated (bukan $item)
            $sizeId  = $validated['size_id'] ?? null;
            $variant = $validated['variant'] ?? 'all';

            // Data yang akan disimpan / dicari
            $data = [
                'product_id' => $validated['product_id'],
                'location_id' => $validated['location_id'],
                'uom_id' => $validated['uom_id'],
                'sloc_id' => $validated['sloc_id'],
                'size_id' => $sizeId,
                'variant' => $variant,
                'qty' => (float) $item['qty'],
                'status' => $status,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            Log::info("Data Inventory yang akan diupdate/dibuat:", $data);

            // CARI STOCK YANG SUDAH ADA
            $inventory = Inventory::where([
                'product_id' => $data['product_id'],
                'location_id' => $data['location_id'],
                'uom_id' => $data['uom_id'],
                'sloc_id' => $data['sloc_id'],
                'size_id' => $sizeId,
                'variant' => $variant,
                'status' => $status,
            ])->first();

            if ($inventory) {

                // UPDATE aman (langsung pada object model)
                $inventory->qty = $inventory->qty + $data['qty'];
                $inventory->updated_by = Auth::id();
                $inventory->save();

                Log::info("Inventory updated", [
                    'id' => $inventory->id,
                    'new_qty' => $inventory->qty
                ]);

            } else {

                // CREATE
                Inventory::create($data);

                Log::info("Inventory created", [
                    'product_id' => $data['product_id'],
                    'location_id' => $data['location_id'],
                ]);
            }

        } catch (\Throwable $e) {

            Log::error("InventoryService update error: " . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            // Jangan hentikan seluruh proses Accept
        }
    }

    /**
     * Booking inventory (kalau Anda gunakan).
     */
    public function updateOrCreateInventoryBooking(array $validated, array $item, $status = "IN")
    {
        try {
            $sizeId  = $validated['size_id'] ?? null;

            $data = [
                'product_id' => $validated['product_id'],
                'location_id' => $validated['location_id'],
                'uom_id' => $validated['uom_id'],
                'sloc_id' => $validated['sloc_id'],
                'size_id' => $sizeId,
                'qty' => (float) $item['qty'],
                'status' => $status,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            $inventory = InventoryBooking::where([
                'product_id' => $data['product_id'],
                'location_id' => $data['location_id'],
                'uom_id' => $data['uom_id'],
                'sloc_id' => $data['sloc_id'],
                'size_id' => $sizeId,
                'status' => $status,
            ])->first();

            if ($inventory) {
                $inventory->qty = $inventory->qty + $data['qty'];
                $inventory->updated_by = Auth::id();
                $inventory->save();

            } else {
                InventoryBooking::create($data);
            }

        } catch (\Throwable $e) {
            Log::error("InventoryBooking update error: " . $e->getMessage());
        }
    }
}

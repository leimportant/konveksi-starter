<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryBooking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InventoryService
{

    public function updateOrCreateInventory(array $validated, array $item, $status = "IN")
{
        Log::info("updateOrCreateInventory");
        
        DB::transaction(function () use ($validated, $item, $status) {
        $where = [
            'product_id'   => $validated['product_id'],
            'location_id'  => $validated['location_id'],
            'uom_id'       => $validated['uom_id'],
            'sloc_id'      => $validated['sloc_id'],
            'size_id'      => $item['size_id'],
            'status'       => $status, // HARUS MASUK WHERE
        ];

        $inventory = Inventory::lockForUpdate()
            ->where($where)
            ->first();

        if ($inventory) {
            $inventory->update([
                'qty'           => $item['qty'],
                'qty_reserved'  => $item['qty_reserved'] ?? 0,
                'updated_by'    => Auth::id(),
            ]);
        } else {
            Inventory::create(array_merge($where, [
                'qty'           => $item['qty'],
                'qty_reserved'  => $item['qty_reserved'] ?? 0,
                'created_by'    => Auth::id(),
                'updated_by'    => Auth::id(),
            ]));
        }
    });
}



    public function updateOrCreateInventoryBooking(array $validated, array $item, $status = "IN")
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
            'qty' => $item['qty'],
            'qty_reserved' => $item['qty_reserved'] ?? 0,
            'status' => $status,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ];


        $inventory = InventoryBooking::where('product_id', $data['product_id'])
            ->where('location_id', $data['location_id'])
            ->where('uom_id', $data['uom_id'])
            ->where('sloc_id', $data['sloc_id'])
            ->where('size_id', $data['size_id'])
            ->where('status', $status)
            ->first();

        if ($inventory) {
            $qty_reserved = $item['qty_reserved'] ?? 0;
            Inventory::where('product_id', $data['product_id'])
                ->where('location_id', $data['location_id'])
                ->where('uom_id', $data['uom_id'])
                ->where('sloc_id', $data['sloc_id'])
                ->where('size_id', $data['size_id'])
                ->where('status', $status)
                ->update([
                    'qty' => $inventory['qty'] + $data['qty'],
                    'status' => $status,
                    'updated_by' => Auth::id()
                ]);
        } else {
            InventoryBooking::create($data);
        }
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferStockService
{
    public static function createTransfer($production, $items, $request)
    {
        self::validateTransfer($request, $items);

        DB::beginTransaction();

        try {

            /* ===========================
                INSERT / UPDATE HEADER
            ============================*/
            DB::table('tr_transfer_stock')->updateOrInsert(
                ['id' => $production->id],
                [
                    'location_id' => $request->location_id,
                    'location_destination_id' => $request->location_destination_id,
                    'sloc_id' => 'GS00',
                    'transfer_date' => now()->toDateString(),
                    'status' => 'Pending',
                    'created_by' => Auth::id(),
                    'created_at'=> now(),
                    'updated_at' => now(),
                ]
            );


            /* =======================================================
                MAPPING INPUT KEY -> size_id + variant (lowercase)
               =======================================================*/
            $newKeys = collect($items)->map(function ($i) {
                return strtolower($i['size_id'] . '|' . trim($i['variant']));
            })->toArray();


            /* =======================================================
                1) DELETE old details not in new input
               =======================================================*/
            $oldDetails = DB::table('tr_transfer_stock_detail')
                ->where('transfer_id', $production->id)
                ->get();

            foreach ($oldDetails as $old) {
                $oldKey = strtolower($old->size_id . '|' . trim($old->variant));

                if (!in_array($oldKey, $newKeys)) {
                    DB::table('tr_transfer_stock_detail')
                        ->where('transfer_id', $production->id)
                        ->where('size_id', $old->size_id)
                        ->where('variant', $old->variant)
                        ->delete();
                }
            }


            /* =======================================================
                2) INSERT / UPDATE DETAIL (updateOrInsert)
               =======================================================*/
            foreach ($items as $item) {
                $variant = ucfirst(strtolower(trim($item['variant'])));

                DB::table('tr_transfer_stock_detail')->updateOrInsert(
                    [
                        'transfer_id' => $production->id,
                        'product_id' => $request->model_id,
                        'size_id' => $item['size_id'],
                        'variant' => $variant,
                    ],
                    [
                        'uom_id' => 'PCS',
                        'qty' => $item['qty'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }


            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            throw new \Exception("Gagal menyimpan transfer stock: " . $e->getMessage());
        }
    }


    /* =======================================================
        VALIDASI
       =======================================================*/
    private static function validateTransfer($request, $items)
    {
        if (!$request->location_id || !$request->location_destination_id) {
            throw new \Exception("Lokasi Kirim dan Lokasi Tujuan wajib diisi.");
        }

        if ($request->location_id == $request->location_destination_id) {
            throw new \Exception("Lokasi tujuan tidak boleh sama dengan lokasi asal.");
        }

        if (empty($items)) {
            throw new \Exception("Tidak ada item yang ditransfer.");
        }
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferStockService
{
    public static function createTransfer($production, $items, $request)
    {
        // VALIDASI sebelum insert
        self::validateTransfer($request, $items);

        // Insert Header
        DB::table('tr_transfer_stock')->updateOrInsert(
            ['id' => $production->id],
            [
                'location_id' => $request->location_id,
                'location_destination_id' => $request->location_destination_id,
                'sloc_id' => 'GS00',
                'transfer_date' => now()->toDateString(),
                'status' => 'Pending',
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Hapus detail lama untuk mencegah duplicate
        DB::table('tr_transfer_stock_detail')->where('transfer_id', $production->id)->delete();

        // Insert detail
        $details = collect($items)->map(fn($item) => [
            'transfer_id' => $production->id,
            'product_id' => $request->model_id,
            'uom_id' => 'PCS',
            'size_id' => $item['size_id'],
            'variant' => ucfirst(strtolower($item['variant'])),
            'qty' => $item['qty'],
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        DB::table('tr_transfer_stock_detail')->insert($details);
    }
    private static function validateTransfer($request, $items)
    {
        // Cek field wajib
        if (!$request->location_id || !$request->location_destination_id) {
            throw new \Exception("Lokasi Kirim dan Lokasi Tujuan wajib diisi untuk proses transfer.");
        }
        // Contoh validasi: Pastikan lokasi tujuan tidak sama dengan lokasi asal
        if ($request->location_id == $request->location_destination_id) {
            throw new \Exception('Lokasi tujuan tidak boleh sama dengan lokasi asal.');
        }

        // Contoh validasi: Pastikan ada item yang ditransfer
        if (empty($items)) {
            throw new \Exception('Tidak ada item untuk ditransfer.');
        }

        // Validasi tambahan dapat ditambahkan di sini sesuai kebutuhan bisnis
    }
}

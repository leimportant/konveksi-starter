<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransferStock;
use App\Models\TransferStockDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\String\ByteString;
use App\Models\Inventory;
use App\Notifications\PushNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use App\Services\InventoryService;
use \Illuminate\Support\Facades\Log;


class TransferStockController extends Controller
{
    public function index()
    {
        $datas = TransferStock::with(['location', 'location_destination', 'transfer_detail'])->latest()->paginate(10);
        return response()->json($datas);
    }

    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'location_id' => 'required|exists:mst_location,id',
                'location_destination_id' => 'required|exists:mst_location,id',
                'sloc_id' => 'required|exists:mst_sloc,id',
                'transfer_detail' => 'required|array',
                'transfer_detail.*.uom_id' => 'required|exists:mst_uom,id',
                'transfer_detail.*.size_id' => 'required|exists:mst_size,id',
                'transfer_detail.*.product_id' => 'required|exists:mst_product,id',
                'transfer_detail.*.qty' => 'required|numeric|min:0.01',
            ]);

            $validated['id'] = uniqid('transfer_');
            $validated['status'] = 'Pending'; // Default status
            $validated['transfer_date'] = now();
            $validated['deleted_by'] = null; // Soft delete field
            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();

            $data = TransferStock::create($validated);

            foreach ($validated['transfer_detail'] as $detail) {
                TransferStockDetail::create([
                    'transfer_id' => $data->id,
                    'uom_id' => $detail['uom_id'],
                    'size_id' => $detail['size_id'],
                    'product_id' => $detail['product_id'],
                    'qty' => $detail['qty'],
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            Log::error('Store transfer error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal membuat transfer'], 500);
        }

        return response()->json($data, 201);
    }

    public function show($id)
    {

        $transfer = TransferStock::with(['location', 'location_destination', 'sloc', 'transfer_detail.product'])
            ->where('id', $id)
            ->firstOrFail();

        if (!$transfer) {
            return response()->json(['message' => 'Transfer tidak ditemukan'], 404);
        }

        return response()->json($transfer->load(['location', 'location_destination', 'transfer_detail.product']));
    }

    public function update(Request $request, TransferStock $transfer)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'location_id' => 'required|exists:mst_location,id',
                'location_destination_id' => 'required|exists:mst_location,id',
                'sloc_id' => 'required|exists:mst_sloc,id',
                'transfer_detail' => 'required|array',
                'transfer_detail.*.uom_id' => 'required|exists:mst_uom,id',
                'transfer_detail.*.size_id' => 'required|exists:mst_size,id',
                'transfer_detail.*.product_id' => 'required|exists:mst_product,id',
                'transfer_detail.*.qty' => 'required|numeric|min:0.01',
            ]);

            // Overwrite or add fields as needed
            $validated['status'] = 'Pending';
            $validated['transfer_date'] = now();
            $validated['deleted_by'] = null;
            $validated['updated_by'] = Auth::id();

            // Remove fields that should not be updated directly
            $validated['id'] = $transfer->id ?? $request->id; // Ensure the ID remains the same
            // unset($validated['location_id']); 
            unset($validated['transfer_date']);

            $transfer->update($validated);

            // Clear and reinsert transfer details
            TransferStockDetail::where('transfer_id', $validated['id'])->delete();

            foreach ($validated['transfer_detail'] as $type) {
                TransferStockDetail::create([
                    'transfer_id' => $validated['id'],
                    'uom_id' => $type['uom_id'],
                    'size_id' => $type['size_id'],
                    'product_id' => $type['product_id'],
                    'qty' => $type['qty'],
                ]);
            }

            DB::commit();
            return response()->json($transfer->fresh(), 200); // Use fresh() to get the latest data

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update transfer error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui transfer'], 500);
        }
    }


    public function destroy($id)
    {
        $transfer = TransferStock::where('id', $id)->firstOrFail();

        $transfer->deleted_by = Auth::id();
        $transfer->save();
        $transfer->delete();

        return response()->json(['message' => 'Transfer berhasil dihapus']);
    }

    public function accept($id)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $transfer = TransferStock::with('transfer_detail')->where('id', $id)->firstOrFail();

            if ($transfer->status !== 'Pending') {
                return response()->json(['error' => 'Transfer sudah diproses'], 400);
            }

            DB::beginTransaction();

            $transfer->status = 'Accepted';
            $transfer->updated_by = Auth::id();
            $transfer->save();

            foreach ($transfer->transfer_detail as $detail) {
                $qty = $detail['qty'] ?? 0;

                // 🔻 Kurangi stok dari lokasi asal
                app(InventoryService::class)->updateOrCreateInventory([
                    'product_id' => $detail['product_id'],
                    'location_id' => $transfer['location_id'], // lokasi asal
                    'uom_id' => $detail['uom_id'],
                    'sloc_id' => $transfer['sloc_id'],
                ], [
                    'size_id' => $detail['size_id'],
                    'qty' => -abs($qty),
                ], 'IN');

                // 🔺 Tambah stok ke lokasi tujuan
                app(InventoryService::class)->updateOrCreateInventory([
                    'product_id' => $detail['product_id'],
                    'location_id' => $transfer['location_destination_id'], // lokasi tujuan
                    'uom_id' => $detail['uom_id'],
                    'sloc_id' => $transfer['sloc_id'],
                ], [
                    'size_id' => $detail['size_id'],
                    'qty' => $qty,
                ], 'IN');
            }

            DB::commit();

            return response()->json(['message' => 'Transfer diterima']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Accept transfer error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menerima transfer'], 500);
        }
    }


    public function reject($id)
    {
        $transfer = TransferStock::where('id', $id)->firstOrFail();

        $transfer->status = 'Accepted';
        $transfer->updated_by = Auth::id();
        $transfer->save();

        $title = 'Transfer Ditolak';
        $body = 'Transfer dengan ID ' . $transfer->id . ' telah ditolak.';
        try {
            $transfer->creator->notify(new PushNotification($title, $body, route('transfer-stock.view', $transfer->id)));
            (new PushNotification($title, $body, route('transfer-stock.view', $transfer->id)))->log(true);
        } catch (\Exception $e) {
            (new PushNotification($title, $body, route('transfer-stock.view', $transfer->id)))->log(false, $e->getMessage());
        }
    }


}

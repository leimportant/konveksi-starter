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
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        if (!in_array($status, [null, 'Pending', 'Accepted', 'Rejected']) || $status == "") {
            $status = "Pending";
        }
        $query = TransferStock::with(['location', 'location_destination', 'transfer_detail.product']);

        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->where('id', 'like', '%' . $search . '%')
                ->orWhere('transfer_date', 'like', '%' . $search . '%')
                ->orWhereHas('location', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('location_destination', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('transfer_detail', function ($q) use ($search) {
                    $q->whereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
                });
        }

        $datas = $query->latest()->paginate(50);
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
                'transfer_detail.*.variant' => 'required',
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
                    'variant' => $detail['variant'],
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
                'transfer_detail.*.variant' => 'required',
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

        // Ambil transfer + detail
        $transfer = TransferStock::with('transfer_detail')
            ->where('id', $id)
            ->firstOrFail();

        if ($transfer->status !== 'Pending') {
            return response()->json(['error' => 'Transfer sudah diproses'], 400);
        }

        DB::beginTransaction();

        // Update status header
        $transfer->status = 'Accepted';
        $transfer->updated_by = Auth::id();
        $transfer->save();


        foreach ($transfer->transfer_detail as $detail) {

            // Gunakan data_get untuk antisipasi array/object/nullable
            $qty      = (float) data_get($detail, 'qty', 0);
            $productId = data_get($detail, 'product_id');
            $uomId     = data_get($detail, 'uom_id');
            $sizeId    = data_get($detail, 'size_id', null);
            $variant   = data_get($detail, 'variant', 'all');

            // Jika detail tidak valid → skip saja
            if (!$productId || !$uomId) {
                Log::warning("Detail transfer tidak lengkap", ['detail' => $detail]);
                continue;
            }

            $sloc_id = $transfer->sloc_id ?? "GS00";
            $sloc_from = $transfer->sloc_id != "GS00" ? "GS00" : $sloc_id;

            // Data umum
            $inventoryData = [
                'product_id' => $productId,
                'uom_id'     => $uomId,
                'size_id'    => $sizeId,
                'variant'    => $variant,
            ];

            // ==========================
            // KURANGI STOK DARI LOKASI ASAL
            // ==========================
            if (substr($transfer->id, 0, 4) != "PRD-") {
                app(InventoryService::class)->updateOrCreateInventory(
                    array_merge($inventoryData, [
                        'location_id' => $transfer->location_id,
                        'sloc_id'     => $sloc_from,
                    ]),
                    ['qty' => -abs($qty)],
                    'IN'
                );
            }

            // ==========================
            // TAMBAHKAN STOK KE LOKASI TUJUAN
            // ==========================
            app(InventoryService::class)->updateOrCreateInventory(
                array_merge($inventoryData, [
                    'location_id' => $transfer->location_destination_id,
                    'sloc_id'     => $transfer->sloc_id,
                ]),
                ['qty' => $qty],
                'IN'
            );
        }

        DB::commit();

        return response()->json(['message' => 'Transfer diterima']);

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error('Accept transfer error: ' . $e->getMessage(), [
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json(['message' => 'Gagal menerima transfer'], 500);
    }
}



    public function reject($id)
    {
        $transfer = TransferStock::where('id', $id)->firstOrFail();

        $transfer->status = 'Rejected';
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

<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PosOrderController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $transactions = PosTransaction::with(['orderItems.product'])
            ->leftJoin('mst_customer as c', 'pos_transaction.customer_id', '=', 'c.id')
            ->select('pos_transaction.*', DB::raw("COALESCE(c.name, 'Umum') as customer"))
            ->whereHas('orderItems', function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->where(function ($query) use ($search) {
                $query->where('pos_transaction.transaction_number', 'like', '%' . $search . '%')
                    ->orWhere('c.name', 'like', '%' . $search . '%')
                    ->orWhere('pos_transaction.total_amount', 'like', '%' . $search . '%');
            })
            ->orderBy('pos_transaction.created_at', 'desc')
            ->paginate(10);

        // Transform safely
        $transactionsCollection = $transactions->getCollection();

        $transactionsCollection->transform(function ($transaction) {
            if ($transaction->order_items) {
                $transaction->order_items->transform(function ($item) {
                    $item->product_name = $item->product->name ?? null;
                    $item->product_id = $item->product->id ?? null;
                    return $item;
                });
            }
            return $transaction;
        });

        $transactions->setCollection($transactionsCollection);

        return response()->json($transactions);
    }
    public function placeOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method_id' => 'required|exists:mst_payment_method,id',
            'paid_amount' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|integer|exists:mst_customer,id',
        ]);

        $userId = Auth::id();
        $user = Auth::user();
        $locationId = $user ? $user->location_id : null;
        $products = $request->input('items');
        $paymentMethodId = $request->input('payment_method_id');
        $customerId = $request->input('customer_id', null);
        $paidAmount = $request->input('paid_amount', null);
        $customerId = $request->input('customer_id', null);
        $transactionIdNumber = $request->input('transaction_number', null);

        DB::beginTransaction();
        try {
            // Ambil nama payment method
            $paymentMethod = DB::table('mst_payment_method')->where('id', $paymentMethodId)->value('name');
            if (!$paymentMethod) {
                return response()->json(['message' => 'Metode pembayaran tidak valid'], 422);
            }

            // Hitung total amount
            $totalAmount = 0;
            foreach ($products as $p) {
                $subtotal = $p['price'] * $p['quantity'];
                $totalAmount += $subtotal;
            }
            $paidAmount = $paidAmount ?? $totalAmount;
            $changeAmount = max(0, $paidAmount - $totalAmount);

            // Generate transaction number: YYMMDD + counter 3 digit per day
            $today = Carbon::now()->format('ymd'); // example: 230531
            $countToday = DB::table('pos_transaction')
                ->whereDate('transaction_date', Carbon::today())
                ->count();

            $counter = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
            $transactionNumber = $transactionId ?? $today . $counter;

            $id = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)) . $transactionNumber;
            // Insert pos_transaction
            $transactionId = DB::table('pos_transaction')->insertGetId([
                'id' => $id,
                'transaction_number' => $transactionNumber,
                'transaction_date' => Carbon::now(),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $paymentMethod,
                'customer_id' => $customerId ?: 0, // ensure null if not set
                'status' => 'completed',
                'notes' => 'OFFLINE-TOKO',
                'created_by' => $userId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Insert detail
            foreach ($products as $p) {
                DB::table('pos_transaction_detail')->insert([
                    'transaction_id' => $id,
                    'product_id' => $p['product_id'],
                    'product_name' => $p['product_name'] ?? "",
                    'quantity' => $p['quantity'],
                    'price' => $p['price'],
                    'uom_id' => $p['uom_id'] ?? null, // Optional UOM
                    'size_id' => $p['size_id'] ?? null, // Optional Size
                    'subtotal' => $p['price'] * $p['quantity'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // jika product_id datang nya dari product unlisted
                // inventory jangan di potong

                $unlisted = DB::table('mst_product_unlisted')
                    ->where('id', $p['product_id'])
                    ->first();
                // jika return, update stock accordingly
                if ($p['quantity'] < 0) {
                    // Update stock
                    app(InventoryService::class)->updateOrCreateInventory([
                        'product_id' => $p['product_id'],
                        'product_name' => $p['product_name'] ?? "",
                        'location_id' => $locationId,
                        'uom_id' => $p['uom_id'],
                        'sloc_id' => 'GS00', // Assuming GS01 is the source location
                    ], [
                        'size_id' => $p['size_id'],
                        'qty' => abs($p['quantity']), // Reduce stock from source location
                    ], 'IN');
                } else {

                    if (!$unlisted) {
                        app(InventoryService::class)->updateOrCreateInventory([
                            'product_id' => $p['product_id'],
                            'product_name' => $p['product_name'] ?? "",
                            'location_id' => $locationId,
                            'uom_id' => $p['uom_id'],
                            'sloc_id' => 'GS00', // Assuming GS01 is the source location
                        ], [
                            'size_id' => $p['size_id'],
                            'qty' => -abs($p['quantity']), // Reduce stock from source location
                        ], 'IN');
                    }
                    // Update stock
                    app(InventoryService::class)->updateOrCreateInventory([
                        'product_id' => $p['product_id'],
                        'product_name' => $p['product_name'] ?? "",
                        'location_id' => $locationId,
                        'uom_id' => $p['uom_id'],
                        'sloc_id' => 'GS00', // Assuming GS01 is the source location
                    ], [
                        'size_id' => $p['size_id'],
                        'qty' => $p['quantity'], // Reduce stock from source location
                    ], 'OUT');
                }
            }

            // update ke table Order where in transactionId ini comma
            $transactionIds = explode(',', $transactionIdNumber);
            DB::table('t_orders')
                ->whereIn('id', $transactionIds)
                ->update([
                    'reference_trans_id' => $id,
                    'status' => OrderStatusEnum::DONE,
                    'updated_at' => Carbon::now(),
                ]);

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully',
                'transaction_number' => $transactionNumber,
                'transaction_id' => $transactionId,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'created_at' => Carbon::now()->toDateTimeString(),
                // Optionally return the items if needed
                // 'items' => $request
            ]);
        } catch (\Throwable $e) {
            Log::info($e);
            DB::rollBack();
            return response()->json(['message' => 'Gagal menempatkan pesanan', 'error' => $e->getMessage()], 500);
        }
    }
}

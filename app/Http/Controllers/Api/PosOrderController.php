<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PosOrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:mst_product,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method_id' => 'required|exists:mst_payment_method,id',
            'paid_amount' => 'nullable|numeric|min:0', // optional, or use total_amount for paid
        ]);

        $userId = auth()->id();
        $products = $request->input('items');
        $paymentMethodId = $request->input('payment_method_id');
        $paidAmount = $request->input('paid_amount', null);

        DB::beginTransaction();
        try {
            // Ambil nama payment method
            $paymentMethod = DB::table('mst_payment_method')->where('id', $paymentMethodId)->value('name');
            if (!$paymentMethod) {
                return response()->json(['message' => 'Invalid payment method'], 422);
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
            $transactionNumber = $today . $counter;

            // Insert pos_transaction
            $transactionId = DB::table('pos_transaction')->insertGetId([
                'transaction_number' => $transactionNumber,
                'transaction_date' => Carbon::now(),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'notes' => null,
                'created_by' => $userId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Insert detail
            foreach ($products as $p) {
                DB::table('pos_transaction_detail')->insert([
                    'transaction_id' => $transactionId,
                    'product_id' => $p['product_id'],
                    'quantity' => $p['quantity'],
                    'price' => $p['price'],
                    'subtotal' => $p['price'] * $p['quantity'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

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
            DB::rollBack();
            return response()->json(['message' => 'Failed to place order', 'error' => $e->getMessage()], 500);
        }
    }
}

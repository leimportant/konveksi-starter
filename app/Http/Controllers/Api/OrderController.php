<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderCreated;
use App\Mail\OrderWaitingPayment;
use App\Mail\OrderStatusUpdated;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use App\Http\Requests\Order\StoreOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Enums\OrderStatusEnum;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request)
    {

        DB::beginTransaction();
        try {
            $order = new Order();
            $id = Order::generateUniqueShortId();
            $order->id = $id;
            $order->customer_id = Auth::id(); // Use authenticated user's ID
            $order->status = OrderStatusEnum::PENDING; // Initial status is PENDING (1)

            $paymentProofPath = null;
            if ($request->payment_method !== 'bank_transfer') {
                $paymentProofPath = $this->handlePaymentProof($request);
            }

            $order->payment_method = $request->payment_method;
            $order->payment_proof = $paymentProofPath;
            $order->created_by = Auth::id();
            $order->save(); // Save to get the auto-incremented ID for order_id

            $calculatedTotalAmount = $this->processOrderItems($request->items, $order->id);
            $order->total_amount = $calculatedTotalAmount;
            if (!$order->save()) {
                throw new \Exception('Gagal menyimpan order!');
            }

            // jika payment_method = CREDIT
            // jika creditLimit < totalAmount, throw error
            if ($request->payment_method == "credit") {
                $customer = Customer::find(Auth::id());
                if ($customer->saldo_kredit < $order->total_amount) {
                    return response()->json(['message' => 'Saldo Kredit tidak mencukupi'], 404);
                }
            }
             DB::commit();

            if ($request->payment_method === 'bank_transfer') {

                 Mail::to(Auth::user()->email)->send(new OrderWaitingPayment($order->load('orderItems.product')));

                $bankAccountInfo = Setting::where('key', 'bank_transfer_info')->first(); // Assuming a setting for bank info
                          
                return response()->json([
                    'message' => 'Order created successfully',
                    'order' => $order,
                    'bank_account_info' => $bankAccountInfo ? $bankAccountInfo->value : 'Please contact admin for bank account details.'
                ], 201);
            }


           
            return response()->json(['message' => 'Pesanan berhasil dibuat', 'order' => $order], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membuat pesanan', 'error' => $e->getMessage()], 500);
        }
    }

     private function processOrderItems(array $itemsData, string $orderId): float
    {
        $calculatedTotalAmount = 0;
        foreach ($itemsData as $itemData) {
            $product = Product::find($itemData['product_id']);

            if (!$product) {
                throw new \Exception("Product with ID {$itemData['product_id']} not found.");
            }

            $basePrice = $itemData['price']; // Unit price from request
            $totalLineDiscount = $itemData['discount'] ?? 0; // Total discount for this item line from request
            $quantity = $itemData['quantity']; // Quantity from request
            $price_sell = $itemData['price_sell']; // Quantity from request

            // Calculate price after discount per item (unit price)
            $priceAfterDiscountPerItem = $basePrice - ($totalLineDiscount / $quantity);

            $orderItem = new OrderItem();
            $orderItem->item_id = Str::uuid(); // Assign UUID
            $orderItem->order_id = $orderId; // Use the newly saved order's ID
            $orderItem->product_id = $itemData['product_id'];
            $orderItem->qty = $quantity;
            $orderItem->size_id = $itemData['size_id'] ?? null; // Optional size_id
            $orderItem->uom_id = $itemData['uom_id'] ?? null; // Optional color_id
            $orderItem->discount = $totalLineDiscount; // Store the total discount amount for this item line
            $orderItem->price = $basePrice; // Store the original product price per unit
            $orderItem->price_final = $price_sell; // Store the final price per unit after discount
            $orderItem->save();

            $calculatedTotalAmount += ($priceAfterDiscountPerItem * $quantity);
        }
        return $calculatedTotalAmount;
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,dikemas,diproses,done,cancel,,rejected',
        ]);

        $order->status = $request->status;
        $order->updated_by = Auth::id();
        $order->save();

        // Notify customer
        $customerEmail = $order->customer->email; // Assuming customer model has an email attribute
        if ($customerEmail) {
            Mail::to($customerEmail)->send(new OrderStatusUpdated($order));
        }

        return response()->json(['message' => 'Status pesanan berhasil diperbarui', 'order' => $order]);
    }

    public function approve(Request $request)
    {
        $order = Order::find($request->order_id);
        if (!$order) {
            return Inertia::render('Order/Approve', [
                'message' => 'Order Tidak di temukan atau sudah di proses',
                'order' => (object)[],
            ]);
        }
        if ($order->status != "1") {
            return Inertia::render('Order/Approve', [
                'message' => 'Order Tidak di temukan atau sudah di proses',
                'order' => [],
            ]);
        }
        $order->status = "2";
        $order->is_paid = 'Y';
        $order->updated_by = Auth::id();
        $order->save();

        // Notify customer
        $customerEmail = $order->customer->email;
        if ($customerEmail) {
            Mail::to($customerEmail)->send(new OrderStatusUpdated($order));
        }

        return response()->json(['message' => 'Pesanan berhasil disetujui',
            'order' => $order,
        ]);
    }

    private function handlePaymentProof(Request $request): ?string
    {
        if ($request->hasFile('payment_proof')) {
            $currentYear = date('Y');
            $currentMonth = date('m');
            return $request->file('payment_proof')->store("payment_proofs/{$currentYear}/{$currentMonth}", 'public');
        }
        return null;
    }

   

    public function reject(Request $request)
    {
        $order = Order::find($request->order_id);
        if (!$order) {
            return Inertia::render('Order/Reject', [
                'message' => 'Order Tidak di temukan atau sudah di proses',
                'order' => [],
            ]);
        }
        if ($order->status != "1") {
            return Inertia::render('Order/Reject', [
                'message' => 'Order Tidak di temukan atau sudah di proses',
                'order' => [],
            ]);
        }

        $order->status = "5";
        $order->updated_by = Auth::id();
        $order->save();
    }

    public function uploadPaymentProof(Request $request, Order $order)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($order->status != OrderStatusEnum::PENDING) {
            return response()->json(['message' => 'Status pesanan bukan pending.'], 400);
        }

        DB::beginTransaction();
        try {
            $paymentProofPath = $this->handlePaymentProof($request);
            $order->payment_proof = $paymentProofPath;
            $order->status = OrderStatusEnum::MENUNGGU_KONFIRMASI; // Status 2: MENUNGGU_PEMBAYARAN

            $order->updated_by = Auth::id();
            $order->save();

            DB::commit();

            // Send notification email to admin about payment proof upload
            $adminEmail = Setting::where('key', 'notif_mail_order')->first();
            if ($adminEmail && $adminEmail->value) {
                $emailAddresses = explode(',', $adminEmail->value);
                $emailAddresses = array_map('trim', $emailAddresses);
                $order->load('orderItems.product');
                Mail::to($emailAddresses)->send(new OrderCreated($order)); // Reusing OrderStatusUpdated for now
            }

            return response()->json(['message' => 'Bukti pembayaran berhasil diunggah dan status pesanan berhasil diperbarui', 'order' => $order]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mengunggah bukti pembayaran', 'error' => $e->getMessage()], 500);
        }
    }


    public function customerOrders(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Tidak terautentikasi.'], 401);
        }

        $query = Order::with('orderItems.product')->where('customer_id', $user->id);
        $status = [1,2,3,4,5];
        if ($request->has('status') == 'done') {
            $status = [6];
        }
        if ($request->has('status') == 'cancel') {
            $status = [7];
        }
        if ($request->has('status')) {
            $query->whereIn('status', $status);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('orderItems.product', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);
        return response()->json($orders, 201);
    }

    public function scan($orderId)
    {
        $order = Order::with('orderItems.product')->find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        return response()->json($order);

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
     }

     
}
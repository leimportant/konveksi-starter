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
use App\Models\BankAccount;
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
use App\Services\InventoryService;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Inventory;
use App\Models\CartItem;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Tidak terautentikasi.'], 401);
        }

        // Handle CART status secara khusus
        if ($request->input('status') === 'cart') {
            $cartItems = CartItem::with('creator','product')
                ->get();

            // FILTER berdasarkan `name` atau lainnya
            if ($request->filled('name')) {
                $search = strtolower($request->input('name'));

                $cartItems = $cartItems->filter(function ($item) use ($search) {
                    return
                        strpos(strtolower($item->product->name ?? ''), $search) !== false ||
                        strpos((string) $item->id, $search) !== false ||
                        strpos((string) $item->quantity, $search) !== false ||
                        strpos((string) $item->price_sell, $search) !== false;
                });
            }

            $fakeOrders = $cartItems->map(function ($item) use ($user) {
                $totalAmount = ($item->price_sell ?? 0) * $item->quantity;

                return [
                    'id' => 'cart-temp-' . $item->id, // pakai ID CartItem agar bisa dibatalkan per item
                    'customer_id' => $item->id,
                    'customer' => [
                        'id' => $item->id,
                        'name' => $item->creator->name ?? '-',
                    ],
                    'total_amount' => number_format($totalAmount, 2, '.', ''),
                    'status' => 1,
                    'is_paid' => 'N',
                    'resi_number' => '',
                    'order_items' => [
                        [
                            'product' => $item->product,
                            'quantity' => $item->quantity,
                            'price_sell' => $item->price_sell,
                            'size_id' => $item->size_id,
                            'uom_id' => $item->uom_id,
                        ]
                    ],
                    'created_at' => now(),
                    'payment_method' => '',
                    'payment_proof' => null,
                    'updated_at' => now(),
                ];
            });

            // Pagination manual
            $page = (int) $request->input('page', 1);
            $perPage = (int) $request->input('per_page', 10);

            $paginated = new LengthAwarePaginator(
                $fakeOrders->forPage($page, $perPage),
                $fakeOrders->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return response()->json($paginated, 200);
        }


        // Default: Ambil data dari Order
        $query = Order::with('orderItems.product', 'customer');

        $statusParam = $request->input('status');
        if ($statusParam === 'done') {
            $status = [6];
        } elseif ($statusParam === 'cancel') {
            $status = [7, 8];
        } elseif ($statusParam === 'pending') {
            $status = [1, 2, 3, 4, 5];
        } else {
            $status = [0];
        }

        $query->whereIn('status', $status);

        if ($request->filled('name')) {
            $search = $request->input('name');

            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('total_amount', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('resi_number', 'like', "%{$search}%")
                    ->orWhere('delivery_provider', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('orderItems.product', function ($q3) use ($search) {
                        $q3->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($orders, 200);
    }

    public function store(StoreOrderRequest $request)
    {

        DB::beginTransaction();
        try {
            $user = Auth::user();

            if (!$user->active) {
                throw new \Exception('Maaf customer status tidak aktif!');
            }

            $order = new Order();
            $id = Order::generateUniqueShortId();
            $order->id = $id;
            $locationId = $user->location_id ?? 1;
            $order->customer_id = $user->id; // Use authenticated user's ID
            $order->status = OrderStatusEnum::PENDING; // Initial status is PENDING (1)

            $paymentProofPath = null;
            if ($request->payment_method !== 'bank_transfer') {
                $paymentProofPath = $this->handlePaymentProof($request);
            }

            $order->location_id = $locationId;
            $order->payment_method = $request->payment_method;
            $order->payment_proof = $paymentProofPath;
            $order->created_by = Auth::id();
            $order->save(); // Save to get the auto-incremented ID for order_id

            $calculatedTotalAmount = $this->processOrderItems($request->items, $order->id, $locationId, $request->payment_method);
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

                $bankAccountInfo = BankAccount::whereNull('deleted_at')->get(); // Assuming a setting for bank info

                return response()->json([
                    'message' => 'Order created successfully',
                    'order' => $order,
                    'bank_account_info' => $bankAccountInfo->isNotEmpty()
                        ? $bankAccountInfo
                        : 'Silahkan hubungi admin untuk informasi lebih jelas'
                ], 201);
            }



            return response()->json(['message' => 'Pesanan berhasil dibuat', 'order' => $order], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membuat pesanan', 'error' => $e->getMessage()], 500);
        }
    }

    private function processOrderItems(array $itemsData, string $orderId, string $locationId, string $paymentMethod): float
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

            $status = "IN";
            if ($paymentMethod == "bank_transfer") {
                $status = "OUT";
            }
            // langsung update inventory
            $inventory = Inventory::where('product_id', $itemData['product_id'])
                ->where('location_id', $locationId)
                ->where('uom_id', $itemData['uom_id'])
                ->where('sloc_id', 'GS00')
                ->where('size_id', $itemData['size_id'])
                ->where('status', $status)
                ->first();
            $qty = $inventory ? $inventory->qty : 0;
            $qty_rese = $inventory ? $inventory->qty_reserved : 0;

            $qty_reserved = intval($qty_rese + $quantity);
            if ($paymentMethod == "bank_transfer") {
                $qty = $quantity;
                $qty_reserved = 0;
            }

            // update stock
            app(InventoryService::class)->updateOrCreateInventory([
                'product_id' => $itemData['product_id'],
                'location_id' => $locationId,
                'uom_id' => $itemData['uom_id'],
                'sloc_id' => 'GS00',
            ], [
                'size_id' => $itemData['size_id'],
                'qty' => $qty, // Reduce stock from source location
                'qty_reserved' => $qty_reserved, // Reduce stock from source location
            ], $status);
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
                'order' => (object) [],
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

        return response()->json([
            'message' => 'Pesanan berhasil disetujui',
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
        DB::beginTransaction();

        try {
            $order = Order::with('orderItems')->find($request->order_id);
            $locationId = $order->location_id;

            if (!$order || $order->status != "1") {
                return Inertia::render('Order/Reject', [
                    'message' => 'Order tidak ditemukan atau sudah diproses',
                    'order' => [],
                ]);
            }

            // Update status order
            $order->status = "5"; // Ditolak
            $order->updated_by = Auth::id();
            $order->save();

            // Kembalikan stok dari setiap item dalam pesanan
            foreach ($order->orderItems as $item) {
                // langsung update inventory
                $inventory = Inventory::where('product_id', $item->product_id)
                    ->where('location_id', $locationId)
                    ->where('uom_id', $item->uom_id)
                    ->where('sloc_id', 'GS00')
                    ->where('size_id', $item->size_id)
                    ->first();
                $qty = $inventory ? $inventory->qty : 0;
                $qty_rese = $inventory ? $inventory->qty_reserved : 0;
                $quantity = $item['qty'] ?? 0;

                $status = "IN";
                $qty_reserved = intval($qty_rese + $quantity);
                if ($order['payment_method'] == "bank_transfer") {
                    $status = "OUT";
                    $qty = $quantity;
                    $qty_reserved = 0;
                }

                // update stock
                app(InventoryService::class)->updateOrCreateInventory([
                    'product_id' => $item->product_id,
                    'location_id' => $locationId,
                    'uom_id' => $item->uom_id,
                    'sloc_id' => 'GS00',
                ], [
                    'size_id' => $item->size_id,
                    'qty' => $qty, // Reduce stock from source location
                    'qty_reserved' => $qty_reserved, // Reduce stock from source location
                ], $status);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Order berhasil ditolak dan stok telah dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menolak order: ' . $e->getMessage());
        }
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
            $order->is_paid = "Y";
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

        $statusParam = $request->input('status');

        if ($statusParam === 'done') {
            $status = [6];
        } elseif ($statusParam === 'cancel') {
            $status = [7, 8];
        } elseif ($statusParam === 'pending') {
            $status = [1, 2, 3, 4, 5]; // optional, sudah default
        } else {
            $status = [0];
        }

        $query = Order::with('orderItems.product')
            ->where(function ($q) use ($user) {
                $q->where('customer_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });

        $query->whereIn('status', $status);

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

    public function scan(Request $request)
    {
        $ids = explode(',', $request->query('ids', ''));

        if (empty($ids)) {
            return response()->json(['message' => 'No Qr tidak terdetect'], 400);
        }

        $orders = Order::with('orderItems.product')
            ->whereIn('id', $ids)
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No orders found.'], 404);
        }

        return response()->json($orders);
    }

    public function cancelOrder(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->status == OrderStatusEnum::CANCEL) {
            return response()->json(['message' => 'Order is already cancelled.'], 400);
        }

        $status = OrderStatusEnum::CANCEL;
        if ($order->payment_method == "bank_transfer") {
            $status = OrderStatusEnum::CONFIRM_CANCEL;
        }

        $order->status = $status;
        $order->updated_by = Auth::id();
        $order->save();

        // kirim email ke customer jika OrderStatusEnum::CANCEL
        // kirim email ke admin jika OrderStatusEnum::CONFIRM_CANCEL
        if ($status == OrderStatusEnum::CANCEL) {
            // update stock

            $order->load('createdBy');
            Mail::to($order->createdBy->email)->send(new OrderStatusUpdated($order));
        }
        if ($status == OrderStatusEnum::CONFIRM_CANCEL) {
            $order->load('customer');
            $adminEmail = Setting::where('key', 'notif_mail_order')->first();
            if ($adminEmail && $adminEmail->value) {
                $emailAddresses = explode(',', $adminEmail->value);
                $emailAddresses = array_map('trim', $emailAddresses);
                Mail::to($emailAddresses)->send(new OrderStatusUpdated($order));
            }
        }

        return response()->json(['message' => 'Order cancelled successfully.', 'order' => $order]);
    }

    public function checkShipping($orderId)
    {
        $data = Order::where('id', $orderId)->first();
        return response()->json(['data' => $data]);
    }

    public function submitShipping(Request $request, Order $order)
    {
        $request->validate([
            'resi_number' => 'required|string|max:255',
            'delivery_provider' => 'required|string|max:255',
            'estimation_date' => 'required|date',
            'delivery_proof' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        DB::beginTransaction();

        try {
            if ($request->hasFile('delivery_proof')) {
                $proofPath = $request->file('delivery_proof')->store('shipping_proofs', 'public');
                $order->delivery_proof = $proofPath;
            }

            $order->resi_number = $request->resi_number;
            $order->delivery_provider = $request->delivery_provider;
            $order->estimation_date = $request->estimation_date;
            $order->status = OrderStatusEnum::DIKIRIM; // use appropriate enum
            $order->updated_by = Auth::id();
            $order->save();

            DB::commit();

            return response()->json([
                'message' => 'Informasi pengiriman berhasil diperbarui',
                'order' => $order
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui informasi pengiriman',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
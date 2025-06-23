<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Mail\OrderCreated;
use App\Mail\OrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(Request $request)
    {
       $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:mst_product,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'total_amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $order = new Order();
            $id = Str::uuid();
            $order->id = $id;
            $order->customer_id = 10001; //Auth::id(); // Use authenticated user's ID
            $order->status = 'pending';
            $order->total_amount = $request->total_amount;
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                $currentYear = date('Y');
                $currentMonth = date('m');
                $paymentProofPath = $request->file('payment_proof')->store("payment_proofs/{$currentYear}/{$currentMonth}", 'public');
            }

            $order->payment_method = $request->payment_method;
            $order->payment_proof = $paymentProofPath;
            $order->created_by = Auth::id();
            $order->save(); // Save to get the auto-incremented ID for order_id

            $calculatedTotalAmount = 0;
            foreach ($request->items as $itemData) {
                $product = Product::find($itemData['product_id']);

                if (!$product) {
                    throw new \Exception("Product with ID {$itemData['product_id']} not found.");
                }

                $basePrice = $product->price; // Use product's base price from DB
                $discountPercentage = $product->discount ?? 0; // Use product's discount percentage from DB
                $quantity = $itemData['quantity']; // Use 'quantity' from request

                // Calculate price after discount
                $priceAfterDiscount = $basePrice * (1 - $discountPercentage / 100);

                $orderItem = new OrderItem();
                $orderItem->item_id = Str::uuid(); // Assign UUID
                $orderItem->order_id = $order->id; // Use the newly saved order's ID
                $orderItem->product_id = $itemData['product_id'];
                $orderItem->qty = $quantity;
                $orderItem->discount = $discountPercentage; // Store the discount percentage
                $orderItem->price = $basePrice; // Store the original product price
                $orderItem->price_final = $priceAfterDiscount; // Store the final price per unit after discount
                $orderItem->save();

                $calculatedTotalAmount += ($priceAfterDiscount * $quantity);
            }

            $order->total_amount = $calculatedTotalAmount;
            $order->save(); // Save again to update total_amount

            DB::commit();

            // Send notification email to admin
            if ($request->payment_method == "bank_transfer") {
                $adminEmail = Setting::where('key', 'notif_mail_order')->first();

                if ($adminEmail && $adminEmail->value) {
                    // Split the email string into an array of email addresses
                    $emailAddresses = explode(',', $adminEmail->value);
    
                    // Trim any whitespace from each email address
                    $emailAddresses = array_map('trim', $emailAddresses);
    
                    // Send the email to all recipients
                    Mail::to($emailAddresses)->send(new OrderCreated($order));
                }
            }
           
            return response()->json(['message' => 'Order created successfully', 'order' => $order], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create order', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,dikemas,diprosess,done,cancel,,rejected',
        ]);

        $order->status = $request->status;
        $order->updated_by = Auth::id();
        $order->save();

        // Notify customer
        $customerEmail = $order->customer->email; // Assuming customer model has an email attribute
        if ($customerEmail) {
            Mail::to($customerEmail)->send(new OrderStatusUpdated($order));
        }

        return response()->json(['message' => 'Order status updated successfully', 'order' => $order]);
    }

    public function approve(Order $order)
    {
        $order->status = 'diprosess';
        $order->is_paid = 'Y';
        $order->updated_by = Auth::id();
        $order->save();

        // Notify customer
        $customerEmail = $order->customer->email;
        if ($customerEmail) {
            Mail::to($customerEmail)->send(new OrderStatusUpdated($order));
        }

        return response()->json(['message' => 'Order approved successfully', 'order' => $order]);
    }

    public function reject(Order $order)
    {
        $order->status = 'rejected';
        $order->updated_by = Auth::id();
        $order->save();

        // Notify customer
        $customerEmail = $order->customer->email;
        if ($customerEmail) {
            Mail::to($customerEmail)->send(new OrderStatusUpdated($order));
        }

        return response()->json(['message' => 'Order rejected successfully', 'order' => $order]);
    }

    public function customerOrders(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        // $query = Order::where('customer_id', $user->id);
        $user->id = 10001;
        $query = Order::where('customer_id', $user->id);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->with('orderItems.product')->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $orders]);
    }

    public function scan($orderId)
    {
        $order = Order::with('orderItems.product')->find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
     }

     
}
<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;


class CustomerOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerOrder::query();
        $search = $request->input('search');

        if ($search) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            })->orWhere('status', 'like', '%' . $search . '%');
        }

        $orders = $query->with(['customer', 'items.product'])
                        ->orderBy('created_at', 'desc')
                        ->paginate(10);

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:mst_product,id',
                'items.*.qty' => 'required|numeric|min:0.01',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.discount' => 'numeric|min:0',
            ]);

            $totalAmount = collect($validated['items'])->sum(function ($item) {
                return ($item['price'] - ($item['discount'] ?? 0)) * $item['qty'];
            });

            $order = CustomerOrder::create([
                'customer_id' => $validated['customer_id'],
                'total_amount' => $totalAmount,
                'status' => 'pending'
            ]);

            foreach ($validated['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'discount' => $item['discount'] ?? 0,
                    'price_final' => $item['price'] - ($item['discount'] ?? 0)
                ]);
            }

            // kirim notifikasi ke admin
            // Notification::send(new OrderCreated($order));    

            DB::commit();

            return response()->json($order, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(CustomerOrder $order)
    {
        return response()->json([
            'success' => true,
            'data' => $order->load(['customer', 'items.product'])
        ]);
    }

    public function update(Request $request, CustomerOrder $order)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'status' => 'required|in:pending,di kemas,on progress,done,cancel'
            ]);

            $order->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order->fresh(['customer', 'items.product']),
                'message' => 'Order updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order: ' . $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Enums\OrderStatusEnum;
use Illuminate\Support\Facades\Log;

class OrdersStatusController2Fix extends Controller
{
    private function authenticateAndGetLocationId(): ?int
    {
        if (!Auth::check() || !Auth::user()->location_id) {
            return null;
        }
        return Auth::user()->location_id;
    }

    private function applyRoleFilter($query)
    {
        $user = Auth::user();

        switch ($user->employee_status) {
            case 'customer':
                $query->where('customer_id', $user->id);
                break;

            case 'staff':
                $query->where('created_by', $user->id);
                break;

            case 'owner':
                // owner bisa melihat semua
                break;

            default:
                $query->whereRaw('1 = 0');
                break;
        }

        return $query;
    }
    /**
     * Ambil daftar order dengan pagination
     */
    public function getOrder(Request $request)
    {
        $locationId = $this->authenticateAndGetLocationId();

        // 🔹 Log request payload dari AI/chatbot
        Log::info('Chatbot Request - /orders/status', [
            'user_id' => Auth::id(),
            'payload' => $request->all(),
            'ip' => $request->ip(),
        ]);

        if (is_null($locationId)) {
            $response = ['error' => 'Location not found'];
            Log::info('Chatbot Response - /orders/status', $response);
            return response()->json($response, 404);
        }

        $page = max(1, (int) $request->input('page', 1));
        $limit = max(1, (int) $request->input('limit', 10));
        $offset = ($page - 1) * $limit;

        $query = Order::with('orderItems')->where('location_id', $locationId);

        if ($request->filled('q')) {
            $keyword = strtolower(trim($request->input('q')));

            if ($keyword === 'belum bayar' || $keyword === 'unpaid') {
                $query->where('is_paid', 'N');
            } else if ($keyword === 'sudah bayar' || $keyword === 'paid') {
                $query->where('is_paid', 'Y');
            } else if (in_array($keyword, ['sudah selesai', 'closed', 'done', 'selesai', 'completed'])) {
                $query->where('is_paid', 'Y')
                    ->where('status', OrderStatusEnum::DONE->value);
            } else if ($keyword === 'minggu' || $keyword === 'this week') {
                // 🔹 Ambil range minggu ini
                $startOfWeek = Carbon::now()->startOfWeek(); // Senin
                $endOfWeek = Carbon::now()->endOfWeek();     // Minggu
                $query->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
            } else {
                $keywords = explode(' ', $keyword);
                $query->where(function ($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        if (is_numeric($word)) {
                            $q->orWhere('id', $word);
                        }
                        $q->orWhere('id', 'like', "%{$word}%")
                            ->orWhere('resi_number', 'like', "%{$word}%")
                            ->orWhere('status', 'like', "%{$word}%");
                    }
                });
            }
        }


        $this->applyRoleFilter($query);

        $total = $query->count();
        $paginatedResults = $query->orderByDesc('created_at')
            ->skip($offset)
            ->take($limit)
            ->get();

        $response = [
            'data' => $paginatedResults,
            'page' => $page,
            'limit' => $limit,
            'total' => $total
        ];

        // 🔹 Log response dari API ke chatbot
        Log::info('Chatbot Response - /orders/status', [
            'response' => $response
        ]);

        return response()->json($response);
    }

    /**
     * Ringkasan order berdasarkan filter
     */

    public function getOrderSummary(Request $request)
    {
        $locationId = $this->authenticateAndGetLocationId();

        Log::info('Chatbot Request - /orders/summary', [
            'user_id' => Auth::id(),
            'payload' => $request->all(),
            'locationId' => $locationId,
            'ip' => $request->ip(),
        ]);

        if (is_null($locationId)) {
            $response = ['error' => 'Location not found'];
            Log::info('Chatbot Response - /orders/summary', $response);
            return response()->json($response, 404);
        }

        $query = Order::with(['orderItems.product'])
            ->where('location_id', $locationId);
        $this->applyRoleFilter($query);

        $q = strtolower(trim($request->input('q', 'all')));
        $filter = match (true) {
            in_array($q, ['this_week', 'minggu']) => 'this_week',
            in_array($q, ['last_week', 'minggu lalu']) => 'last_week',
            in_array($q, ['this_month', 'bulan ini']) => 'this_month',
            in_array($q, ['last_month', 'bulan lalu']) => 'last_month',
            in_array($q, ['this_year', 'tahun ini']) => 'this_year',
            in_array($q, ['last_year', 'tahun lalu']) => 'last_year',
            default => 'all',
        };

        log::info('Determined filter', ['filter' => $filter]);
        Log::info('Determined start filter', ['start' => now()->startOfMonth()]);
        Log::info('Determined end filter', ['end' => now()->endOfMonth()]);

        switch (true) {
            case $filter === 'this_week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;

            case $filter === 'last_week':
                $query->whereBetween('created_at', [
                    now()->subWeek()->startOfWeek(),
                    now()->subWeek()->endOfWeek(),
                ]);
                break;

            case $filter === 'this_month':
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                break;

            case $filter === 'last_month':
                $query->whereBetween('created_at', [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth(),
                ]);
                break;

            case $filter === 'this_year':
                $query->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()]);
                break;

            case $filter === 'last_year':
                $query->whereBetween('created_at', [
                    now()->subYear()->startOfYear(),
                    now()->subYear()->endOfYear(),
                ]);
                break;

            // 🧠 Tambahan: pola dinamis seperti "months_ago:2"
            case str_starts_with($filter, 'weeks_ago:'):
                $n = (int) str_replace('weeks_ago:', '', $filter);
                $query->whereBetween('created_at', [
                    now()->subWeeks($n)->startOfWeek(),
                    now()->subWeeks($n)->endOfWeek(),
                ]);
                break;

            case str_starts_with($filter, 'months_ago:'):
                $n = (int) str_replace('months_ago:', '', $filter);
                $query->whereBetween('created_at', [
                    now()->subMonths($n)->startOfMonth(),
                    now()->subMonths($n)->endOfMonth(),
                ]);
                break;

            case str_starts_with($filter, 'years_ago:'):
                $n = (int) str_replace('years_ago:', '', $filter);
                $query->whereBetween('created_at', [
                    now()->subYears($n)->startOfYear(),
                    now()->subYears($n)->endOfYear(),
                ]);
                break;
        }


        $orders = $query->orderByDesc('created_at')->get();

        Log::info('Filter summary debug', [
            'filter' => $filter,
            'range' => [$start ?? '-', $end ?? '-'],
            'query_sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'total_found' => $query->count(),
        ]);


        // 🔹 Map hasil orders ke data yang siap dikirim ke chatbot
        $data = $orders->map(function ($order) {
            // Status Enum
            $statusEnum = $order->status instanceof OrderStatusEnum
                ? $order->status
                : OrderStatusEnum::tryFrom((int) $order->status);
            $statusLabel = $statusEnum?->label() ?? '-';

            // Payment method label
            $paymentLabel = match ($order->payment_method) {
                'cod_store' => 'Bayar di Toko 🏪',
                'cod' => 'Bayar di Tempat 💵',
                'transfer' => 'Transfer Bank 💳',
                'qris' => 'Pembayaran QRIS 📱',
                default => ucfirst(str_replace('_', ' ', $order->payment_method ?? '-')),
            };

            // Pembayaran label
            $isPaidLabel = match ($order->is_paid) {
                'Y' => 'Berhasil Dibayar ✅',
                'N' => 'Pending ⏳',
                default => 'Tidak Diketahui',
            };

            // Item details
            $items = $order->orderItems->map(function ($item) {
                return [
                    'id' => $item->item_id,
                    'product_name' => $item->product->name ?? '-',
                    'quantity' => $item->qty ?? 0,
                    'size_id' => $item->size_id ?? '-',
                    'price' => (float) ($item->price_final ?? 0),
                ];
            })->values();

            $total = $items->sum('price');

            // 🔹 HTML format untuk chatbot modern
            $itemsText = $items->map(
                fn($i) =>
                "<p><b>{$i['product_name']}</b>Size: {$i['size_id']} | Qty: {$i['quantity']} | Harga: <b>Rp" .
                number_format($i['price'], 0, ',', '.') . "</b></p>"
            )->implode('');

            $fullText = "
               <span>🧾Order #{$order->id}</span>
                <span>{$order->created_at->format('d M Y H:i')}</span>
                <span><b>Status:</b> {$statusLabel}</span>
                <span><b>Pembayaran:</b> {$isPaidLabel}</span>
                <span><b>Metode:</b> {$paymentLabel}</span>
                <span><b>Total:</b> Rp" . number_format($total, 0, ',', '.') . "</div>
              <span>{$itemsText}</span>
            ";


            return [
                'id' => $order->id,
                'total_amount' => $total,
                'is_paid' => $order->is_paid,
                'is_paid_label' => $isPaidLabel,
                'status' => $statusLabel,
                'payment_method' => $paymentLabel,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'orderItems' => $items,
                'fullText' => $fullText,
            ];
        });

        // 🔹 Grafik (AI chatbot dapat pakai untuk visualisasi)
        $chartData = [
            'by_status' => $orders->groupBy(
                fn($o) =>
                ($o->status instanceof OrderStatusEnum
                ? $o->status
                : OrderStatusEnum::tryFrom((int) $o->status)
                )?->label() ?? '-'
            )->map(fn($g) => [
                    'count' => $g->count(),
                    'total' => $g->sum(fn($o) => $o->orderItems->sum('price_final')),
                ])->values(),

            'by_day' => $orders->groupBy(fn($o) => $o->created_at->format('Y-m-d'))
                ->map(fn($g) => [
                    'date' => $g->first()->created_at->format('Y-m-d'),
                    'count' => $g->count(),
                    'total' => $g->sum(fn($o) => $o->orderItems->sum('price_final')),
                ])->values(),
            'by_product' => $orders->groupBy(fn($o) => $o->created_at->format('Y-m-d'))
                ->map(fn($g) => [
                    'date' => $g->first()->created_at->format('Y-m-d'),
                    'count' => $g->count(),
                    'total' => $g->sum(fn($o) => $o->orderItems->sum('price_final')),
                ])->values(),
        ];

        $response = [
            'filter' => $filter,
            'count' => $orders->count(),
            'data' => $data,
            'chartData' => $chartData,
        ];

        Log::info('Chatbot Response - /orders/summary', ['response' => $response]);
        return response()->json($response);
    }

}


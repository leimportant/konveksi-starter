<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Enums\OrderStatusEnum;
use Illuminate\Support\Facades\Log;

class OrdersStatusController extends Controller
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

        // =====================
        // 1️⃣ Mapping filter
        // =====================
        $filterMap = [
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'last_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
        ];

        // =====================
        // 2️⃣ Tentukan filter & periode
        // =====================
        $singlePeriodKeywords = [
            'this_week' => ['this_week', 'minggu'],
            'last_week' => ['last_week', 'minggu lalu'],
            'this_month' => ['this_month', 'bulan ini'],
            'last_month' => ['last_month', 'bulan lalu'],
            'this_year' => ['this_year', 'tahun ini'],
            'last_year' => ['last_year', 'tahun lalu'],
        ];

        $filter = 'all';
        foreach ($singlePeriodKeywords as $key => $aliases) {
            if (in_array($q, $aliases)) {
                $filter = $key;
                break;
            }
        }

        // =====================
        // 3️⃣ Tentukan apakah perbandingan
        // =====================
        $isComparison = str_contains($q, 'banding') || str_contains($q, 'dengan');

        // Helper function format date
        $getDateFormat = fn($filterName) => match ($filterName) {
            'this_week', 'last_week' => 'Y-m-d',
            'this_month', 'last_month' => 'Y-m',
            'this_year', 'last_year' => 'Y',
            default => 'Y-m-d',
        };

        // =====================
        // 4️⃣ Ambil data
        // =====================
        if ($isComparison) {
            // Untuk compare, tentukan dua periode
            // Default: bulan ini vs bulan lalu, minggu ini vs minggu lalu, tahun ini vs tahun lalu
            switch ($filter) {
                case 'this_week':
                case 'last_week':
                    $periodA = $filterMap['this_week'];
                    $periodB = $filterMap['last_week'];
                    break;
                case 'this_month':
                case 'last_month':
                    $periodA = $filterMap['this_month'];
                    $periodB = $filterMap['last_month'];
                    break;
                case 'this_year':
                case 'last_year':
                    $periodA = $filterMap['this_year'];
                    $periodB = $filterMap['last_year'];
                    break;
                default:
                    $periodA = $filterMap['this_month'];
                    $periodB = $filterMap['last_month'];
            }

            $ordersA = (clone $query)->whereBetween('created_at', $periodA)->get();
            $ordersB = (clone $query)->whereBetween('created_at', $periodB)->get();

            // Map data
            $dataA = $this->mapOrdersToData($ordersA);
            $dataB = $this->mapOrdersToData($ordersB);

            $chartDataA = $this->generateChartData($ordersA, $getDateFormat($filter));
            $chartDataB = $this->generateChartData($ordersB, $getDateFormat($filter));

            $chartData = [
                'periodA' => $chartDataA,
                'periodB' => $chartDataB,
            ];

            $count = $ordersA->count();

        } else {
            // Single period
            $period = $filterMap[$filter] ?? null;
            if ($period) {
                [$start, $end] = $period;
                $query->whereBetween('created_at', [$start, $end]);
            }

            $orders = $query->orderByDesc('created_at')->get();
            $data = $this->mapOrdersToData($orders);
            $chartData = $this->generateChartData($orders, $getDateFormat($filter));
            $count = $orders->count();
        }

        // =====================
        // 5️⃣ Return response
        // =====================
        $response = [
            'filter' => $filter,
            'count' => $count,
            'data' => $isComparison ? ['periodA' => $dataA, 'periodB' => $dataB] : $data,
            'chartData' => $chartData,
        ];

        Log::info('Chatbot Response - /orders/summary', ['response' => $response]);
        return response()->json($response);
    }


    // =====================
    // Helper: generate chartData
    // =====================
    private function mapOrdersToData($orders)
    {
        return $orders->map(function ($order) {
            $statusEnum = $order->status instanceof OrderStatusEnum
                ? $order->status
                : OrderStatusEnum::tryFrom((int) $order->status);
            $statusLabel = $statusEnum?->label() ?? '-';

            $paymentLabel = match ($order->payment_method) {
                'cod_store' => 'Bayar di Toko 🏪',
                'cod' => 'Bayar di Tempat 💵',
                'transfer' => 'Transfer Bank 💳',
                'qris' => 'Pembayaran QRIS 📱',
                default => ucfirst(str_replace('_', ' ', $order->payment_method ?? '-')),
            };

            $isPaidLabel = match ($order->is_paid) {
                'Y' => 'Berhasil Dibayar ✅',
                'N' => 'Pending ⏳',
                default => 'Tidak Diketahui',
            };

            $items = $order->orderItems->map(fn($item) => [
                'id' => $item->item_id,
                'product_name' => $item->product->name ?? '-',
                'quantity' => $item->qty ?? 0,
                'size_id' => $item->size_id ?? '-',
                'price' => (float) ($item->price_final ?? 0),
            ])->values();

            $total = $items->sum('price');

            $itemsText = $items->map(
                fn($i) =>
                "<p><b>{$i['product_name']}</b> Size: {$i['size_id']} | Qty: {$i['quantity']} | Harga: <b>Rp" .
                number_format($i['price'], 0, ',', '.') . "</b></p>"
            )->implode('');

            $fullText = "
            <span>🧾Order #{$order->id}</span>
            <span>{$order->created_at->format('d M Y H:i')}</span>
            <span><b>Status:</b> {$statusLabel}</span>
            <span><b>Pembayaran:</b> {$isPaidLabel}</span>
            <span><b>Metode:</b> {$paymentLabel}</span>
            <span><b>Total:</b> Rp" . number_format($total, 0, ',', '.') . "</span>
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
    }

    // =====================
// Helper: generate chartData
// =====================
    private function generateChartData($orders, $dateFormat)
    {
        return [
            'by_status' => $orders->groupBy(fn($o) => $o->status?->label() ?? '-')
                ->map(fn($g) => [
                    'count' => $g->count(),
                    'total' => $g->sum(fn($o) => $o->orderItems->sum('price_final')),
                ])->values(),

            'by_day' => $orders->groupBy(fn($o) => $o->created_at->format($dateFormat))
                ->map(fn($g) => [
                    'date' => $g->first()->created_at->format($dateFormat),
                    'count' => $g->count(),
                    'total' => $g->sum(fn($o) => $o->orderItems->sum('price_final')),
                ])->values(),
        ];
    }
}


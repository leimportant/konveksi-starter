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

        $page  = max(1, (int) $request->input('page', 1));
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
            'data'  => $paginatedResults,
            'page'  => $page,
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
            'ip' => $request->ip(),
        ]);

        if (is_null($locationId)) {
            $response = ['error' => 'Location not found'];
            Log::info('Chatbot Response - /orders/summary', $response);
            return response()->json($response, 404);
        }

        $query = Order::where('location_id', $locationId);
        $this->applyRoleFilter($query);

        $filter = $request->input('filter', 'all');

        switch ($filter) {
            case 'unpaid':
                $query->where('status', OrderStatusEnum::MENUNGGU_PEMBAYARAN->value);
                break;
            case 'not_shipped':
                $query->whereNotIn('status', [
                    OrderStatusEnum::DIKIRIM->value,
                    OrderStatusEnum::DONE->value,
                    OrderStatusEnum::CANCEL->value,
                    OrderStatusEnum::CONFIRM_CANCEL->value,
                    OrderStatusEnum::REJECTED->value,
                ]);
                break;
            case 'this_week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'last_week':
                $query->whereBetween('created_at', [
                    now()->subWeek()->startOfWeek(),
                    now()->subWeek()->endOfWeek()
                ]);
                break;
            case 'this_month':
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
            case 'last_month':
                $query->whereBetween('created_at', [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth()
                ]);
                break;
            case 'this_year':
                $query->whereYear('created_at', now()->year);
                break;
            case 'last_year':
                $query->whereYear('created_at', now()->subYear()->year);
                break;
            default:
                break;
        }

        $orders = $query->orderByDesc('created_at')->get();

        $response = [
            'filter' => $filter,
            'count'  => $orders->count(),
            'data'   => $orders
        ];

        Log::info('Chatbot Response - /orders/summary', [
            'response' => $response
        ]);

        return response()->json($response);
    }
}


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Enums\OrderStatusEnum;

class OrdersStatusController extends Controller
{
    private function authenticateAndGetLocationId(): ?int
    {
        if (!Auth::user() || !Auth::user()->location_id) {
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
                // Owner bisa lihat semua
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
        if (is_null($locationId)) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        // Pagination
        $page  = max(1, (int) $request->input('page', 1));
        $limit = max(1, (int) $request->input('limit', 10));
        $offset = ($page - 1) * $limit;

        // Base query
        $query = Order::with('orderItems')->where('location_id', $locationId);

        if ($request->filled('q')) {
            $keyword = strtolower(trim($request->input('q')));

            if ($keyword === 'belum bayar' || $keyword === 'unpaid') {
                $query->where('is_paid', 'N');
            } else if ($keyword === 'sudah bayar' || $keyword === 'paid') {
                $query->where('is_paid', 'Y');
            } else if ($keyword === 'sudah selesai' || $keyword === 'closed' || $keyword === 'done' || $keyword === 'selesai' || $keyword === 'completed') {
                $query->where('is_paid', 'Y')
                        ->where('status', OrderStatusEnum::DONE->value);
            } else {
                $keywords = explode(' ', $keyword);

                $query->where(function ($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        if (is_numeric($word)) {
                            $q->orWhere('id', $word);
                        }
                        $q->orWhere('id', 'like', "%{$word}%");
                        $q->orWhere('resi_number', 'like', "%{$word}%");
                        $q->orWhere('status', 'like', "%{$word}%");
                    }
                });
            }
        }

        // Filter role
        $this->applyRoleFilter($query);

        // Total data
        $total = $query->count();

        // Data dengan pagination
        $paginatedResults = $query
            ->orderByDesc('created_at')
            ->skip($offset)
            ->take($limit)
            ->get();

        return response()->json([
            'data'  => $paginatedResults,
            'page'  => $page,
            'limit' => $limit,
            'total' => $total
        ]);
    }

    /**
     * Ringkasan order berdasarkan filter
     */
    public function getOrderSummary(Request $request)
    {
        $locationId = $this->authenticateAndGetLocationId();
        if (is_null($locationId)) {
            return response()->json(['error' => 'Location not found'], 404);
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
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->whereNotIn('status', [
                    OrderStatusEnum::CANCEL->value,
                    OrderStatusEnum::CONFIRM_CANCEL->value,
                    OrderStatusEnum::REJECTED->value,
                ]);
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
                // tanpa filter
                break;
        }

        $orders = $query->orderByDesc('created_at')->get();

        return response()->json([
            'filter' => $filter,
            'count'  => $orders->count(),
            'data'   => $orders
        ]);
    }
}

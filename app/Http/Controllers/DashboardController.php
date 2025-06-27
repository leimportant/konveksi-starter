<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ModelRef;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class DashboardController extends Controller
{
    public function index(Request $request)
    {
       
        // You can add any dashboard statistics or data here
        $total_order = ModelRef::count();
        $total_products = Product::count();
        $total_users = Customer::count();
        $total_sales = Order::where('is_paid', 'Y')->sum('total_amount');
        $data = [
            'stats' => [
                'total_order' => $total_order,
                'total_transactions' => $total_sales,
                'total_products' => $total_products,
                'total_users' => $total_users,
            ],
        ];

        return response()->json($data);
    }

    public function getSalesData(Request $request)
    {
        $startDate = $request->input('start_date') ?? now()->startOfMonth()->format('Y-m-d'); // Default to the first day of the current month
        $endDate = $request->input('end_date') ?? now()->endOfMonth()->format('Y-m-d'); // Default to the last day of the current month

        // Query sales data
        $salesData = DB::table('t_orders as a')
            ->join('t_order_items as b', 'a.id', '=', 'b.order_id')
            ->leftJoin('mst_product as c', 'b.product_id', '=', 'c.id')
            ->selectRaw('DATE(a.created_at) AS sale_date, b.product_id, c.name AS product_name, SUM(b.qty) AS total_sold')
            ->whereIn('a.status', ['done', 'diproses']) // hanya order selesai
            ->whereBetween('a.created_at', [$startDate, $endDate])
            ->groupBy('sale_date', 'b.product_id', 'c.name')
            ->orderBy('sale_date', 'ASC')
            ->get();

        return response()->json( ['data' => $salesData]);
    }

}
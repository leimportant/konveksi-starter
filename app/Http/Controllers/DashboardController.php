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
        $salesData = DB::table('pos_transaction as a')
            ->join('pos_transaction_detail as b', 'a.id', '=', 'b.transaction_id')
            ->leftJoin('mst_product as c', 'b.product_id', '=', 'c.id')
            ->selectRaw('DATE(a.created_at) AS sale_date, b.product_id, c.name AS product_name, SUM(b.quantity) AS total_sold')
            ->whereIn('a.status', ['completed']) // hanya order selesai
            ->whereBetween('a.created_at', [$startDate, $endDate])
            ->groupBy('a.created_at', 'b.product_id', 'c.name')
            ->orderBy('a.created_at', 'ASC')
            ->get();

        return response()->json( ['data' => $salesData]);
    }

    public function getSalesByOmsetData(Request $request)
    {
        $startDate = $request->input('start_date') ?? now()->startOfMonth()->toDateString();
        $endDate = $request->input('end_date') ?? now()->endOfMonth()->toDateString();

        $salesData = DB::table('pos_transaction as a')
            ->join('pos_transaction_detail as b', 'a.id', '=', 'b.transaction_id')
            ->where('a.status', 'completed')
            ->whereBetween('a.created_at', [$startDate, $endDate])
            ->selectRaw('DATE(a.created_at) as sale_date, SUM(a.total_amount) as total_amount')
            ->groupByRaw('DATE(a.created_at)')
            ->orderBy('sale_date')
            ->get();

        return response()->json([
            'data' => $salesData
        ]);
    }


}
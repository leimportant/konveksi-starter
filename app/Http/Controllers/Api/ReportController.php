<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function reportSalesSummary(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $searchKey = $request->input('search_key');

        $query = DB::table('t_orders as a')
            ->leftJoin('t_order_items as b', 'a.id', '=', 'b.order_id')
            ->leftJoin('mst_product as c', 'b.product_id', '=', 'c.id')
            ->leftJoin('mst_customer as d', 'a.customer_id', '=', 'd.id')
            ->select('a.customer_id', 'a.payment_method', 'a.status', 'b.product_id', 'c.name as product_name', 'b.qty', 'b.uom_id', 'b.size_id', 'b.discount', 'b.price', 'b.price_final')
            ->whereBetween(DB::raw('DATE(a.created_at)'), [$startDate, $endDate])
            ->where('a.status', '!=', '1');

        if ($searchKey) {
            $query->where('c.name', 'like', "%" . $searchKey . "%");
        }

        $results = $query->get();

        return response()->json($results);
    }

    public function reportProductionSummary(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $searchKey = $request->input('search_key');

        $query = DB::table('tr_model as a')
            ->join('tr_production as b', 'a.id', '=', 'b.model_id')
            ->leftJoin('tr_production_item as c', 'b.id', '=', 'c.production_id')
            ->leftJoin('mst_activity_role as d', 'b.activity_role_id', '=', 'd.id')
            ->select(
                'a.id',
                'a.description',
                'a.estimation_price_pcs',
                'a.estimation_qty',
                'a.start_date',
                'a.end_date',
                'b.activity_role_id',
                'd.name as activity_role_name',
                'c.size_id',
                'c.qty'
            )
            ->whereNull('b.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereBetween('a.created_at', [$startDate, $endDate]);

        if ($searchKey) {
            $query->where(function ($q) use ($searchKey) {
                $q->where('a.description', 'like', "%" . $searchKey . "%")
                    ->orWhere('b.activity_role_id', 'like', "%" . $searchKey . "%")
                    ->orWhere('c.size_id', 'like', "%" . $searchKey . "%");
            });
        }

        $data = $query->get();

        $activityTotals = [];

        foreach ($data as $row) {
            $modelId = $row->id;

            if (!isset($pivot[$modelId])) {
                $pivot[$modelId] = [
                    'model_id' => $row->id,
                    'description' => $row->description,
                    'estimation_price_pcs' => $row->estimation_price_pcs,
                    'estimation_qty' => $row->estimation_qty,
                    'start_date' => Carbon::parse($row->start_date)->format('d/m/Y'),
                    'end_date' => $row->end_date ? Carbon::parse($row->end_date)->format('d/m/Y') : null,
                    'activities' => [],
                    'subtotal_qty' => 0,
                ];
            }

            $activity = $row->activity_role_id ?? 'unknown';
            $activityName = $row->activity_role_name ?? 'Unknown';
            $qty = $row->qty ?? 0;

            if (!isset($pivot[$modelId]['activities'][$activity])) {
                $pivot[$modelId]['activities'][$activity] = [
                    'role_id' => $activity,
                    'name' => $activityName,
                    'qty' => 0
                ];
            }

            $pivot[$modelId]['activities'][$activity]['qty'] += $qty;
            $pivot[$modelId]['subtotal_qty'] += $qty;

            // Tambahkan ke total per activity type
            if (!isset($activityTotals[$activity])) {
                $activityTotals[$activity] = 0;
            }
            $activityTotals[$activity] += $qty;
        }

        $productionSummary = array_values($pivot); // reset keys

        return response()->json([
            'data' => $productionSummary,
            'activity_totals' => $activityTotals, // ⬅️ Global subtotal by activity_type
        ]);
    }

    public function reportOmsetPerPayment(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $rawData = DB::select("
        SELECT 
            DATE_FORMAT(transaction_date, '%d/%m/%Y') AS tanggal,
            payment_method,
            SUM(paid_amount - change_amount) AS total_omset
        FROM 
            pos_transaction
        WHERE 
            status = 'completed'
            AND DATE(transaction_date) BETWEEN :startDate AND :endDate
        GROUP BY 
            DATE_FORMAT(transaction_date, '%d/%m/%Y'), payment_method
        ORDER BY 
            DATE_FORMAT(transaction_date, '%d/%m/%Y') ASC, payment_method ASC
    ", [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        // Convert to array of objects
        $grouped = [];
        foreach ($rawData as $item) {
            $tanggal = $item->tanggal;
            if (!isset($grouped[$tanggal])) {
                $grouped[$tanggal] = [];
            }
            $grouped[$tanggal][] = $item;
        }

        // Inject subtotal rows
        $result = [];
        foreach ($grouped as $tanggal => $rows) {
            $subtotal = 0;
            foreach ($rows as $row) {
                $subtotal += $row->total_omset;
                $result[] = $row;
            }
            $result[] = (object) [
                'tanggal' => $tanggal,
                'payment_method' => 'SUBTOTAL',
                'total_omset' => $subtotal,
            ];
        }

        $perPage = $request->input('per_page', 10); // Default to 10 items per page
        $page = $request->input('page', 1); // Default to page 1

        $paginatedResult = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($result)->forPage($page, $perPage),
            count($result),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($paginatedResult);
    }

    public function reportProductionDetail(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $searchKey = $request->input('search_key');

        $query = DB::table('tr_model as a')
            ->join('tr_production as b', 'a.id', '=', 'b.model_id')
            ->leftJoin('tr_production_item as c', 'b.id', '=', 'c.production_id')
            ->leftJoin('mst_activity_role as d', 'b.activity_role_id', '=', 'd.id')
            ->leftJoin('users as u', 'b.created_by', '=', 'u.id') // join ke users
            ->select(
                'a.id',
                'a.description',
                'a.estimation_price_pcs',
                'a.estimation_qty',
                'a.start_date',
                'a.end_date',
                'b.created_by',
                'u.name as created_by_name', // nama staff
                'b.activity_role_id',
                'd.name as activity_role_name',
                'c.size_id',
                'c.qty',
                'b.remark' // include remark
            )
            ->whereNull('b.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereBetween('a.created_at', [$startDate, $endDate]);

        if ($searchKey) {
            $query->where(function ($q) use ($searchKey) {
                $q->where('a.description', 'like', '%' . $searchKey . '%')
                    ->orWhere('b.activity_role_id', 'like', '%' . $searchKey . '%')
                    ->orWhere('c.size_id', 'like', '%' . $searchKey . '%');
            });
        }

        $data = $query->get();

        $pivot = [];
        $activityTotals = [];

        foreach ($data as $row) {
            $modelId = $row->id;

            if (!isset($pivot[$modelId])) {
                $pivot[$modelId] = [
                    'model_id' => $row->id,
                    'description' => $row->description,
                    'created_by' => $row->created_by_name ?? 'Unknown',
                    'estimation_price_pcs' => $row->estimation_price_pcs,
                    'estimation_qty' => $row->estimation_qty,
                    'start_date' => Carbon::parse($row->start_date)->format('d/m/Y'),
                    'end_date' => $row->end_date ? Carbon::parse($row->end_date)->format('d/m/Y') : null,
                    'activities' => [],
                    'subtotal_qty' => 0,
                ];
            }

            $activityId = $row->activity_role_id ?? 'unknown';
            $activityName = $row->activity_role_name ?? 'Unknown';
            $qty = $row->qty ?? 0;

            if (!isset($pivot[$modelId]['activities'][$activityId])) {
                $pivot[$modelId]['activities'][$activityId] = [
                    'role_id' => $activityId,
                    'name' => $activityName,
                    'qty' => 0,
                    'remark' => $row->remark ?? null
                ];
            }

            $pivot[$modelId]['activities'][$activityId]['qty'] += $qty;
            $pivot[$modelId]['subtotal_qty'] += $qty;

            // Global activity total
            if (!isset($activityTotals[$activityId])) {
                $activityTotals[$activityId] = 0;
            }
            $activityTotals[$activityId] += $qty;
        }

        $productionSummary = array_values($pivot);

        return response()->json([
            'data' => $productionSummary,
            'activity_totals' => $activityTotals,
        ]);
    }


}
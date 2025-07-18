<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        $pivot = [];
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

        $paginatedResult = new LengthAwarePaginator(
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
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        // Query dasar
        $rawData = DB::table('tr_production as a')
            ->join('tr_production_item as b', 'a.id', '=', 'b.production_id')
            ->join('tr_model as c', 'a.model_id', '=', 'c.id')
            ->join('users as d', 'a.created_by', '=', 'd.id')
            ->leftJoin('tr_model_activity as e', function ($join) {
                $join->on('a.model_id', '=', 'e.model_id')
                    ->on('a.activity_role_id', '=', 'e.activity_role_id');
            })
            ->leftJoin('mst_activity_role as f', 'a.activity_role_id', '=', 'f.id')
            ->select([
                'a.created_at',
                'a.model_id',
                'c.description as model_name',
                'a.activity_role_id',
                'f.name as activity_role_name',
                'a.created_by',
                'd.name as staff_name',
                'b.size_id',
                'b.variant',
                'b.qty',
                'e.price as unit_price',
            ])
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('a.created_at', [$startDate, $endDate]);
            })
            ->when($searchKey, function ($q) use ($searchKey) {
                $q->where('c.description', 'like', '%' . $searchKey . '%')
                    ->orWhere('d.name', 'like', '%' . $searchKey . '%')
                    ->orWhere('f.name', 'like', '%' . $searchKey . '%')
                    ->orWhere('b.variant', 'like', '%' . $searchKey . '%');
            })
            ->get();

        // Hitung total per row
        foreach ($rawData as $row) {
            $row->total = ($row->qty ?? 0) * ($row->unit_price ?? 0);
        }

        // Grouping untuk subtotal: staff_name
        $grouped = [];
        foreach ($rawData as $row) {
            $key = $row->staff_name;
            $grouped[$key][] = $row;
        }

        // Format hasil akhir dengan subtotal dan total
        $result = [];
        $grandTotal = 0;

        foreach ($grouped as $key => $rows) {
            $subtotal = 0;
            foreach ($rows as $row) {
                $result[] = [
                    'created_at' => $row->created_at,
                    'staff_name' => $row->staff_name,
                    'activity_role_name' => $row->activity_role_name,
                    'model_name' => $row->model_name,
                    'variant' => $row->variant,
                    'qty' => $row->qty,
                    'unit_price' => floatval($row->unit_price),
                    'total' => floatval($row->total),
                ];
                $subtotal += floatval($row->total);
                $grandTotal += floatval($row->total);

            }

            $staff_name = $key;

            $result[] = [
                'created_at' => null,
                'staff_name' => null,
                'activity_role_name' => null,
                'model_name' => 'SUBTOTAL ',
                'variant' => null,
                'qty' => null,
                'unit_price' => null,
                'total' => $subtotal,
            ];
        }

        // Baris TOTAL
        $result[] = [
            'created_at' => null,
            'staff_name' => null,
            'activity_role_name' => null,
            'model_name' => 'TOTAL',
            'variant' => null,
            'qty' => null,
            'unit_price' => null,
            'total' => floatval($grandTotal),
        ];

        // Pagination manual
        $paginated = new LengthAwarePaginator(
            collect($result)->forPage($page, $perPage),
            count($result),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'data' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ]
        ]);
    }


    public function reportOmsetPerCustomer(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $searchKey = $request->input('searchKey');
        $customer_id = $request->input('customer_id');

        
        $rawData = $this->getRawTransactionData($startDate, $endDate, $searchKey, $customer_id);
        $grouped = $this->groupTransactionsByCustomer($rawData);
        $reportRows = $this->buildReportWithSubtotals($grouped);
        $paginated = $this->paginateReport($reportRows, $page, $perPage, $request);

        return response()->json([
            'data' => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    private function getRawTransactionData(string $startDate, string $endDate, ?string $searchKey, ?int $customer_id): Collection

    {

        $user = Auth::user();
        $filterCustomerId = "";
        if ($user->employee_status == "customer" && $customer_id > 0) {
            $filterCustomerId = Auth::id();
        }

        $data = DB::table('pos_transaction as a')
            ->join('pos_transaction_detail as b', 'a.id', '=', 'b.transaction_id')
            ->leftJoin('mst_customer as c', 'a.customer_id', '=', 'c.user_id')
            ->leftJoin('mst_product as d', 'b.product_id', '=', 'd.id')
            ->select(
                'a.id',
                DB::raw("DATE_FORMAT(a.transaction_date, '%d/%m/%Y') as tanggal"),
                'a.customer_id',
                DB::raw("COALESCE(c.name, 'Umum') as customer"),
                'b.product_id',
                'd.name as product',
                'b.quantity as qty',
                'b.price',
                'a.notes',
                'b.subtotal as total'
            )
            ->whereBetween(DB::raw('DATE(a.transaction_date)'), [$startDate, $endDate])
            ->when($filterCustomerId, function ($query, $filterCustomerId) { // Corrected closure signature
                $query->where('a.customer_id', $filterCustomerId);
            })
            ->when($searchKey !== null && $searchKey !== '', function ($query) use ($searchKey) {
                $query->where(function ($q) use ($searchKey) {
                    if (is_numeric($searchKey)) {
                        $q->where('a.customer_id', $searchKey)
                            ->orWhere('b.product_id', $searchKey);
                    } else {
                        $q->whereRaw('LOWER(c.name) LIKE ?', ['%' . strtolower($searchKey) . '%'])
                            ->orWhereRaw('LOWER(d.name) LIKE ?', ['%' . strtolower($searchKey) . '%']);
                    }
                });
            })

            ->orderBy('c.name')
            ->orderBy('a.transaction_date')
            ->get();

        return $data;


    }

    private function groupTransactionsByCustomer(Collection $rawData): array
    {
        $grouped = [];

        foreach ($rawData as $row) {
            $key = $row->customer ?? 'Unknown';
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $row;
        }

        return $grouped;
    }

    private function buildReportWithSubtotals(array $groupedData): array
    {
        $result = [];
        $grandTotal = 0;

        foreach ($groupedData as $customer => $items) {
            $subtotalQty = 0;
            $subtotalPrice = 0;
            $subtotalTotal = 0;

            foreach ($items as $row) {
                $result[] = [
                    'id'  => $row->id,
                    'tanggal' => $row->tanggal,
                    'customer' => $row->customer,
                    'product_id' => $row->product_id,
                    'product' => $row->product,
                    'qty' => $row->qty,
                    'price' => $row->price,
                    'total' => $row->total,
                    'is_summary' => false,
                ];

                $subtotalQty += $row->qty;
                $subtotalPrice += $row->price;
                $subtotalTotal += $row->total;
            }

            $result[] = [
                'id'  => null,
                'tanggal' => null,
                'customer' => null,
                'product_id' => null,
                'product' => 'SUBTOTAL',
                'qty' => $subtotalQty,
                'price' => $subtotalPrice,
                'total' => $subtotalTotal,
                'is_summary' => true,
            ];

            $grandTotal += $subtotalTotal;
        }

        $result[] = [
            'id'  => null,
            'tanggal' => null,
            'customer' => null,
            'product_id' => null,
            'product' => 'TOTAL',
            'qty' => null,
            'price' => null,
            'total' => $grandTotal,
            'is_summary' => true,
        ];

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    private function paginateReport(array $data, int $page, int $perPage, Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            collect($data)->forPage($page, $perPage),
            count($data),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }



}
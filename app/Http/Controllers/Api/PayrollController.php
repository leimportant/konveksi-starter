<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClosePayrollRequest;
use App\Services\PayrollClosingService;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Production;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\PayrollSummaryExport;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $start = Carbon::parse($request->start)->startOfDay();
        $end = Carbon::parse($request->end)->endOfDay();
        $search = $request->input('search');
        $user = Auth::user();
        $userId = $user->id;
        $employee_status = $user->employee_status ?? "staff";

        $query = Payroll::query()
            ->with('employee') // relasi ke pegawai
            ->with([
                'payrollDetails' => function ($q) {
                    // jika pakai relasi ke PayrollDetail model
                    $q->select('payroll_id', 'data');
                }
            ])
            ->whereBetween('created_at', [$start, $end])
            ->when($search, function ($q) use ($search) {
                $q->whereHas(
                    'employee',
                    fn($e) =>
                    $e->where('name', 'like', "%$search%")
                );
            });

             // ✅ Tambahkan filter employee_id hanya jika bukan owner
        if ($employee_status !== 'owner') {
            $query->where('employee_id', $userId);
        }

        $payrolls = $query->paginate(50);

        // decode JSON detail
        $payrolls->getCollection()->transform(function ($payroll) {
            if (isset($payroll->details)) {
                $payroll->details = $payroll->details->map(fn($d) => json_decode($d->data, true));
            }
            return $payroll;
        });

        return response()->json($payrolls);
    }

    public function preview(Request $request)
    {
        $start = Carbon::parse($request->start)->startOfDay();
        $end = Carbon::parse($request->end)->endOfDay();
        $search = $request->input('search');

        $mealAllow = DB::table('mst_meal_allowances')->pluck('amount', 'name');

        // Ambil semua data produksi + relasi items & activity_role
        $records = Production::with(['items', 'employee', 'activityRole', 'model'])
            ->whereBetween('tr_production.created_at', [$start, $end])
            ->when($search, function ($q) use ($search) {
                $q->whereHas(
                    'employee',
                    fn($e) =>
                    $e->where('name', 'like', "%$search%")
                );
            })
            ->where('tr_production.status', 1)
            ->get();

        // Group by employee
        $grouped = $records->groupBy('employee_id')->map(function ($rows, $employeeId) use ($mealAllow, $start, $end) {

            $employee = $rows->first()->employee;
            $price = $rows->first()->price_per_pcs;

            // Sum total qty
            $totalQty = $rows->sum(function ($r) {
                return $r->items->sum('qty');
            });

            $totalUpah = $totalQty * $price;

            // Absensi
            $absensi = DB::table('absensi')
                ->where('employee_id', $employeeId)
                ->whereBetween('absensi_date', [$start, $end])
                ->get();

            $uangMakan = $absensi->sum(function ($a) use ($mealAllow) {
                return $a->status_kehadiran == 'penuh'
                    ? ($mealAllow['full'] ?? 0)
                    : ($a->status_kehadiran == 'setengah'
                        ? ($mealAllow['half'] ?? 0)
                        : 0);
            });

            // Ambil kasbon karyawan
            $kasbon = DB::table('mutasi_kasbon')
                ->selectRaw("
                SUM(CASE WHEN type='Kasbon' THEN amount ELSE 0 END) AS total_kasbon,
                SUM(CASE WHEN type='Pembayaran' THEN amount ELSE 0 END) AS total_pembayaran
            ")
                ->where('employee_id', $employeeId)
                ->first();

            $sisaKasbon = ($kasbon->total_kasbon ?? 0) - ($kasbon->total_pembayaran ?? 0);

            // Ambil aturan potongan kasbon
            $rule = DB::table('mst_kasbon_potongan')
                ->where('amount', '<=', $sisaKasbon)
                ->orderByDesc('amount')
                ->first();

            $defaultPotongan = 10000;
            $potongan = $rule->potongan ?? ($sisaKasbon > 0 ? $defaultPotongan : 0);

            if ($sisaKasbon < $potongan)
                $potongan = $sisaKasbon; // jangan minus

            $totalGaji = $totalUpah + $uangMakan - $potongan;

            // Gabungkan semua items ke satu array
            $details = $rows->flatMap(function ($r) use ($price) {
                return $r->items->map(function ($d) use ($price, $r) {
                    return [
                        'activity_role' => $r->activityRole?->name,
                        'model_desc' => $r->model?->description,
                        'variant' => $d->variant,
                        'size_id' => $d->size_id,
                        'qty' => $d->qty,
                        'price' => $price,
                        'total' => $d->qty * $price,
                        'created_at' => $r->created_at,
                    ];
                });
            });

            return [
                'employee_id' => $employeeId,
                'phone_number' => $employee->phone_number,
                'employee_name' => $employee->name,
                'status' => 'open',
                'total_qty' => $totalQty,
                'price_per_pcs' => $price,
                'total_upah' => $totalUpah,
                'uang_makan' => $uangMakan,
                'lembur' => 0,
                'potongan' => $potongan,
                'total_gaji' => $totalGaji,
                'sisa_kasbon' => $sisaKasbon,
                'details' => $details->values(),
            ];
        })->values();

        return response()->json(['data' => $grouped]);
    }



    public function slip(Employee $employee)
    {
        $payrolls = Payroll::query()
            ->with('employee')
            ->where('employee_id', $employee->id)
            ->get();

        return Inertia::render('Payroll/Slip', [
            'employee' => $employee,
            'payrolls' => $payrolls
        ]);
    }

    public function closePayroll(Request $request, PayrollClosingService $service)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'employees' => 'required|array',
        ]);

        foreach ($request->employees as $emp) {
            $service->closePayroll(
                employeeId: $emp['employee_id'],
                periodStart: $request->period_start,
                periodEnd: $request->period_end,
                totalGaji: $emp['total_gaji'],
                uangMakan: $emp['uang_makan'],
                lembur: $emp['lembur'] ?? 0,
                potongan: $emp['potongan'] ?? 0,
            );
        }


        return response()->json([
            'success' => true,
            'message' => 'Bulk payroll closed'
        ]);
    }


    public function summary(Request $request)
    {
        $summary = Payroll::query()
            ->with('employee')
            ->selectRaw('employee_id, SUM(total_gaji) as total_gaji, SUM(uang_makan) as total_uang_makan, SUM(lembur) as total_lembur, SUM(potongan) as total_potongan, SUM(net_gaji) as total_net_gaji')
            ->when($request->input('period_start') && $request->input('period_end'), function ($query) use ($request) {
                $query->wherePeriod($request->input('period_start'), $request->input('period_end'));
            })
            ->groupBy('employee_id')
            ->paginate(10);

        return Inertia::render('Payroll/Summary', [
            'summary' => $summary,
            'filters' => $request->only(['period_start', 'period_end'])
        ]);
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new PayrollSummaryExport($request->all()), 'payroll-summary.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $summary = Payroll::query()
            ->with('employee')
            ->selectRaw('employee_id, SUM(total_gaji) as total_gaji, SUM(uang_makan) as total_uang_makan, SUM(lembur) as total_lembur, SUM(potongan) as total_potongan, SUM(net_gaji) as total_net_gaji')
            ->when($request->input('period_start') && $request->input('period_end'), function ($query) use ($request) {
                $query->wherePeriod($request->input('period_start'), $request->input('period_end'));
            })
            ->groupBy('employee_id')
            ->get();

        $pdf = Pdf::loadView('pdf.payroll_summary', ['summary' => $summary]);
        return $pdf->download('payroll-summary.pdf');
    }
}

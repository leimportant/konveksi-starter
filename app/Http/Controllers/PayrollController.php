<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClosePayrollRequest;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\PayrollSummaryExport;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $payrolls = Payroll::query()
            ->with('employee')
            ->when($request->input('period_start') && $request->input('period_end'), function ($query) use ($request) {
                $query->wherePeriod($request->input('period_start'), $request->input('period_end'));
            })
            ->latest()
            ->paginate(10);

        return Inertia::render('Payroll/Index', [
            'payrolls' => $payrolls,
            'filters' => $request->only(['period_start', 'period_end'])
        ]);
    }

    public function close(ClosePayrollRequest $request)
    {
        Payroll::whereIn('id', $request->validated()['payroll_ids'])
            ->where('status', 'open')
            ->update(['status' => 'closed']);

        return redirect()->back()->with('success', 'Payroll closed successfully.');
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

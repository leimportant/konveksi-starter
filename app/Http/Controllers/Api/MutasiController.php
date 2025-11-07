<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kasbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MutasiController extends Controller
{

    public function index(Request $request)
    {
        \Log::info('Mutation function hit', $request->all());
        Log::info("aa");

        $user = Auth::user();
        $userId = $user->id;
        $employee_status = $user->employee_status ?? "staff";

        $employeeId = $request->input('employee_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');
        $status = $request->input('status'); // ex: 2025-10
        $perPage = $request->input('per_page', 50);

        $query = DB::table('mutasi_kasbon as m')
            ->join('users as e', 'm.employee_id', '=', 'e.id')
            ->join('kasbon as k', 'm.kasbon_id', '=', 'k.id')
            ->select([
                'm.id',
                'm.kasbon_id',
                'e.name as employee_name',
                'm.amount',
                'm.type',
                'm.description',
                'm.saldo_kasbon',
                'k.status as kasbon_status',
                DB::raw('DATE(m.created_at) as tanggal'),
                'm.created_at'
            ])
            ->orderBy('m.created_at', 'desc');

        // ✅ Filter employee
        if ($$employee_status !== "owner") {
            $query->where('m.employee_id', $userId);
        }

        // ✅ Filter rentang tanggal
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('m.created_at', [$start, $end]);
        }

        // ✅ Filter status (jika berisi "YYYY-MM")
        if ($status && preg_match('/^\d{4}-\d{2}$/', $status)) {
            [$year, $month] = explode('-', $status);
            $query->whereYear('m.created_at', $year)
                ->whereMonth('m.created_at', $month);
        }

        // ✅ Filter search (nama / deskripsi)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('e.name', 'like', "%{$search}%")
                    ->orWhere('m.description', 'like', "%{$search}%");
            });
        }

        $mutasi = $query->paginate($perPage);
        return response()->json($mutasi);
    }

}

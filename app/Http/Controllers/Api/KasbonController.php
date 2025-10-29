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

class KasbonController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;
        $employee_status = $user->employee_status ?? "staff";

        $query = Kasbon::with('employee');

        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%')
                ->orWhere('employee_id', 'like', '%' . $request->search . '%')
                ->orWhere('amount', 'like', '%' . $request->search . '%');
        }

        if ($request->has('status')) {
            $query->where('status', 'like', '%' . $request->status . '%');
        }

        if ($employee_status !== "owner") {
            $query->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)
                    ->orWhere('employee_id', $userId);
            });
        }

        $perPage = $request->input('perPage', 50);
        $payment = $query->paginate($perPage);

        return response()->json($payment);

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'employee_id' => 'required|exists:users,id',
            'description' => 'required|string',
            'status' => ['required', Rule::in(['Pending', 'Approved', 'Rejected'])],
        ]);

        $validated['approved_by'] = null;
        $validated['approved_at'] = null;
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $payment = Kasbon::create($validated);
        return response()->json($payment, 201);
    }

    public function show(Kasbon $Kasbon)
    {
        return response()->json($Kasbon);
    }

    public function update(Request $request, Kasbon $Kasbon)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'employee_id' => 'required|exists:users,id',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['Pending', 'Approved', 'Rejected'])],
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $Kasbon->update($validated);
        return response()->json($Kasbon);
    }

    public function destroy(Kasbon $kasbon)
    {
        $kasbon->delete();
        return response()->json(null, 204);
    }

    public function approve(Kasbon $kasbon)
    {
        try {
            DB::beginTransaction();
            $kasbon->status = 'approved';
            $kasbon->approved_at = Carbon::now();
            $kasbon->approved_by = Auth::id();
            $kasbon->save();

            // Insert ke mutasi_kasbon
            DB::table('mutasi_kasbon')->insert([
                'kasbon_id' => $kasbon->id,
                'employee_id' => $kasbon->employee_id,
                'amount' => $kasbon->amount,
                'description' => $kasbon->description,
                'type' => 'Kasbon',
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json($kasbon, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve kasbon: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, $kasbon)
    {
        $kasbon->status = 'rejected';
        $kasbon->remark = $request->input('remark');
        $kasbon->rejected_at = Carbon::now();
        $kasbon->rejected_by = Auth::id();
        $kasbon->save();
        return response()->json($kasbon);
    }

    public function mutasi(Request $request)
    {

        Log::info("aa");
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
                'k.status as kasbon_status',
                DB::raw('DATE(m.created_at) as tanggal'),
                'm.created_at'
            ])
            ->orderBy('m.created_at', 'desc');

        // ✅ Filter employee
        if ($employeeId) {
            $query->where('m.employee_id', $employeeId);
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

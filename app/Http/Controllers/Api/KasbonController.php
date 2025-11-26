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
use Illuminate\Support\Str;

class KasbonController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;
        $employee_status = $user->employee_status ?? "staff";

        \Log::info($request->status);

        $query = Kasbon::with('employee');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                    ->orWhere('employee_id', 'like', '%' . $request->search . '%')
                    ->orWhere('amount', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->whereRaw('LOWER(status) = ?', [strtolower($request->status)]);
        }

        if ($employee_status !== "owner") {
            $query->where(function ($q) use ($userId) {
                $q->where('employee_id', $userId);
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

    public function destroy($kasbonId)
    {
        $kasbon = Kasbon::findOrFail($kasbonId);
        $kasbon->delete();

        return response()->json(null, 204);
    }


    public function approve($kasbonId)
    {
        try {
            DB::beginTransaction();

            $kasbon = Kasbon::with('employee')->findOrFail($kasbonId);

            // --- Pastikan employee_id ada ---
            $employeeId = $kasbon->employee_id ?? $kasbon->employee?->id;
            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kasbon belum memiliki employee terkait.'
                ], 400);
            }

            // --- Cek status sudah approved ---
            if (strtolower($kasbon->status) === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Kasbon sudah di-approve sebelumnya.'
                ], 400);
            }

            // --- Pastikan employee_id ada ---
            $employeeId = $kasbon->employee_id ?? $kasbon->employee?->id;
            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kasbon belum memiliki employee terkait.'
                ], 400);
            }

            // --- Update Kasbon ---
            $kasbon->status = 'Approved';
            $kasbon->approved_at = Carbon::now();
            $kasbon->approved_by = Auth::id();
            $kasbon->save();

            // Hitung total kasbon yang diajukan untuk employee
            $totalKasbon = DB::table('mutasi_kasbon')
                ->where('employee_id', $employeeId)
                ->where('type', 'Kasbon')
                ->sum('amount');

            // Hitung total pembayaran yang sudah dilakukan untuk employee
            $totalPembayaran = DB::table('mutasi_kasbon')
                ->where('employee_id', $employeeId)
                ->where('type', 'Pembayaran')
                ->sum('amount');

            // sisa kasbon = total kasbon - total pembayaran + kasbon baru
            $saldoKasbon = $totalKasbon - $totalPembayaran + $kasbon->amount;

            // --- Insert ke mutasi_kasbon ---
            DB::table('mutasi_kasbon')->insert([
                'id' => Str::uuid()->toString(),
                'kasbon_id' => $kasbon->id,
                'employee_id' => $employeeId,
                'amount' => $kasbon->amount,
                'description' => $kasbon->description,
                'saldo_kasbon' => $saldoKasbon,
                'type' => 'Kasbon',
                'created_at' => now()
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


    public function reject(Request $request, $kasbonId)
    {
        try {
            DB::beginTransaction();

            $kasbon = Kasbon::with('employee')->findOrFail($kasbonId);

            // --- Pastikan employee_id ada ---
            $employeeId = $kasbon->employee_id ?? $kasbon->employee?->id;
            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kasbon belum memiliki employee terkait.'
                ], 400);
            }

            // --- Cek status sudah approved ---
            if (strtolower($kasbon->status) === 'reject') {
                return response()->json([
                    'success' => false,
                    'message' => 'Kasbon sudah di-reject sebelumnya.'
                ], 400);
            }

            // --- Pastikan employee_id ada ---
            $employeeId = $kasbon->employee_id ?? $kasbon->employee?->id;
            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kasbon belum memiliki employee terkait.'
                ], 400);
            }

            // --- Update Kasbon ---
            $kasbon->status = 'Rejected';
            $kasbon->remark = $request->input('remark');
            $kasbon->rejected_at = Carbon::now();
            $kasbon->rejected_by = Auth::id();

            $kasbon->save();


            DB::commit();

            return response()->json($kasbon, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject kasbon: ' . $e->getMessage()
            ], 500);
        }
    }

    public function mutasi(Request $request)
    {
        \Log::info('Mutation function hit', $request->all());
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

    public function bayar(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|numeric',
            'amount' => 'required|numeric|min:1',
            'type' => ['required', Rule::in(['Pembayaran'])],
        ]);

        DB::beginTransaction();
        try {
            $employeeId = $validated['employee_id'];
            $paymentAmount = $validated['amount'];

            $kasbonId = DB::table('mutasi_kasbon')
                    ->where('employee_id', $employeeId)
                    ->where('type', 'Kasbon')
                    ->orderByDesc('created_at')
                    ->limit(1)
                    ->value('kasbon_id');


            // Calculate current saldo kasbon
            $totalKasbon = DB::table('mutasi_kasbon')
                ->where('employee_id', $employeeId)
                ->where('type', 'Kasbon')
                ->sum('amount');

            $totalPembayaran = DB::table('mutasi_kasbon')
                ->where('employee_id', $employeeId)
                ->where('type', 'Pembayaran')
                ->sum('amount');

            $currentSaldo = $totalKasbon - $totalPembayaran;
            $newSaldo = $currentSaldo - $paymentAmount; // Payment reduces saldo

            // Create new mutasi_kasbon entry for payment
            DB::table('mutasi_kasbon')->insert([
                'id' => Str::uuid()->toString(),
                'kasbon_id' => $kasbonId ?? 0, 
                'employee_id' => $employeeId,
                'amount' => $paymentAmount,
                'description' => 'Pembayaran kasbon',
                'saldo_kasbon' => $newSaldo,
                'type' => 'Pembayaran',
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json(['message' => 'Pembayaran kasbon berhasil.', 'saldo_terbaru' => $newSaldo], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error during kasbon payment: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal melakukan pembayaran kasbon.', 'error' => $e->getMessage()], 500);
        }
    }
}

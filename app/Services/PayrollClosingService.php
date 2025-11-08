<?php

namespace App\Services;

use App\Models\Payroll;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PayrollClosingService
{
    public function closePayroll(
        int $employeeId,
        int $activityRoleId,
        string $periodStart,
        string $periodEnd,
        float $totalGaji,
        float $uangMakan,
        float $lembur = 0,
        float $potongan = 0,
        array $details = []
    ) {
        DB::beginTransaction();


        try {
            $payroll = Payroll::where('employee_id', $employeeId)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->first();

            if (!$payroll) {
                $payroll = new Payroll([
                    'id' => Str::uuid()->toString(),
                    'employee_id' => $employeeId,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ]);
            }

            $payroll->activity_role_id = $activityRoleId ?? 0;
            $payroll->payroll_date = now();
            $payroll->total_upah = $totalGaji;
            $payroll->uang_makan = $uangMakan;
            $payroll->lembur = $lembur;
            $payroll->potongan = $potongan;
            $payroll->status = 'closed';
            $payroll->created_by = Auth::id();
            $payroll->save(); // ✅ pastikan disave dulu

            // Gunakan ID dari record yang benar-benar tersimpan
            $payrollId = $payroll->id;

            // Simpan detail payroll
            if (!empty($details)) {
                DB::table('payrolls_detail')->insert([
                    'payroll_id' => $payrollId,
                    'data' => json_encode($details, JSON_UNESCAPED_UNICODE),
                ]);
            }

            // Gunakan ID dari record yang benar-benar tersimpan
            $payrollId = $payroll->id;

            // 🧹 Hapus detail lama kalau ada
            DB::table('payrolls_detail')
                ->where('payroll_id', $payrollId)
                ->delete();

            // Simpan detail payroll
            if (!empty($details)) {
                DB::table('payrolls_detail')->insert([
                    'payroll_id' => $payrollId,
                    'data' => json_encode($details, JSON_UNESCAPED_UNICODE),
                ]);
            }
            // jika ada potongan
            // masukan kan ke table mutasi
            if ($potongan > 0) {
                $kasbonId = DB::table('mutasi_kasbon')
                    ->where('employee_id', $employeeId)
                    ->where('type', 'Kasbon')
                    ->whereBetween('created_at', [
                        Carbon::parse($periodStart)->startOfDay(),
                        Carbon::parse($periodEnd)->endOfDay()
                    ])
                    ->orderByDesc('created_at')
                    ->first()
                    ->kasbon_id;

                $totalKasbon = DB::table('mutasi_kasbon')
                    ->where('employee_id', $employeeId)
                    ->where('type', 'Kasbon')
                    ->sum('amount');

                $totalPembayaran = DB::table('mutasi_kasbon')
                    ->where('employee_id', $employeeId)
                    ->where('type', 'Pembayaran')
                    ->sum('amount');

                $sisaKasbon = $totalKasbon - $totalPembayaran;

                DB::table('mutasi_kasbon')->insert([
                    'id' => Str::uuid()->toString(),
                    'reference_no' => $payrollId,
                    'employee_id' => $employeeId,
                    'kasbon_id' => $kasbonId,
                    'amount' => $potongan,
                    'saldo_kasbon' => $sisaKasbon,
                    'description' => 'Potongan Kasbon',
                    'type' => 'Pembayaran',
                    'created_at' => now(),
                ]);
            }


            // Update tr_production status
            DB::table('tr_production')
                ->where('employee_id', $employeeId)
                ->whereBetween('created_at', [
                    Carbon::parse($periodStart)->startOfDay(),
                    Carbon::parse($periodEnd)->endOfDay()
                ])
                ->update(['status' => 2]);

            DB::commit(); // ✅ commit transaksi jika semua berhasil
        } catch (\Exception $e) {
            \Log::info($e);
            DB::rollback(); // ❌ rollback jika terjadi error
            throw $e;
        }
    }
}

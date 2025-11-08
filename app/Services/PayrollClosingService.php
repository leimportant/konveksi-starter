<?php

namespace App\Services;

use App\Models\Payroll;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
            $payrollId = Str::uuid()->toString();

            Payroll::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ],
                [
                    'id' => $payrollId,
                    'activity_role_id' => 0,
                    'payroll_date' => now(),
                    'total_upah' => $totalGaji,
                    'uang_makan' => $uangMakan,
                    'lembur' => $lembur,
                    'potongan' => $potongan,
                    'status' => 'closed',
                    'created_by' => Auth::id(),
                ]
            );

            // Simpan detail payroll sebagai JSON
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
                            ->whereBetween('created_at', [$periodStart, $periodEnd])
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
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->update(['status' => 2]);

            DB::commit(); // ✅ commit transaksi jika semua berhasil
        } catch (\Exception $e) {
            \Log::info($e);
            DB::rollback(); // ❌ rollback jika terjadi error
            throw $e;
        }
    }
}

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

            // 🔹 Simpan / update header payroll
            $payroll = Payroll::firstOrNew([
                'employee_id'  => $employeeId,
                'period_start' => $periodStart,
                'period_end'   => $periodEnd,
            ]);

            if (!$payroll->exists) {
                $payroll->id = Str::uuid()->toString();
            }

            $payroll->activity_role_id = $activityRoleId ?? 0;
            $payroll->payroll_date     = now();
            $payroll->total_upah       = $totalGaji;
            $payroll->uang_makan       = $uangMakan;
            $payroll->lembur           = $lembur;
            $payroll->potongan         = $potongan;
            $payroll->status           = 'closed';
            $payroll->created_by       = Auth::id();
            $payroll->save();

            $payrollId = $payroll->id;

            // 🔹 Simpan detail payroll (replace lama)
            DB::table('payrolls_detail')
                ->where('payroll_id', $payrollId)
                ->delete();

            if (!empty($details)) {
                DB::table('payrolls_detail')->insert([
                    'payroll_id' => $payrollId,
                    'data'       => json_encode($details, JSON_UNESCAPED_UNICODE),
                ]);
            }

            // 🔹 Jika ada potongan payroll → masukkan mutasi pembayaran kasbon
            if ($potongan > 0) {

                // Ambil kasbon_id terakhir untuk reference
                $kasbonId = DB::table('mutasi_kasbon')
                    ->where('employee_id', $employeeId)
                    ->where('type', 'Kasbon')
                    ->orderByDesc('created_at')
                    ->limit(1)
                    ->value('kasbon_id');

                // Masukkan mutasi tanpa saldo dulu (saldo dihitung ulang)
                $mutasiId = Str::uuid()->toString();
                DB::table('mutasi_kasbon')->insert([
                    'id'           => $mutasiId,
                    'reference_no' => $payrollId,
                    'employee_id'  => $employeeId,
                    'kasbon_id'    => $kasbonId,
                    'amount'       => $potongan,
                    'saldo_kasbon' => 0, // sementara
                    'description'  => 'Potongan Kasbon Payroll',
                    'type'         => 'Pembayaran',
                    'created_at'   => now(),
                ]);

                // 🔥 Recalculate saldo kasbon per employee (paling aman)
                DB::statement("
                    UPDATE mutasi_kasbon m
                    JOIN (
                        SELECT
                            id,
                            SUM(
                                CASE 
                                    WHEN type = 'Kasbon' THEN amount
                                    WHEN type = 'Pembayaran' THEN -amount
                                    ELSE 0
                                END
                            ) OVER (PARTITION BY employee_id ORDER BY created_at, id)
                            AS saldo_baru
                        FROM mutasi_kasbon
                        WHERE employee_id = $employeeId
                    ) x ON m.id = x.id
                    SET m.saldo_kasbon = x.saldo_baru
                ");
            }

            // 🔹 Update status produksi
            DB::table('tr_production')
                ->where('employee_id', $employeeId)
                ->whereBetween('created_at', [
                    Carbon::parse($periodStart)->startOfDay(),
                    Carbon::parse($periodEnd)->endOfDay()
                ])
                ->update(['status' => 2]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error($e);
            throw $e;
        }
    }
}

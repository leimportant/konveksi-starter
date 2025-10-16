<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayrollSummaryExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return Payroll::query()
            ->with('employee')
            ->selectRaw('employee_id, SUM(total_gaji) as total_gaji, SUM(uang_makan) as total_uang_makan, SUM(lembur) as total_lembur, SUM(potongan) as total_potongan, SUM(net_gaji) as total_net_gaji')
            ->when(isset($this->filters['period_start']) && isset($this->filters['period_end']), function ($query) {
                $query->wherePeriod($this->filters['period_start'], $this->filters['period_end']);
            })
            ->groupBy('employee_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Total Gaji',
            'Total Uang Makan',
            'Total Lembur',
            'Total Potongan',
            'Total Net Gaji',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee->name,
            $row->total_gaji,
            $row->total_uang_makan,
            $row->total_lembur,
            $row->total_potongan,
            $row->total_net_gaji,
        ];
    }
}

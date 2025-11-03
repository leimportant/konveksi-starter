<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'payrolls';

    // primary string
    protected $primaryKey = 'id';
    public $incrementing = false; // ✅ harus public
    protected $keyType = 'string';
    protected $dates = [
        'created_at',
        'updated_at',
        'payroll_date',
    ];


    protected $fillable = [
        'id',
        'payroll_date',
        'period_start',
        'period_end',
        'employee_id',
        'status',
        'total_gaji',
        'uang_makan',
        'lembur',
        'potongan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payroll_date' => 'date',
    ];

    public function employee()
    {
       return $this->belongsTo(User::class, foreignKey: 'employee_id');
    }

    public function payrollDetails()
    {
        return $this->hasMany(PayrollDetail::class, foreignKey: 'payroll_id');
    }

    public function scopeWherePeriod(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate])
            ->orWhereBetween('period_end', [$startDate, $endDate]);
    }

    protected static function booted()
    {
        static::updating(function ($payroll) {
            if ($payroll->isDirty('status') && $payroll->status === 'closed') {
                $payroll->payroll_date = now();
            }
        });

        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}

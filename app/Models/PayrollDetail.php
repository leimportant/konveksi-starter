<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'absensi_id',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function absensi()
    {
        return $this->belongsTo(Absensi::class);
    }
}

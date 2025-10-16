<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensi';

    protected $fillable = [
        'employee_id',
        'absensi_date',
        'jam_masuk',
        'jam_keluar',
        'status_kehadiran',
        'source',
        'remark',
        'jam_kerja',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

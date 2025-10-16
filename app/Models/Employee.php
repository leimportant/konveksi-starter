<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class);
    }
}

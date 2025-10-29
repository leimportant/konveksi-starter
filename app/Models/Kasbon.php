<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kasbon extends Model
{
     use HasFactory;

    protected $table = 'kasbon';
    protected $fillable = [
        'employee_id',
        'amount',
        'description',
        'status',
        'created_by',
        'updated_by',
        'approved_by',
        'rejected_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'approved_at',
        'rejected_at'
    ];

     protected $casts = [
        'amount',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealAllowances extends Model
{
    use HasFactory;

    protected $table = 'mst_meal_allowances';

    protected $fillable = [
        'name',
        'amount',
        'created_by',
        'updated_by',
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
}
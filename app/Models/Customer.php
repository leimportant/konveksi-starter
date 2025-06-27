<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Customer extends Model
{
    protected $table = 'mst_customer';
    protected $primaryKey = 'id';
    public $incrementing = false; // Use custom ID generation
    protected $fillable = [
        'id',
        'name',
        'address', 
        'phone_number',
        'saldo_kredit',
        'is_active',
        'created_by',
        'updated_by'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $lastCustomer = self::orderBy('id', 'desc')->first();
            $model->id = $lastCustomer ? $lastCustomer->id + 1 : 10001;
            $model->created_by = Auth::id();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }
}
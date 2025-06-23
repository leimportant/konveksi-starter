<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


class CustomerOrder extends Model
{
    protected $fillable = [
        'id',
        'customer_id',
        'total_amount',
        'status',
        'payment_method',
        'created_by',
        'updated_by'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate ID: YYYYMMDD00001
            $today = Carbon::now();
            $latestOrder = self::whereYear('created_at', $today->year)
                ->whereMonth('created_at', $today->month)
                ->whereDay('created_at', $today->day)
                ->orderBy('id', 'desc')
                ->first();

            if ($latestOrder) {
                $model->id = $latestOrder->id + 1;
            } else {
                $model->id = (int)($today->format('Ymd') . '00001');
            }

            $model->created_by = Auth::id();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(CustomerOrderItem::class, 'customer_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
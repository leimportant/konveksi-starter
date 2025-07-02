<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 't_orders';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'customer_id',
        'total_amount',
        'payment_method',
        'location_id',
        'payment_proof',
        'total_amount',
        'is_paid',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => \App\Enums\OrderStatusEnum::class,
    ];


    public static function generateUniqueShortId()
    {
        do {
            $numbers = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $letters = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(4));
            $id = $numbers . $letters;
        } while (self::where('id', $id)->exists());

        return $id;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }
}
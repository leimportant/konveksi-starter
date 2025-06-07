<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOrderItem extends Model
{
    protected $primaryKey = 'item_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'item_id',
        'customer_order_id',
        'product_id',
        'qty',
        'discount',
        'price',
        'price_final'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->item_id = uniqid('item_');
        });
    }

    public function order()
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
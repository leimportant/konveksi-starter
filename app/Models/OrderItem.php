<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 't_order_items';
    protected $primaryKey = 'item_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'item_id',
        'order_id',
        'product_id',
        'qty',
        'discount',
        'price',
        'price_final',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'discount' => 'decimal:2',
        'price' => 'decimal:2',
        'price_final' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
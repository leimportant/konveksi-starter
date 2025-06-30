<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{

    protected $table = 'cart_items';
    protected $fillable = [
        'product_id',
        'size_id',
        'uom_id',
        'quantity',
        'price',
        'discount',
        'price_sell',
        'price_sell_grosir',
        'price_grosir',
        'discount_grosir',
        'created_by',
        'updated_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
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
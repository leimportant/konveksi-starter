<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTransactionDetail extends Model
{
    use HasFactory;

    protected $table = 'pos_transaction_detail';

    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'subtotal'
    ];

    /**
     * Get the transaction that owns the detail
     */
    public function transaction()
    {
        return $this->belongsTo(PosTransaction::class, 'transaction_id');
    }

    /**
     * Get the product for this detail
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getProductNameAttribute()
    {
        return $this->product->name ?? null;
    }

    public function getNameAttribute()
    {
        return $this->getProductNameAttribute();
    }

    protected $appends = ['product_name'];


    /**
     * Calculate subtotal before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->subtotal = $model->quantity * $model->price;
        });
    }
}
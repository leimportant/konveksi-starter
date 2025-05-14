<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    use HasFactory;

    protected $table = 'mst_product_price';

    protected $fillable = [
        'product_id',
        'price_type_id',
        'price',
        'cost_price',
        'effective_date',
        'is_active'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_active' => 'boolean'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function priceType()
    {
        return $this->belongsTo(PriceType::class, 'price_type_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceType extends Model
{
    use SoftDeletes;

    protected $table = 'mst_product_price_type';

    protected $fillable = [
        'price_id',
        'price_type_id',
        'price',
        'qty',
        'uom_id',
        'size_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'qty' => 'decimal:2'
    ];

    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class, 'price_id');
    }

    public function priceType(): BelongsTo
    {
        return $this->belongsTo(PriceType::class, 'price_type_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class, 'size_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
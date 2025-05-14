<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosProduct extends Model
{
    use HasFactory;

    protected $table = 'pos_product';

    protected $fillable = [
        'name',
        'category_id',
        'uom_id',
        'remark',
        'created_by'
    ];

    /**
     * Get the category that owns the product
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the user who created the product
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the transaction details for this product
     */
    public function transactionDetails()
    {
        return $this->hasMany(PosTransactionDetail::class, 'product_id');
    }

    /**
     * Get the UOM (Unit of Measure) for this product
     */
    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }
}
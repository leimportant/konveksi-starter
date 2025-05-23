<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $table = 'tr_stock_opname';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id',
        'product_id',
        'location_id',
        'sloc_id',
        'uom_id',
        'remark',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function sloc()
    {
        return $this->belongsTo(Sloc::class, 'sloc_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class, 'opname_id');
    }
}
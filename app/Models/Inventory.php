<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tr_inventory';
    protected $primaryKey = ['product_id', 'location_id', 'uom_id', 'sloc_id', 'status'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'product_id',
        'location_id',
        'uom_id',
        'sloc_id',
        'size_id',
        'qty',
        'status',
        'qty_reserved',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function sloc()
    {
        return $this->belongsTo(Sloc::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
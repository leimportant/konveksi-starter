<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelMaterial extends Model
{
    use SoftDeletes;

    protected $table = 'tr_model_material';

    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'model_id',
        'product_id',
        'item',
        'remark',
        'price',
        'qty',
        'uom_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'qty' => 'integer',
        'item' => 'integer',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the model that owns the material.
     */
    public function model(): BelongsTo
    {
        return $this->belongsTo(ModelRef::class, 'model_id');
    }

    /**
     * Get the product associated with the material.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the UOM associated with the material.
     */
    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }

    /**
     * Get the user who created the record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the record.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
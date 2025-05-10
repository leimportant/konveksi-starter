<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodReceiveItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tr_good_receive_item';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'good_receive_id',
        'model_material_id',
        'model_material_item',
        'qty',
        'qty_convert',
        'uom_base',
        'uom_convert',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'qty' => 'decimal:2',
        'qty_convert' => 'decimal:2',
    ];

    /**
     * Get the good receive that owns the item.
     */
    public function goodReceive(): BelongsTo
    {
        return $this->belongsTo(GoodReceive::class, 'good_receive_id'); // Assuming GoodReceive model exists
    }

    /**
     * Get the model material associated with the good receive item.
     * This item belongs to a ModelMaterial identified by a composite key:
     * - GoodReceiveItem.model_material_id maps to ModelMaterial.product_id
     * - GoodReceiveItem.model_material_item maps to ModelMaterial.item
     */
    public function modelMaterial(): BelongsTo
    {
        // Base relationship: local 'model_material_id' (on GoodReceiveItem) references 'product_id' (on ModelMaterial)
        return $this->belongsTo(ModelMaterial::class, 'model_material_id', 'product_id')
                    // Additional condition: ModelMaterial.item must match the current GoodReceiveItem's model_material_item
                    ->where(ModelMaterial::getModel()->getTable() . '.item', $this->model_material_item);
    }

    // If model_material_item also refers to a distinct part of ModelMaterial or another model,
    // you might need another relationship or adjust the one above.
    // For example, if tr_model_material has a primary key 'id' and 'item' is a separate attribute:
    // public function modelMaterialDetail(): BelongsTo
    // {
    //    return $this->belongsTo(ModelMaterial::class, 'model_material_item', 'item');
    // }

    public function model()
    {
        return $this->belongsTo(ModelRef::class, 'model_id');
    }

    public function baseUom()
    {
        return $this->belongsTo(Uom::class, 'uom_base');
    }

    public function convertUom()
    {
        return $this->belongsTo(Uom::class, 'uom_convert');
    }

    /**
     * Get the user who created the record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the record.
     */
    public function destroyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
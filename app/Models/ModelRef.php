<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelRef extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'tr_model';

    protected $fillable = [
        'description',
        'remark',
        'estimation_price_pcs',
        'estimation_qty',
        'start_date',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'estimation_price_pcs' => 'decimal:2',
        'estimation_qty' => 'integer',
        'start_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function sizes()
    {
        return $this->hasMany(ModelSize::class, 'model_id');
    }

    public function activities()
    {
        return $this->hasMany(ModelActivity::class, 'model_id');
    }

    public function modelMaterial()
    {
        return $this->hasMany(ModelMaterial::class, 'model_id');
    }

}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Production extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tr_production';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'model_id',
        'employee_id',
        'activity_role_id',
        'remark',
        'qty',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'model_id' => 'integer',
        'activity_role_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    // Relationships
    public function model()
    {
        return $this->belongsTo(ModelRef::class, 'model_id');
    }

    public function activityRole()
    {
        return $this->belongsTo(ActivityRole::class, 'activity_role_id');
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

    public function items()
    {
        return $this->hasMany(ProductionItem::class, 'production_id');
    }
}
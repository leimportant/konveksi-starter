<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tr_model_activity';

    protected $fillable = [
        'model_id',
        'role_id',
        'price',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'model_id' => 'integer',
        'role_id' => 'integer',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function model()
    {
        return $this->belongsTo(ModelRef::class, 'model_id');
    }

    public function role()
    {
        return $this->belongsTo(ActivityRole::class, 'role_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelSize extends Model
{
    use HasFactory, SoftDeletes;
    protected $primaryKey = ['model_id', 'size_id', 'variant'];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'tr_model_size';

    protected $fillable = [
        'model_id',
        'size_id',
        'variant',
        'qty',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'model_id' => 'integer',
        'size_id' => 'string',
        'variant'  => 'string',
        'qty' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function model()
    {
        return $this->belongsTo(ModelRef::class, 'model_id');
    }

    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id');
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
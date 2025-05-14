<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tr_production_item';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'production_id',
        'size_id',
        'qty',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'size_id' => 'string',
        'qty' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer'
    ];

    // Relationships
    public function production()
    {
        return $this->belongsTo(Production::class, 'production_id');
    }

    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id');
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
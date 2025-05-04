<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reference extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_reference';

    protected $fillable = [
        'ref_type_id',
        'name',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Get the reference type that owns this reference
     */
    public function refType(): BelongsTo
    {
        return $this->belongsTo(RefType::class, 'ref_type_id');
    }
}
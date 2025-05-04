<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_ref_type';

    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Get all references for this type
     */
    public function references(): HasMany
    {
        return $this->hasMany(Reference::class, 'ref_type_id');
    }
}
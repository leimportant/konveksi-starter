<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Uom extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mst_uom';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'created_by',
        'updated_by'
    ];

    /**
     * Get the good receives that use this UOM as base.
     */
    // public function goodReceivesBase()
    // {
    //     return $this->hasMany(GoodReceive::class, 'uom_base');
    // }

    /**
     * Get the good receives that use this UOM as convert.
     */
    // public function goodReceivesConvert()
    // {
    //     return $this->hasMany(GoodReceive::class, 'uom_convert');
    // }
}
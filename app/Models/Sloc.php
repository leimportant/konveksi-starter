<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sloc extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_sloc';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
}
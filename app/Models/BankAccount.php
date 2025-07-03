<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mst_bank_account';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'id',
        'name',
        'account_number',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

}
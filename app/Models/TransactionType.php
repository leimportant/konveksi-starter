<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_transaction_type';

    protected $fillable = [
        'name'
    ];

    /**
     * Get the approval configs for the transaction type
     */
    public function approvalConfigs()
    {
        return $this->hasMany(ConfigApproval::class, 'transaction_type');
    }
}
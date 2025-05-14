<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigApproval extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'config_approval';

    protected $fillable = [
        // 'transaction_type',
        'username',
        'level',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    /**
     * Get the transaction type that owns the approval config
     */
    // public function transactionType()
    // {
    //     return $this->belongsTo(TransactionType::class, 'transaction_type');
    // }

    /**
     * Get the user who created the approval config
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated the approval config
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the approval config
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
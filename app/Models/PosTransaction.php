<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosTransaction extends Model
{
    protected $table = 'pos_transaction';
    protected $primaryKey = 'id';
    public $incrementing = false; // karena ID-nya VARCHAR, bukan auto-increment
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'transaction_number',
        'customer_id',
        'transaction_date',
        'total_amount',
        'paid_amount',
        'change_amount',
        'payment_method',
        'status',
        'notes',
        'created_by',
    ];

    public function orderItems()
    {
        return $this->hasMany(PosTransactionDetail::class, 'transaction_id');
    }
    
}

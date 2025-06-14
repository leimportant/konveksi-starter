<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashBalance extends Model
{
    protected $table = 'tr_cash_balance'; // Specify the table name

    protected $fillable = [
        'cashier_id',
        'shift_date',
        'shift_number',
        'opening_balance',
        'cash_sales_total',
        'cash_in',
        'cash_out',
        'closing_balance',
        'discrepancy',
        'notes',
        'opened_at',
        'closed_at',
        'status',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
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
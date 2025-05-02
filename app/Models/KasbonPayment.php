<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasbonPayment extends Model
{
    protected $fillable = [
        'payment_number',
        'amount',
        'description',
        'status',
    ];
}

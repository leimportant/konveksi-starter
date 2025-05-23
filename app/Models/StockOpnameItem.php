<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $table = 'tr_stock_opname_item';

    protected $fillable = [
        'opname_id',
        'size_id',
        'qty_system',
        'qty_physical',
        'difference',
        'note',
        'created_by',
        'updated_by',
    ];
}
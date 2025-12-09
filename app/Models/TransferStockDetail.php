<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferStockDetail extends Model
{
    protected $table = 'tr_transfer_stock_detail';

    protected $fillable = [
        'transfer_id',
        'product_id',
        'uom_id',
        'size_id',
        'variant',
        'qty',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'uom_id' => 'string',
        'size_id' => 'string',
        'variant' => 'string',
        'qty' => 'decimal:2',
    ];


    public function transfer()
    {
        return $this->belongsTo(TransferStock::class, 'transfer_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
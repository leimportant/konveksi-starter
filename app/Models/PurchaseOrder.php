<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_order';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'purchase_date',
        "supplier",
        'nota_number',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'datetime',
    ];

     public static function generateUniqueShortId()
    {
        do {
            $numbers = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $letters = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(4));
            $id = $numbers . $letters;
        } while (self::where('id', $id)->exists());

        return $id;
    }
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = self::generateUniqueShortId();
        });
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
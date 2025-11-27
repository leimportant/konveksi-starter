<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnlistedProduct extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';

    protected $table = 'mst_product_unlisted';

    protected $fillable = [
        'id',
        'category_id',
        'uom_id',
        'name',
        'descriptions',
        'variant',
        'size_id',
        'price_store',
        'price_grosir',
        'image_path',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}

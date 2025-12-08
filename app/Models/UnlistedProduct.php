<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnlistedProduct extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';

    protected $table = 'mst_product';

    protected $fillable = [
        'id',
        'unlisted',
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

    public static function generateId(): string
    {
        // Find the latest ID that starts with '999'
        $latestProduct = static::where('id', 'like', '999%')
            ->orderBy('id', 'desc')
            ->first();

        $newNumber = 1;
        if ($latestProduct) {
            $latestId = $latestProduct->id;
            // Extract the numeric part after '999'
            $numericPart = (int) substr($latestId, 3); // Assuming '999' is always 3 characters
            $newNumber = $numericPart + 1;
        }

        // Format the new ID as 999 followed by a running number, padded with zeros if necessary
        // For example, 999001, 999002, ..., 999010, etc.
        return '999' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}

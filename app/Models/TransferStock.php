<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferStock extends Model
{
    use SoftDeletes;

    protected $table = 'tr_transfer_stock';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'location_id',
        'location_destination_id',
        'sloc_id',
        "status",
        'transfer_date',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    public function transfer_detail()
    {
        return $this->hasMany(TransferStockDetail::class, 'transfer_id', 'id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    public function location_destination()
    {
        return $this->belongsTo(Location::class, 'location_destination_id', 'id');
    }

    public function sloc()
    {
        return $this->belongsTo(Sloc::class, 'sloc_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
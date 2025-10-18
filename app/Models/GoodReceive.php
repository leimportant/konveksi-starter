<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodReceive extends Model
{
    use HasFactory;

    protected $table = 'tr_good_receive';

    protected $fillable = [
        'date',
        'model_id',
        'recipent',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    public function items()
    {
        return $this->hasMany(GoodReceiveItem::class, 'good_receive_id');
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

    public function model()
    {
        return $this->belongsTo(ModelRef::class, 'model_id');
    }
}
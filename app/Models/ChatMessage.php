<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ChatMessage extends Model
{
    use HasFactory;

    // UUID sebagai primary key
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'chat_messages';

    protected $fillable = [
        'id',
        'order_id',
        'sender_type',
        'sender_id',
        'receiver_id',
        'message',
        'is_read',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Optional relationships:
     * 
     * You can uncomment and customize these if needed.
     */

    // public function order()
    // {
    //     return $this->belongsTo(Order::class, 'order_id');
    // }

    // public function sender()
    // {
    //     return $this->belongsTo(User::class, 'sender_id');
    // }

    // public function receiver()
    // {
    //     return $this->belongsTo(User::class, 'receiver_id');
    // }
}

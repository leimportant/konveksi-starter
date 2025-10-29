<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatLog extends Model
{
    use HasFactory;

    protected $table = 'chat_logs';

    protected $fillable = [
        'transaction_id',
        'chat_id',
        'role',
        'question',
        'content',
        'rating',
        'is_saved',
        'is_important',
        'escalated_at',
    ];

    protected $casts = [
        'is_saved' => 'boolean',
        'is_important' => 'boolean',
        'escalated_at' => 'datetime',
    ];
}

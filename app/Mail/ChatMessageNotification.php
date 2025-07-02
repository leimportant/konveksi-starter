<?php

namespace App\Mail;

use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChatMessageNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $chatMessage;

    public function __construct(ChatMessage $chatMessage)
    {
        $this->chatMessage = $chatMessage;
    }

    public function build()
    {
        return $this->subject('Pesan Baru dari ' . ucfirst($this->chatMessage->sender_type))
            ->view('emails.chat_notification');
    }
}

<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatMessage;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ChatMessage $chatMessage)
    {
        $this->chatMessage = $chatMessage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Broadcast to a private channel for the recipient and sender
        return [
            new Channel('chat.' . $this->chatMessage->order_id),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'message.sent';
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Load the sender relationship if not already loaded
        if (!$this->chatMessage->relationLoaded('sender')) {
            $this->chatMessage->load('sender');
        }
        
        // Log the broadcast data for debugging
        \Illuminate\Support\Facades\Log::info('Broadcasting chat message', [
            'channel' => 'chat.' . $this->chatMessage->order_id,
            'event' => 'message.sent',
            'message_id' => $this->chatMessage->id,
            'order_id' => $this->chatMessage->order_id
        ]);
        
        return ['chatMessage' => $this->chatMessage];
    }
}
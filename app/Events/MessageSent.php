<?php

namespace App\Events;

use App\Models\Message;
use App\Models\ChatRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $chatRoom;

    public function __construct(Message $message, ChatRoom $chatRoom)
    {
        $this->message = $message->load('sender');
        $this->chatRoom = $chatRoom;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('chat-room.' . $this->chatRoom->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'message_type' => $this->message->message_type,
                'reply_to_message_id' => $this->message->reply_to_message_id,
                'is_edited' => $this->message->is_edited,
                'created_at' => $this->message->created_at->toISOString(),
                'sender' => [
                    'id' => $this->message->sender->id,
                    'name' => $this->message->sender->name ?? 'Unknown User',
                    'avatar_url' => $this->message->sender->avatar_url ?? null,
                ]
            ],
            'chat_room_id' => $this->chatRoom->id,
            'timestamp' => now()->toISOString()
        ];
    }
}
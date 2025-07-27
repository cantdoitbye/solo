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
    public $currentUserId;

    public function __construct(Message $message, ChatRoom $chatRoom, int $currentUserId)
    {
        $this->message = $message->load('sender');
        $this->chatRoom = $chatRoom->load('event');
        $this->currentUserId = $currentUserId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
                \Log::info('Broadcasting on channel: chat-room.' . $this->chatRoom->id);

        return [
            // new PresenceChannel('chat-room.' . $this->chatRoom->id),
                    new Channel('chat-room-' . $this->chatRoom->id),

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

       \Log::info('Broadcasting on channel: chat-room.' . $this->chatRoom->id);

        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'message_type' => $this->message->message_type,
                'reply_to_message_id' => $this->message->reply_to_message_id,
                'is_edited' => $this->message->is_edited,
                'sender_id' => $this->message->sender->id,
                'created_at' => $this->message->created_at->toISOString(),
                'sender' => [
                    'id' => $this->message->sender->id,
                    'name' => $this->message->sender->name ?? 'Unknown User',
                    'avatar_url' => $this->message->sender->profile_photo ?? null,
                ]
            ],
             'chat_room' => [
                'id' => $this->chatRoom->id,
                'name' => $this->chatRoom->name,
                'type' => $this->chatRoom->type,
                'event_name' => $this->chatRoom->event?->name,
            ],
             'chat_room_id' => $this->chatRoom->id,
            'group_name' => $this->chatRoom->name,
            // 'chat_room_id' => $this->chatRoom->id,
            'timestamp' => now()->toISOString()
        ];
    }
}
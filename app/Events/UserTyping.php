<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $chatRoomId;
    public $isTyping;

    public function __construct(User $user, int $chatRoomId, bool $isTyping = true)
    {
        $this->user = $user;
        $this->chatRoomId = $chatRoomId;
        $this->isTyping = $isTyping;
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('chat-room.' . $this->chatRoomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name ?? 'Unknown User',
            ],
            'chat_room_id' => $this->chatRoomId,
            'is_typing' => $this->isTyping,
            'timestamp' => now()->toISOString()
        ];
    }
}
<?php

namespace App\Events;

use App\Models\User;
use App\Models\ChatRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserJoinedChat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $chatRoom;

    public function __construct(User $user, ChatRoom $chatRoom)
    {
        $this->user = $user;
        $this->chatRoom = $chatRoom;
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('chat-room.' . $this->chatRoom->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name ?? 'Unknown User',
                'avatar_url' => $this->user->avatar_url ?? null,
            ],
            'chat_room_id' => $this->chatRoom->id,
            'message' => $this->user->name . ' joined the chat',
            'timestamp' => now()->toISOString()
        ];
    }
}
<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ChatRoom;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Chat room presence channel
Broadcast::channel('chat-room.{chatRoomId}', function ($user, $chatRoomId) {
    $chatRoom = ChatRoom::find($chatRoomId);
    
    if (!$chatRoom) {
        return false;
    }

    // Check if user is a member of this chat room
    $isMember = $chatRoom->members()
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->exists();

    if ($isMember) {
        return [
            'id' => $user->id,
            'name' => $user->name ?? 'Unknown User',
            'avatar_url' => $user->avatar_url ?? null,
        ];
    }

    return false;
});
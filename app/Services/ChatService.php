<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Events\UserJoinedChat;
use App\Events\UserTyping;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChatService
{
    /**
     * Create event group chat when event is published
     */
   /**
     * Create event group chat when event is published
     */
    public function createEventGroupChat(Event $event): ChatRoom
    {
        return DB::transaction(function () use ($event) {
            // Create chat room
            $chatRoom = ChatRoom::create([
                'name' => $event->name,
                'type' => ChatRoom::TYPE_EVENT_GROUP,
                'event_id' => $event->id,
                'created_by' => $event->host_id,
                'is_active' => true
            ]);

            // Add host as admin member
            $chatRoom->users()->attach($event->host_id, [
                'role' => 'admin',
                'is_active' => true,
                'joined_at' => now()
            ]);

            // Send welcome system message
            $welcomeMessage = $this->sendSystemMessage($chatRoom, "Welcome to {$event->name} group chat!");

            // Broadcast welcome message
            broadcast(new MessageSent($welcomeMessage, $chatRoom))->toOthers();

            return $chatRoom;
        });
    }

    /**
     * Add user to event group chat when they join event
     */
    public function addUserToEventChat(int $eventId, int $userId): void
    {
        $chatRoom = ChatRoom::where('event_id', $eventId)
                           ->where('type', ChatRoom::TYPE_EVENT_GROUP)
                           ->first();

        if ($chatRoom && !$chatRoom->members()->where('user_id', $userId)->exists()) {
            $user = User::find($userId);
            
            $chatRoom->members()->attach($userId, [
                'role' => 'member',
                'is_active' => true,
                'joined_at' => now()
            ]);

            // Send system message about new member
            $joinMessage = $this->sendSystemMessage($chatRoom, "{$user->name} joined the group!");

            // Broadcast user joined event
            broadcast(new UserJoinedChat($user, $chatRoom));
            broadcast(new MessageSent($joinMessage, $chatRoom))->toOthers();
        }
    }

    /**
     * Create or get personal chat between two users
     */
    public function createPersonalChat(int $user1Id, int $user2Id): ChatRoom
    {
        // Check if chat already exists
        $existingChat = ChatRoom::where('type', ChatRoom::TYPE_PERSONAL)
            ->whereHas('members', function ($q) use ($user1Id) {
                $q->where('user_id', $user1Id);
            })
            ->whereHas('members', function ($q) use ($user2Id) {
                $q->where('user_id', $user2Id);
            })
            ->first();

        if ($existingChat) {
            return $existingChat;
        }

        return DB::transaction(function () use ($user1Id, $user2Id) {
            $user1 = User::find($user1Id);
            $user2 = User::find($user2Id);

            $chatRoom = ChatRoom::create([
                'name' => "{$user1->name}, {$user2->name}",
                'type' => ChatRoom::TYPE_PERSONAL,
                'created_by' => $user1Id,
                'is_active' => true
            ]);

            // Add both users as members
            $chatRoom->members()->attach([
                $user1Id => ['role' => 'member', 'is_active' => true, 'joined_at' => now()],
                $user2Id => ['role' => 'member', 'is_active' => true, 'joined_at' => now()]
            ]);

            return $chatRoom;
        });
    }

    /**
     * Send a message with real-time broadcasting
     */
    public function sendMessage(
        int $chatRoomId, 
        int $senderId, 
        string $content, 
        string $type = Message::TYPE_TEXT,
        array $fileData = [],
        int $replyToMessageId = null
    ): Message {
        $message = Message::create([
            'chat_room_id' => $chatRoomId,
            'sender_id' => $senderId,
            'message_type' => $type,
            'content' => $content,
            'file_url' => $fileData['file_url'] ?? null,
            'file_name' => $fileData['file_name'] ?? null,
            'file_size' => $fileData['file_size'] ?? null,
            'reply_to_message_id' => $replyToMessageId
        ]);

        // Update chat room's last message time
        $chatRoom = ChatRoom::find($chatRoomId);
        $chatRoom->update(['last_message_at' => now()]);

        // Load sender relationship for broadcasting
        $message->load('sender', 'replyTo.sender');

        // Broadcast message to all chat room members except sender
        broadcast(new MessageSent($message, $chatRoom,$senderId))->toOthers();

        return $message;
    }

    /**
     * Handle user typing indicator
     */
    public function handleUserTyping(int $chatRoomId, int $userId, bool $isTyping = true): void
    {
        $user = User::find($userId);
        
        // Broadcast typing status to other users in the chat room
        broadcast(new UserTyping($user, $chatRoomId, $isTyping))->toOthers();
    }

    /**
     * Mark user as online in chat room
     */
    public function markUserOnline(int $chatRoomId, int $userId): void
    {
        DB::table('chat_room_members')
            ->where('chat_room_id', $chatRoomId)
            ->where('user_id', $userId)
            ->update([
                'last_seen_at' => now(),
                'is_online' => true
            ]);
    }

    /**
     * Mark user as offline in chat room
     */
    public function markUserOffline(int $chatRoomId, int $userId): void
    {
        DB::table('chat_room_members')
            ->where('chat_room_id', $chatRoomId)
            ->where('user_id', $userId)
            ->update([
                'last_seen_at' => now(),
                'is_online' => false
            ]);
    }

    /**
     * Get online users in chat room
     */
    public function getOnlineUsers(int $chatRoomId): array
    {
        $onlineUsers = DB::table('chat_room_members')
            ->join('users', 'chat_room_members.user_id', '=', 'users.id')
            ->where('chat_room_members.chat_room_id', $chatRoomId)
            ->where('chat_room_members.is_online', true)
            ->where('chat_room_members.is_active', true)
            ->select('users.id', 'users.name', 'users.avatar_url', 'chat_room_members.last_seen_at')
            ->get();

        return $onlineUsers->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name ?? 'Unknown User',
                'avatar_url' => $user->avatar_url,
                'last_seen_at' => $user->last_seen_at
            ];
        })->toArray();
    }

    /**
     * Get user's chat rooms
     */
    public function getUserChatRooms(int $userId): array
    {
        $chatRooms = ChatRoom::forUser($userId)
            ->with(['latestMessage.sender', 'event', 'members'])
            ->orderBy('last_message_at', 'desc')
            ->get();

        return $chatRooms->map(function ($room) use ($userId) {
            $latestMessage = $room->latestMessage;
            $unreadCount = $this->getUnreadCount($room->id, $userId);

            return [
                'id' => $room->id,
                'name' => $room->name,
                'type' => $room->type,
                'event_id' => $room->event_id,
                'members_count' => $room->activeMembers->count(),
                'latest_message' => $latestMessage ? [
                    'content' => $latestMessage->content,
                    'sender_name' => $latestMessage->sender->name ?? 'Unknown',
                    'sent_at' => $latestMessage->created_at->toISOString(),
                    'type' => $latestMessage->message_type
                ] : null,
                'unread_count' => $unreadCount,
                'last_activity' => $room->last_message_at?->toISOString()
            ];
        })->toArray();
    }

    /**
     * Get messages for a chat room
     */
 public function getChatMessages(int $chatRoomId, int $userId, int $page = 1, int $perPage = 50): array
{
    $messages = Message::where('chat_room_id', $chatRoomId)
        ->with([
            'sender:id,name,profile_photo', 
            'replyTo:id,content,message_type,sender_id', 
            'replyTo.sender:id,name'
        ])      
        ->select([
            'id',
            'sender_id', 
            'message_type', 
            'content', 
            'reply_to_message_id',
            'is_edited',
            'created_at'
        ])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

    // Mark messages as read
    $this->markMessagesAsRead($chatRoomId, $userId);

    // Convert items to collection and then map
    $formattedMessages = collect($messages->items())->map(function ($message) use ($userId) {
        $formatted = [
            'id' => $message->id,
            'content' => $message->content,
            'message_type' => $message->message_type,
            'is_edited' => $message->is_edited,
            'isSender' => $message->sender_id === $userId,
            'created_at' => $message->created_at->toISOString(),
            'sender' => [
                'id' => $message->sender->id,
                'name' => $message->sender->name ?? 'Unknown User',
                'profile_photo' => $message->sender->profile_photo ?? null,
            ]
        ];

        // Add reply information if it exists
        if ($message->reply_to_message_id && $message->replyTo) {
            $formatted['reply_to'] = [
                'id' => $message->replyTo->id,
                'content' => $message->replyTo->content,
                'message_type' => $message->replyTo->message_type,
                'sender' => [
                    'id' => $message->replyTo->sender->id,
                    'name' => $message->replyTo->sender->name ?? 'Unknown User',
                ]
            ];
        }

        return $formatted;
    })->values()->toArray();

    return [
        'messages' => $formattedMessages,
        'pagination' => [
            'current_page' => $messages->currentPage(),
            'per_page' => $messages->perPage(),
            'total_pages' => $messages->lastPage(),
            'total_messages' => $messages->total()
        ]
    ];
}

  

   

 


       private function sendSystemMessage(ChatRoom $chatRoom, string $message): Message
    {
        return Message::create([
            'chat_room_id' => $chatRoom->id,
            'sender_id' => $chatRoom->created_by,
            'message_type' => Message::TYPE_SYSTEM,
            'content' => $message
        ]);
    }

    private function getUnreadCount(int $chatRoomId, int $userId): int
    {
        $lastReadAt = DB::table('chat_room_members')
            ->where('chat_room_id', $chatRoomId)
            ->where('user_id', $userId)
            ->value('last_read_at');

        return Message::where('chat_room_id', $chatRoomId)
            ->where('sender_id', '!=', $userId)
            ->when($lastReadAt, function ($q) use ($lastReadAt) {
                $q->where('created_at', '>', $lastReadAt);
            })
            ->count();
    }

    private function markMessagesAsRead(int $chatRoomId, int $userId): void
    {
        DB::table('chat_room_members')
            ->where('chat_room_id', $chatRoomId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }
}
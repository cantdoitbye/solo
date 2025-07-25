<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Get user's chat rooms
     */
    public function getChatRooms(Request $request): JsonResponse
    {
        try {
            $chatRooms = $this->chatService->getUserChatRooms($request->user()->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'personal_chats' => array_filter($chatRooms, fn($room) => $room['type'] === 'personal'),
                    'group_chats' => array_filter($chatRooms, fn($room) => $room['type'] === 'event_group'),
                    'total_chats' => count($chatRooms)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get messages for a chat room
     */
    public function getChatMessages(Request $request, int $chatRoomId): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $messages = $this->chatService->getChatMessages(
                $chatRoomId,
                $request->user()->id,
                $request->input('page', 1),
                $request->input('per_page', 50)
            );

            // Mark user as online when they view messages
            $this->chatService->markUserOnline($chatRoomId, $request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a message with real-time broadcasting
     */
    public function sendMessage(Request $request, int $chatRoomId): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:2000',
            'type' => 'nullable|in:text,image,file',
            'file' => 'nullable|file|max:10240', // 10MB max
            'reply_to_message_id' => 'nullable|integer|exists:messages,id'
        ]);

        try {
            $fileData = [];
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('chat-files', 'public');
                $fileData = [
                    'file_url' => Storage::url($path),
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize()
                ];
            }

            $message = $this->chatService->sendMessage(
                $chatRoomId,
                $request->user()->id,
                $request->input('content'),
                $request->input('type', 'text'),
                $fileData,
                $request->input('reply_to_message_id')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'message_id' => $message->id,
                    'sent_at' => $message->created_at->toISOString(),
                    'broadcasted' => true
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle typing indicator
     */
    public function handleTyping(Request $request, int $chatRoomId): JsonResponse
    {
        $request->validate([
            'is_typing' => 'required|boolean'
        ]);

        try {
            $this->chatService->handleUserTyping(
                $chatRoomId,
                $request->user()->id,
                $request->input('is_typing')
            );

            return response()->json([
                'success' => true,
                'message' => 'Typing status updated'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get online users in chat room
     */
    public function getOnlineUsers(Request $request, int $chatRoomId): JsonResponse
    {
        try {
            $onlineUsers = $this->chatService->getOnlineUsers($chatRoomId);

            return response()->json([
                'success' => true,
                'data' => [
                    'online_users' => $onlineUsers,
                    'total_online' => count($onlineUsers)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create personal chat
     */
    public function createPersonalChat(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        try {
            $chatRoom = $this->chatService->createPersonalChat(
                $request->user()->id,
                $request->input('user_id')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'chat_room_id' => $chatRoom->id,
                    'name' => $chatRoom->name,
                    'type' => $chatRoom->type
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark user as offline when leaving chat
     */
    public function leaveChat(Request $request, int $chatRoomId): JsonResponse
    {
        try {
            $this->chatService->markUserOffline($chatRoomId, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'User marked as offline'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


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

               $personalChats = array_filter($chatRooms, fn($room) => $room['type'] === 'personal');
        $groupChats = array_filter($chatRooms, fn($room) => $room['type'] === 'event_group');

            return response()->json([
                'success' => true,
                'data' => [
                    // 'personal_chats' => array_filter($chatRooms, fn($room) => $room['type'] === 'personal'),
                    // 'group_chats' => array_filter($chatRooms, fn($room) => $room['type'] === 'event_group'),
                    // 'total_chats' => count($chatRooms)

                      'personal_chats' => array_values($personalChats), // Re-index array
                'group_chats' => array_values($groupChats), // Re-index array
                'total_personal_chats' => count($personalChats),
                'total_group_chats' => count($groupChats),
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
            'content' => 'nullable|string|max:2000',
            'type' => 'nullable|in:text,image,file',
            'file' => 'nullable|file|max:10240', // 10MB max
            'reply_to_message_id' => 'nullable|integer|exists:messages,id'
        ]);

          $chatRoom = ChatRoom::find($chatRoomId);
    if (!$chatRoom) {
        return response()->json([
            'success' => false,
            'message' => 'Chat room not found'
        ], 404);
    }

        try {
              $fileData = [];
        $messageType = $request->input('type', 'text');

        // Handle file upload for personal chats only
        if ($request->hasFile('file')) {
            $file = $request->file('file');
                        $messageType = $this->detectMessageType($file);

            $fileData = $this->handleFileUpload($file);
            
            // Auto-detect message type based on file


        }
            
            // Auto-detect message type based on file


            $message = $this->chatService->sendMessage(
                $chatRoomId,
                $request->user()->id,
                $request->input('content'),
                $messageType,
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
 * Handle file upload and return file data
  */
// private function handleFileUpload($file): array
// {
//     try {
//         // Generate unique filename
//         $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
//         // Store in chat-media folder
//         $path = $file->storeAs('chat-media', $filename, 'public');
        
//         return [
//             'file_url' => \Storage::url($path),
//             'file_name' => $file->getClientOriginalName(),
//             'file_size' => $file->getSize(),
//             'mime_type' => $file->getMimeType()
//         ];
        
//     } catch (\Exception $e) {
//         throw new \Exception('Failed to upload file: ' . $e->getMessage());
//     }
// }

private function handleFileUpload($file): array 
{
    try {
        // Get file properties BEFORE moving the file
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();
        
        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $extension;
        
        // Define the path in public folder
        $publicPath = 'chat-media';
        $fullPath = public_path($publicPath);
        
        // Create directory if it doesn't exist
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        // Move file to public folder
        $file->move($fullPath, $filename);
        
        return [
            'file_url' => $publicPath . '/' . $filename,
            'file_name' => $originalName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType
        ];
        
    } catch (\Exception $e) {
        throw new \Exception('Failed to upload file: ' . $e->getMessage());
    }
}
/**
 * Detect message type based on file
 */
private function detectMessageType($file): string
{
    $mimeType = $file->getMimeType();
    
    if (str_starts_with($mimeType, 'image/')) {
        return 'image';
    } elseif (str_starts_with($mimeType, 'video/')) {
        return 'video';
    } else {
        return 'file';
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


    /**
 * Get all attendees for a specific event
 * GET /api/v1/events/{eventId}/attendees
 */
// public function getChatAttendees(Request $request, int $chatId): JsonResponse
// {
//     try {
//         $userId = $request->user()->id;
//         $getEvent = ChatRoom::find($chatId);

//         if (!$getEvent) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Chat room not found'
//             ], 404);
//         }
//         $eventId = $getEvent->event_id;
//         // Check if event exists
//         $event = Event::find($eventId);
//         if (!$event) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Event not found'
//             ], 404);
//         }

//         // Get all attendees with user information
//         $attendees = EventAttendee::with(['user:id,name,profile_photo'])
//             ->where('event_id', $eventId)
//             ->whereIn('status', ['interested', 'confirmed'])
//             ->orderBy('created_at', 'asc') // First joined, first listed
//             ->get();

//         // Calculate total members count
//         $totalMembers = $attendees->sum('total_members') ?: $attendees->count();
        
//         // Check if current user is attendee or host
//         $isUserAttendee = $attendees->contains('user_id', $userId);
//         $isUserHost = $event->host_id === $userId;

//         return response()->json([
//             'success' => true,
//             'data' => [
//                 'event_id' => $eventId,
//                 'event_name' => $event->name,
//                 'total_attendees' => $attendees->count(),
//                 'total_members' => $totalMembers,
//                 'attendees' => $attendees->map(function ($attendee) {
//                     return [
//                         'user_id' => $attendee->user->id,
//                         'name' => $attendee->user->name,
//                         'profile_photo' => $attendee->user->profile_photo ?? null,
//                         'total_members' => $attendee->total_members ?? 1,
//                     ];
//                 }),
//                 'user_context' => [
//                     'is_attendee' => $isUserAttendee,
//                     'is_host' => $isUserHost,
//                     'can_view_attendees' => $isUserAttendee || $isUserHost, // Only attendees and host can see full list
//                 ]
//             ]
//         ]);

//     } catch (\Exception $e) {
//         Log::error('Event attendees error:', ['error' => $e->getMessage(), 'event_id' => $eventId]);
        
//         return response()->json([
//             'success' => false,
//             'message' => 'Failed to fetch event attendees'
//         ], 500);
//     }
// }

public function getChatAttendees(Request $request, int $chatId): JsonResponse
{
    try {
        $userId = $request->user()->id;
        $getEvent = ChatRoom::find($chatId);

        if (!$getEvent) {
            return response()->json([
                'success' => false,
                'message' => 'Chat room not found'
            ], 404);
        }
        $eventId = $getEvent->event_id;
        
        // Check if event exists and load host data
        $event = Event::with(['host:id,name,profile_photo'])->find($eventId);
        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        // Get all attendees with user information
        $attendees = EventAttendee::with(['user:id,name,profile_photo'])
            ->where('event_id', $eventId)
            ->whereIn('status', ['interested', 'confirmed'])
            ->orderBy('created_at', 'asc') // First joined, first listed
            ->get();

        // Calculate total members count (including host)
        $totalMembers = 1 + ($attendees->sum('total_members') ?: $attendees->count()); // +1 for host
        
        // Check if current user is attendee or host
        $isUserAttendee = $attendees->contains('user_id', $userId);
        $isUserHost = $event->host_id === $userId;

        // Build attendees array with host first
        $attendeesData = collect();
        
        // Add host as first attendee
        if ($event->host) {
            $attendeesData->push([
                'user_id' => $event->host->id,
                'name' => $event->host->name,
                'profile_photo' => $event->host->profile_photo ?? null,
                'total_members' => 1,
                'is_host' => true,
                'role' => 'host'
            ]);
        }

        // Add regular attendees
        $attendees->each(function ($attendee) use ($attendeesData) {
            if ($attendee->user) { // Check if user exists (not soft deleted)
                $attendeesData->push([
                    'user_id' => $attendee->user->id,
                    'name' => $attendee->user->name,
                    'profile_photo' => $attendee->user->profile_photo ?? null,
                    'total_members' => $attendee->total_members ?? 1,
                    'is_host' => false,
                    'role' => 'attendee'
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'data' => [
                'event_id' => $eventId,
                'event_name' => $event->name,
                'total_attendees' => $attendees->count() + 1, // +1 for host
                'total_members' => $totalMembers, // Host + sum of all attendee members
                'attendees' => $attendeesData->values(), // Reset array keys
                'user_context' => [
                    'is_attendee' => $isUserAttendee,
                    'is_host' => $isUserHost,
                    'can_view_attendees' => $isUserAttendee || $isUserHost, // Only attendees and host can see full list
                ]
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Event attendees error:', ['error' => $e->getMessage(), 'event_id' => $eventId ?? null]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch event attendees'
        ], 500);
    }
}
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DmRequest;
use App\Models\User;
use App\Services\ChatService;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DmRequestController extends Controller
{
    private FirebaseNotificationService $notificationService;
    private ChatService $chatService;

    public function __construct(
        FirebaseNotificationService $notificationService,
        ChatService $chatService
    ) {
        $this->notificationService = $notificationService;
        $this->chatService = $chatService;
    }

    /**
     * Send a DM request to another user
     * POST /api/v1/dm-requests
     */
    public function sendDmRequest(Request $request, User $id): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
        ]);

        $senderId = $request->user()->id;
        $receiverId = $request->input('receiver_id');

        // Prevent self-request
        if ($senderId === $receiverId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send a DM request to yourself'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Check if request already exists
            $existingRequest = DmRequest::where('sender_id', $senderId)
                ->where('receiver_id', $receiverId)
                ->first();

            if ($existingRequest) {
                return response()->json([
                    'success' => false,
                    'message' => $existingRequest->isPending() ? 
                        'DM request already sent and pending' : 
                        'DM request already exists'
                ], 409);
            }

            // Check if they already have a chat (meaning request was previously accepted)
            $existingChat = \App\Models\ChatRoom::where('type', \App\Models\ChatRoom::TYPE_PERSONAL)
                ->whereHas('members', function ($q) use ($senderId) {
                    $q->where('user_id', $senderId);
                })
                ->whereHas('members', function ($q) use ($receiverId) {
                    $q->where('user_id', $receiverId);
                })
                ->first();

            if ($existingChat) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a chat with this user'
                ], 409);
            }

            // Create DM request
            $dmRequest = DmRequest::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message' => $request->input('message'),
                'status' => DmRequest::STATUS_PENDING,
            ]);

            // Send notification to receiver
            $this->notificationService->sendDmRequestNotification(
                $dmRequest->id,
                $senderId,
                $receiverId,
                $request->input('message')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'DM request sent successfully',
                'data' => [
                    'dm_request_id' => $dmRequest->id,
                    'status' => $dmRequest->status,
                    'sent_at' => $dmRequest->created_at->toISOString(),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to send DM request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept a DM request via notification action
     * POST /api/v1/dm-requests/{dmRequestId}/accept
     */
    public function acceptDmRequest(Request $request, int $dmRequestId)
    {
        $userId = $request->user()->id;

        try {
            DB::beginTransaction();
  
            $dmRequest = DmRequest::with(['sender', 'receiver'])
                ->where('id', $dmRequestId)
                ->where('receiver_id', $userId)
                ->where('status', DmRequest::STATUS_PENDING)
                ->first();

            if (!$dmRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'DM request not found or already responded to'
                ], 404);
            }

            // Accept the request
            $dmRequest->accept();

            // Create personal chat between the two users
            $chatRoom = $this->chatService->createPersonalChat(
                $dmRequest->sender_id,
                $dmRequest->receiver_id
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'DM request accepted successfully',
                'data' => [
                    'dm_request_id' => $dmRequest->id,
                    'chat_room_id' => $chatRoom->id,
                    'status' => $dmRequest->status,
                    'responded_at' => $dmRequest->responded_at->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept DM request'
            ], 500);
        }
    }

    /**
     * Reject a DM request via notification action
     * POST /api/v1/dm-requests/{dmRequestId}/reject
     */
    public function rejectDmRequest(Request $request, int $dmRequestId): JsonResponse
    {
        $userId = $request->user()->id;

        try {
            $dmRequest = DmRequest::where('id', $dmRequestId)
                ->where('receiver_id', $userId)
                ->where('status', DmRequest::STATUS_PENDING)
                ->first();

            if (!$dmRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'DM request not found or already responded to'
                ], 404);
            }

            // Reject the request
            $dmRequest->reject();

            return response()->json([
                'success' => true,
                'message' => 'DM request rejected successfully',
                'data' => [
                    'dm_request_id' => $dmRequest->id,
                    'status' => $dmRequest->status,
                    'responded_at' => $dmRequest->responded_at->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject DM request'
            ], 500);
        }
    }

    /**
     * Get pending DM requests for the authenticated user
     * GET /api/v1/dm-requests/pending
     */
    public function getPendingDmRequests(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $requests = DmRequest::with(['sender:id,name,profile_photo'])
            ->forReceiver($userId)
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests->map(function ($request) {
                return [
                    'id' => $request->id,
                    'message' => $request->message,
                    'status' => $request->status,
                    'created_at' => $request->created_at->toISOString(),
                    'sender' => [
                        'id' => $request->sender->id,
                        'name' => $request->sender->name,
                        'profile_photo' => $request->sender->profile_photo,
                    ],
                ];
            })
        ]);
    }

    /**
     * Get sent DM requests by the authenticated user
     * GET /api/v1/dm-requests/sent
     */
    public function getSentDmRequests(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $requests = DmRequest::with(['receiver:id,name,profile_photo'])
            ->forSender($userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests->map(function ($request) {
                return [
                    'id' => $request->id,
                    'message' => $request->message,
                    'status' => $request->status,
                    'created_at' => $request->created_at->toISOString(),
                    'responded_at' => $request->responded_at?->toISOString(),
                    'receiver' => [
                        'id' => $request->receiver->id,
                        'name' => $request->receiver->name,
                        'profile_photo' => $request->receiver->profile_photo,
                    ],
                ];
            })
        ]);
    }

    /**
     * Get user profile with DM request status and chat availability
     * GET /api/v1/users/{userId}/profile
     */
    public function getUserProfile(Request $request, int $userId): JsonResponse
    {
        $currentUserId = $request->user()->id;
        
        // Get the target user
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Basic user profile data
        $profileData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email, // Only if needed
            'phone_number' => $user->phone_number ?? null,
            'profile_photo' => $user->profile_photo ?? null,
            'bio' => $user->bio ?? null,
            'age' => $user->age ?? null,
            'city' => $user->city ?? null,
            'state' => $user->state ?? null,
            'is_online' => $user->is_online ?? false,
            'last_seen_at' => $user->last_seen_at ?? null,
        ];

        // Initialize DM and chat status
        $dmRequestStatus = null;
        $existingChatId = null;
        $canSendDmRequest = false;

        if ($currentUserId !== $userId) {
            // Check for existing DM request (in either direction)
            $dmRequest = DmRequest::where(function ($query) use ($currentUserId, $userId) {
                $query->where('sender_id', $currentUserId)->where('receiver_id', $userId);
            })->orWhere(function ($query) use ($currentUserId, $userId) {
                $query->where('sender_id', $userId)->where('receiver_id', $currentUserId);
            })->orderBy('created_at', 'desc')->first();

            // Check for existing chat
            $existingChat = \App\Models\ChatRoom::where('type', \App\Models\ChatRoom::TYPE_PERSONAL)
                ->whereHas('members', function ($q) use ($currentUserId) {
                    $q->where('user_id', $currentUserId);
                })
                ->whereHas('members', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->first();

            if ($existingChat) {
                $existingChatId = $existingChat->id;
            }

            // Determine DM request status and availability
            if ($dmRequest) {
                $dmRequestStatus = [
                    'id' => $dmRequest->id,
                    'status' => $dmRequest->status,
                    'sender_id' => $dmRequest->sender_id,
                    'receiver_id' => $dmRequest->receiver_id,
                    'message' => $dmRequest->message,
                    'created_at' => $dmRequest->created_at->toISOString(),
                    'responded_at' => $dmRequest->responded_at?->toISOString(),
                    'is_sender' => $dmRequest->sender_id === $currentUserId,
                    'is_receiver' => $dmRequest->receiver_id === $currentUserId,
                ];
            }

              $canSendDmRequest = !$existingChat && (
            !$dmRequest || 
            in_array($dmRequest->status, ['rejected'])
        );

        // If there's a pending DM request, user cannot send another one
        if ($dmRequest && $dmRequest->status === 'pending') {
            $canSendDmRequest = false;
        }

        // If there's an accepted DM request, they should have a chat already
        // But just in case, don't allow new DM requests
        if ($dmRequest && $dmRequest->status === 'accepted') {
            $canSendDmRequest = false;
        }
            // Can send DM request if no existing request and no chat
            // $canSendDmRequest = !$dmRequest && !$existingChat;
        }

        // Get mutual events (events both users have joined)
        $mutualEvents = [];
        if ($currentUserId !== $userId) {
            $currentUserEvents = \DB::table('event_attendees')
                ->where('user_id', $currentUserId)
                ->whereIn('status', ['interested', 'confirmed'])
                ->pluck('event_id');

            $mutualEventIds = \DB::table('event_attendees')
                ->where('user_id', $userId)
                ->whereIn('status', ['interested', 'confirmed'])
                ->whereIn('event_id', $currentUserEvents)
                ->pluck('event_id');

            if ($mutualEventIds->isNotEmpty()) {
                $mutualEvents = \App\Models\Event::whereIn('id', $mutualEventIds)
                    ->select('id', 'name', 'event_date', 'venue_name')
                    ->get()
                    ->map(function ($event) {
                        return [
                            'id' => $event->id,
                            'name' => $event->name,
                            'event_date' => $event->event_date->toDateString(),
                            'venue_name' => $event->venue_name,
                        ];
                    });
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $profileData,
                'dm_status' => [
                    'can_send_dm_request' => $canSendDmRequest,
                    'dm_request' => $dmRequestStatus,
                ],
               
            ]
        ]);
    }
}
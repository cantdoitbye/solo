<?php
// app/Http/Controllers/Api/V1/EventJoinController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Services\EventJoinService;
use App\Services\OlosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class EventJoinController extends Controller
{
    private EventJoinService $eventJoinService;
    private OlosService $olosService;

    public function __construct(
        EventJoinService $eventJoinService,
        OlosService $olosService
    ) {
        $this->eventJoinService = $eventJoinService;
        $this->olosService = $olosService;
    }


    public function getEventDetails(Request $request, int $eventId): JsonResponse
{
    try {
        $userId = $request->user()->id;
        
        // Get event with all necessary relationships
        $event = Event::with([
            'host', 
            'venueType', 
            'venueCategory', 
            'media',
            'menuImages',
            'attendees' => function ($query) {
                $query->whereIn('status', ['interested', 'confirmed']);
            }
        ])->find($eventId);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }

        // Check if event is available for joining
        $isJoinable = $event->status === 'published' && 
                     $event->event_date->isFuture() && 
                     $event->host_id !== $userId;

        // Check if user already joined
        $userAttendance = EventAttendee::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->whereIn('status', ['interested', 'confirmed'])
            ->first();

        // Calculate current attendees (sum of all members)
        $currentTotalAttendees = $event->attendees->sum('total_members') 
                               ?: $event->attendees->count(); // Fallback for legacy records

        // Calculate available slots
        $availableSlots = $event->max_group_size ? 
                         ($event->max_group_size - $currentTotalAttendees) : 
                         null;

        // Get user's current Olos balance
        $userOlosBalance = app(OlosService::class)->getUserBalance($userId);

          // Process event media to include file_url
        $eventMedia = $event->media->map(function ($media) {
            return [
                'id' => $media->id,
                'media_type' => $media->media_type,
                'file_url' => $media->file_url,
            ];
        })->toArray();

         $menuImages = $event->menuImages->map(function ($menu) {
            return [
                'id' => $menu->id,
                'file_url' => $menu->file_url
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'description' => $event->description,
                    'event_date' => $event->event_date->toDateString(),
                    'event_time' => $event->event_time->format('H:i'),
                    'timezone' => $event->timezone,
                    'tags' => $event->tags,
                    'notes' => $event->notes,
                    'event_image' => $event->event_image,
 'media' => $eventMedia,      
  'menu_images' => $menuImages,                 
                    // Venue details
                    'venue' => [
                        'type' => $event->venueType->name ?? null,
                        'category' => $event->venueCategory->name ?? null,
                        'name' => $event->venue_name,
                        'address' => $event->venue_address,
                        'city' => $event->city,
                        'state' => $event->state,
                        'country' => $event->country,
                        'latitude' => $event->latitude,
                        'longitude' => $event->longitude,
                    ],
                    
                    // Host details
                    'host' => [
                        'id' => $event->host->id,
                        'name' => $event->host->name ?? 'Unknown',
                        'phone_number' => $event->host->phone_number ?? null,
                    ],
                    
                    // Attendee requirements
                    'requirements' => [
                        'min_group_size' => $event->min_group_size,
                        'max_group_size' => $event->max_group_size,
                        'min_age' => $event->min_age,
                        'max_age' => $event->max_age,
                        'gender_rule_enabled' => $event->gender_rule_enabled,
                        'allowed_genders' => $event->allowed_genders,
                        'gender_composition' => $event->gender_composition,
                    ],
                    
                    // Cost and capacity
                    'pricing' => [
                        'token_cost_per_attendee' => $event->token_cost_per_attendee,
                        'is_free' => $event->token_cost_per_attendee == 0,
                    ],
                    
                    'capacity' => [
                        'current_attendees' => $currentTotalAttendees,
                        'max_capacity' => $event->max_group_size,
                        'available_slots' => $availableSlots,
                        'is_full' => $availableSlots !== null && $availableSlots <= 0,
                    ],
                    
                    // Status
                    'status' => $event->status,
                    'published_at' => $event->published_at?->toISOString(),
                ],
                
                // User-specific data
                'user_context' => [
                    'can_join' => $isJoinable && !$userAttendance,
                    'is_host' => $event->host_id === $userId,
                    'already_joined' => !!$userAttendance,
                    'attendance_status' => $userAttendance?->status,
                    'joined_members_count' => $userAttendance?->total_members ?? 0,
                    'olos_balance' => $userOlosBalance,
                    'can_afford_one_member' => $userOlosBalance >= $event->token_cost_per_attendee,
                    'max_affordable_members' => $event->token_cost_per_attendee > 0 
                        ? floor($userOlosBalance / $event->token_cost_per_attendee) 
                        : 10, // If free event, max 10 members
                ],
                
                // Validation messages
                'validation' => [
                    'messages' => $this->getValidationMessages($event, $userId, $userOlosBalance, $availableSlots),
                ],
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Event details error:', ['error' => $e->getMessage()]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch event details'
        ], 500);
    }
}



    /**
     * Join an event with multiple members (single API endpoint)
     * POST /api/v1/events/{eventId}/join
     */
    public function joinEvent(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'members' => 'required|array|min:1|max:10', // Allow up to 10 members max
            'members.*.member_name' => 'required|string|max:255',
            'members.*.member_email' => 'nullable|email|max:255',
            'members.*.member_contact' => 'nullable|string|max:20',
             'members.*.govt_id_file_path' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',

            // 'members.*.govt_id' => 'nullable|string|max:100',
            // 'members.*.govt_id_file_path' => 'nullable|string|max:500', // If file upload implemented
        ]);

    //    foreach ($request->input('members', []) as $index => $member) {
    //     $fileKey = "govt_id_file_path_{$index}";
    //     if ($request->hasFile($fileKey)) {
    //         $request->validate([
    //             $fileKey => 'file|mimes:jpeg,jpg,png,pdf|max:2048' // max 2MB
    //         ]);
    //     }
    // }

        try {
            $membersData = $request->input('members');
        $processedMembersData = $this->processGovtIdUploads($request, $membersData);
        


            $result = $this->eventJoinService->joinEvent(
                $request->user()->id,
                $eventId,
                $membersData,
                $processedMembersData
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }


    /**
 * Process government ID file uploads and add file paths to members data
 */
 private function processGovtIdUploads(Request $request, array $membersData): array
{
    foreach ($membersData as $index => &$member) {
        $fileKey = "members.$index.govt_id_file_path";
        Log::info("Checking file for member {$index} at key: {$fileKey}");

        if ($request->hasFile($fileKey)) {
            $file = $request->file($fileKey);

            if ($file && $file->isValid()) {
                try {
                    $fileName = 'govt_id_member_' . $index . '_' . uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                    $filePath = $file->storeAs('govt_ids', $fileName, 'public');

                    $member['govt_id_file_path'] = $filePath;
                    $member['govt_id_file_url'] = asset('storage/' . $filePath);
                    $member['govt_id_original_name'] = $file->getClientOriginalName();
                    $member['govt_id_file_size'] = $file->getSize();
                    $member['govt_id_mime_type'] = $file->getMimeType();

                    Log::info("Uploaded govt ID for member {$index}", [
                        'path' => $filePath,
                        'url' => $member['govt_id_file_url'],
                    ]);

                } catch (\Exception $e) {
                    Log::error("Upload failed for member {$index}: " . $e->getMessage());
                    throw new \Exception("Failed to upload govt ID for member " . ($index + 1));
                }
            } else {
                Log::warning("Invalid file uploaded for member {$index}");
                $member['govt_id_file_path'] = null;
                $member['govt_id_file_url'] = null;
            }
        } else {
            Log::info("No file uploaded for member {$index}");
            $member['govt_id_file_path'] = null;
            $member['govt_id_file_url'] = null;
        }
    }

    return $membersData;
}



    /**
     * Get user's joined events
     * GET /api/v1/events/joined
     */
    public function getJoinedEvents(Request $request): JsonResponse
    {
        try {
            $events = $this->eventJoinService->getUserJoinedEvents($request->user()->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $events,
                    'total_events' => count($events)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel event attendance
     * POST /api/v1/events/{eventId}/cancel
     */
    public function cancelAttendance(Request $request, int $eventId): JsonResponse
    {
        try {
            $result = $this->eventJoinService->cancelEventAttendance(
                $request->user()->id,
                $eventId
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get user's Olos balance and summary
     * GET /api/v1/olos/balance
     */
    public function getOlosBalance(Request $request): JsonResponse
    {
        try {
            $summary = $this->olosService->getUserOlosSummary($request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get user's Olos transaction history
     * GET /api/v1/olos/transactions
     */
    public function getOlosTransactions(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $limit = $request->get('limit', 50);
            $transactions = $this->olosService->getUserTransactions($request->user()->id, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'total_transactions' => count($transactions)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Initialize user Olos (can be called during onboarding completion)
     * POST /api/v1/olos/initialize
     */
    public function initializeOlos(Request $request): JsonResponse
    {
        try {
            $userOlos = $this->olosService->initializeUserOlos($request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Olos account initialized successfully',
                'data' => [
                    'current_balance' => $userOlos->balance,
                    'registration_bonus' => OlosService::REGISTRATION_BONUS,
                    'total_earned' => $userOlos->total_earned,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Determine error type for frontend handling
     */
    private function getErrorType(string $message): string
    {
        if (str_contains($message, 'Insufficient Olos')) {
            return 'insufficient_olos';
        }

        if (str_contains($message, 'already joined')) {
            return 'already_joined';
        }

        if (str_contains($message, 'Event is full')) {
            return 'event_full';
        }

        if (str_contains($message, 'not available')) {
            return 'event_unavailable';
        }

        if (str_contains($message, 'Age requirement') || str_contains($message, 'Gender requirements')) {
            return 'eligibility_failed';
        }

        return 'general_error';
    }


    private function getValidationMessages(Event $event, int $userId, float $userBalance, ?int $availableSlots): array
{
    $messages = [];

    // Check if event is available
    if ($event->status !== 'published') {
        $messages[] = [
            'type' => 'error',
            'message' => 'This event is not available for joining'
        ];
    }

    // Check if event is past
    if ($event->event_date->isPast()) {
        $messages[] = [
            'type' => 'error',
            'message' => 'Cannot join past events'
        ];
    }

    // Check if user is host
    if ($event->host_id === $userId) {
        $messages[] = [
            'type' => 'info',
            'message' => 'You are the host of this event'
        ];
    }

    // Check if user already joined
    $userAttendance = EventAttendee::where('user_id', $userId)
        ->where('event_id', $event->id)
        ->whereIn('status', ['interested', 'confirmed'])
        ->first();

    if ($userAttendance) {
        $messages[] = [
            'type' => 'info',
            'message' => "You have already joined this event with {$userAttendance->total_members} member(s)"
        ];
    }

    // Check capacity
    if ($availableSlots !== null && $availableSlots <= 0) {
        $messages[] = [
            'type' => 'warning',
            'message' => 'This event is full'
        ];
    } elseif ($availableSlots !== null && $availableSlots <= 5) {
        $messages[] = [
            'type' => 'warning',
            'message' => "Only {$availableSlots} slots remaining"
        ];
    }

    // Check Olos balance
    if ($event->token_cost_per_attendee > 0) {
        if ($userBalance < $event->token_cost_per_attendee) {
            $messages[] = [
                'type' => 'error',
                'message' => "Insufficient Olos. You need {$event->token_cost_per_attendee} Olos per member, but have {$userBalance} Olos"
            ];
        } elseif ($userBalance < ($event->token_cost_per_attendee * 2)) {
            $messages[] = [
                'type' => 'info',
                'message' => "You can afford " . floor($userBalance / $event->token_cost_per_attendee) . " member(s) with your current Olos balance"
            ];
        }
    }

    return $messages;
}
}
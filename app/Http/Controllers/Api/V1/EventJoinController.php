<?php
// app/Http/Controllers/Api/V1/EventJoinController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventReview;
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
        
        // Get event with necessary relationships (UPDATED for new structure)
        $event = Event::with([
            'host:id,name,phone_number,profile_photo', 
            'suggestedLocation:id,name,description,category,google_maps_url',
            'suggestedLocation.primaryImage:id,suggested_location_id,image_url,is_primary',
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

        // Check if event is gender balanced
        $isGenderBalanced = $this->isEventGenderBalanced($event);
        
        // Get gender-specific slots if gender balanced
        $genderSlots = null;
        if ($isGenderBalanced) {
            $genderSlots = $this->getGenderSlots($event, $currentTotalAttendees);
        }

           $existingReview = EventReview::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->first();

                $eventData = $event->toArray();
$attendeeInfo = $this->getAttendeeInfo($eventData);

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
                    'notes' => $event->notes,
                    
                    // UPDATED: Location details from SuggestedLocation
                    'location' => [
                        'suggested_location_id' => $event->suggested_location_id,
                        'name' => $event->suggestedLocation->name ?? 'Custom Location',
                        'description' => $event->suggestedLocation->description ?? null,
                        'category' => $event->suggestedLocation->category ?? null,
                        'venue_name' => $event->venue_name,
                        'venue_address' => $event->venue_address,
                        'city' => $event->city,
                        'state' => $event->state,
                        'country' => $event->country,
                        'latitude' => $event->latitude,
                        'longitude' => $event->longitude,
                        'google_maps_url' => $event->suggestedLocation->google_maps_url ?? null,
                        'image_url' => $event->suggestedLocation && $event->suggestedLocation->primaryImage 
                            ? $event->suggestedLocation->primaryImage->image_url 
                            : null,
                    ],
                    
                    // Host details
                    'host' => [
                        'id' => $event->host->id,
                        'name' => $event->host->name ?? 'Unknown',
                        'phone_number' => $event->host->phone_number ?? null,
                        'profile_photo' => $event->host->profile_photo ?? null,
                    ],
                    
                    // UPDATED: Requirements with gender balance info
                    'requirements' => [
                        'group_size' => $event->min_group_size, // Simplified - min and max are same
                        'min_age' => $event->min_age,
                        'max_age' => $event->max_age,
                        'age_restriction_disabled' => $event->age_restriction_disabled ?? false,
                        'age_range_display' => $this->getAgeRangeDisplay($event),
                        'gender_balanced' => $isGenderBalanced,
                        'gender_composition' => $event->gender_composition,
                        'allowed_genders' => $event->allowed_genders,
                    ],
                    'attendees' => $attendeeInfo,
                    
                    // Cost and capacity (UPDATED with gender balance)
                    'pricing' => [
                        'token_cost_per_attendee' => 5.00, // Fixed at 5 olos
                        'is_free' => false, // Always costs 5 olos
                        'total_cost' => $event->min_group_size * 5.00,
                    ],
                    
                    'capacity' => [
                        'current_attendees' => $currentTotalAttendees,
                        'group_size' => $event->min_group_size,
                        'available_slots' => $availableSlots,
                        'is_full' => $availableSlots !== null && $availableSlots <= 0,
                        'gender_slots' => $genderSlots, // NEW: Gender-specific slots
                    ],
                    
                    // Status
                    'status' => $event->status,
                    'published_at' => $event->published_at?->toISOString(),
                ],
                
                // User-specific data (UPDATED)
                'user_context' => [
                    'can_join' => $isJoinable && !$userAttendance,
                    'is_host' => $event->host_id === $userId,
                    'already_joined' => !!$userAttendance,
                    'is_reviewed' => $existingReview? true : false,
                    'attendance_status' => $userAttendance?->status,
                    'joined_members_count' => $userAttendance?->total_members ?? 0,
                    'olos_balance' => $userOlosBalance,
                    'can_afford_one_member' => $userOlosBalance >= 5.00, // Fixed at 5 olos
                    'max_affordable_members' => floor($userOlosBalance / 5.00),
                ],
                
                // Validation messages
                // 'validation' => [
                //     'messages' => $this->getValidationMessages($event, $userId, $userOlosBalance, $availableSlots),
                // ],
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
 * Get attendee information using the same logic as HomeScreenService
 */
private function getAttendeeInfo(array $eventData): array
{
    // Calculate actual attendees including host
    $currentAttendees = $this->calculateActualAttendees($eventData);
    $maxGroupSize = $eventData['max_group_size'] ?? $eventData['min_group_size'] ?? 0;
    $availableSpots = max(0, $maxGroupSize - $currentAttendees);
    
    $baseInfo = [
        'current_count' => $currentAttendees, // Includes host + members
        'group_size' => $eventData['min_group_size'],
        'available_spots' => $availableSpots,
        'spots_text' => $availableSpots > 0 ? "{$availableSpots} spots left" : "Full",
        'profiles' => []
    ];

    // Format profiles with host first, then members
    if (isset($eventData['attendees']) && is_array($eventData['attendees'])) {
        $baseInfo['profiles'] = $this->formatAttendeeProfiles($eventData['attendees'], $eventData);
    } else {
        // If no attendees data, still show the host
        if (isset($eventData['host'])) {
            $host = $eventData['host'];
            $baseInfo['profiles'] = [[
                'id' => $host['id'],
                'name' => $host['name'],
                'profile_photo' => $host['profile_photo'] ?? null,
                'is_host' => true
            ]];
        }
    }

    return $baseInfo;
}

/**
 * Calculate actual attendees including event host
 */
private function calculateActualAttendees(array $eventData): int
{
    $totalMembers = 0;
    
    // Add 1 for the event host (host is always counted as an attendee)
    $totalMembers += 1;
    
    // If attendees data is loaded with the event
    if (isset($eventData['attendees']) && is_array($eventData['attendees'])) {
        foreach ($eventData['attendees'] as $attendee) {
            $attendeeData = is_array($attendee) ? $attendee : $attendee->toArray();
            
            // Only count active attendees (interested or confirmed)
            $status = $attendeeData['status'] ?? 'interested';
            if (in_array($status, ['interested', 'confirmed'])) {
                $totalMembers += $attendeeData['total_members'] ?? 1;
            }
        }
        
        return $totalMembers;
    }
    
    // Fallback: host + attendees_count if attendees array not loaded (legacy)
    return 1 + ($eventData['attendees_count'] ?? 0);
}

/**
 * Format attendee profiles showing host first, then all members from members_data
 */
private function formatAttendeeProfiles(array $attendees, array $eventData): array
{
    $profiles = [];
    
    // FIRST: Add the event host as the first profile
    if (isset($eventData['host'])) {
        $host = $eventData['host'];
        $profiles[] = [
            'id' => $host['id'],
            'name' => $host['name'],
            'profile_photo' => $host['profile_photo'] ?? null,
            'is_host' => true
        ];
    }
    
    // THEN: Add all members from members_data
    foreach ($attendees as $attendee) {
        $attendeeData = is_array($attendee) ? $attendee : $attendee->toArray();
        
        // Only include active attendees (interested or confirmed)
        $status = $attendeeData['status'] ?? 'interested';
        if (!in_array($status, ['interested', 'confirmed'])) {
            continue;
        }
        
        // Get members_data JSON
        $membersData = $attendeeData['members_data'] ?? [];
        
        // Extract individual members from the JSON data
        if (is_array($membersData) && !empty($membersData)) {
            foreach ($membersData as $member) {
                if (isset($member['member_name']) && !empty(trim($member['member_name']))) {
                    $profiles[] = [
                        'id' => null,
                        'name' => $member['member_name'],
                        'profile_photo' => null,
                        'is_host' => false
                    ];
                }
            }
        }
        // Handle legacy data without members_data
        else {
            $totalMembers = $attendeeData['total_members'] ?? 1;
            
            for ($i = 0; $i < $totalMembers; $i++) {
                $profiles[] = [
                    'id' => null,
                    'name' => 'Member ' . ($i + 1),
                    'profile_photo' => null,
                    'is_host' => false
                ];
            }
        }
    }
    
    return $profiles;
}

/**
 * Check if event is gender balanced
 */
private function isEventGenderBalanced($event): bool
{
    if (!$event->gender_rule_enabled) {
        return false;
    }
    
    $composition = $event->gender_composition ?? '';
    return strpos($composition, 'Gender balanced') !== false;
}

/**
 * Get gender-specific slots for gender balanced events
 */
private function getGenderSlots($event, int $currentAttendees): ?array
{
    if (!$this->isEventGenderBalanced($event)) {
        return null;
    }
    
    $groupSize = $event->min_group_size;
    $maleSlots = $groupSize / 2;
    $femaleSlots = $groupSize / 2;
    
    // TODO: Get actual gender count from current attendees
    // For now, using placeholder values
    $currentMales = 0; 
    $currentFemales = 0;
    
    return [
        'male_spots_total' => $maleSlots,
        'female_spots_total' => $femaleSlots,
        'male_spots_left' => max(0, $maleSlots - $currentMales),
        'female_spots_left' => max(0, $femaleSlots - $currentFemales),
        'ratio' => "{$maleSlots}:{$femaleSlots}",
    ];
}

/**
 * Get age range display text
 */
private function getAgeRangeDisplay($event): string
{
    if ($event->age_restriction_disabled ?? false) {
        return 'All ages welcome';
    }
    
    return "Ages {$event->min_age}-{$event->max_age}";
}



    /**
     * Join an event with multiple members (single API endpoint)
     * POST /api/v1/events/{eventId}/join
     */
    public function joinEvent(Request $request, int $eventId)
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
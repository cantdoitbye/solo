<?php
// app/Services/EventJoinService.php

namespace App\Services;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use App\Models\OlosTransaction;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EventJoinService
{
    private EventRepositoryInterface $eventRepository;
    private OlosService $olosService;

    public function __construct(
        EventRepositoryInterface $eventRepository,
        OlosService $olosService
    ) {
        $this->eventRepository = $eventRepository;
        $this->olosService = $olosService;
    }

//     /**
//      * Handle user joining an event with multiple members (single API endpoint)
//      */
//     public function joinEvent(int $userId, int $eventId, array $membersData): array
//     {
//         return DB::transaction(function () use ($userId, $eventId, $membersData) {
//             // 1. Validate event exists and is active
//             $event = $this->validateEvent($eventId);
            
//             // 2. Validate members data
//             $this->validateMembersData($membersData);
            
//             // 3. Calculate total members and cost
//             $totalMembers = count($membersData);
//             $costPerMember = $event->token_cost_per_attendee;
//             $totalCost = $totalMembers * $costPerMember;
            
//             // 4. Validate user eligibility
//             $this->validateUserEligibility($userId, $event);
            
//             // 5. Check if user already joined
//             $this->checkExistingAttendance($userId, $eventId);
            
//             // 6. Validate Olos balance for total cost
//             // $this->validateOlosBalance($userId, $totalCost);
            
//             // 7. Check event capacity for all members
//             $this->validateEventCapacity($event, $totalMembers);
            
//             // 8. Deduct total Olos cost
//             $olosTransaction = $this->deductEventCost($userId, $event, $totalMembers, $totalCost);
            
//             // 9. Create attendee record with member data as JSON
//             $attendee = $this->createAttendeeRecord($userId, $event, $totalMembers, $costPerMember, $totalCost, $membersData);
            
//             app(\App\Services\FirebaseNotificationService::class)->sendMemberJoinNotification(
//     $eventId, 
//     $userId, 
//     $totalMembers
// );
//             // 10. Update user's Olos balance info
//             $userOlosSummary = $this->olosService->getUserOlosSummary($userId);
            
//                $chatService = app(ChatService::class);
//         $chatService->addUserToEventChat($eventId, $userId);
//             return [
//                 'success' => true,
//                 'message' => 'Successfully joined the event!',
//                 'event_id' => $eventId,
//                 'attendee_id' => $attendee->id,
//                 'status' => $attendee->status,
//                 'total_members' => $totalMembers,
//                 'cost_per_member' => $costPerMember,
//                 'total_cost' => $totalCost,
//                 'tokens_paid' => $attendee->tokens_paid, // Legacy field, same as total_cost
//                 'joined_at' => $attendee->joined_at->toISOString(),
//                 'members' => $membersData, // Return the member data directly
//                 'olos_balance' => [
//                     'current_balance' => $userOlosSummary['current_balance'],
//                     'tokens_spent' => $totalCost,
//                     'transaction_id' => $olosTransaction->id,
//                 ],
//                 'event_details' => [
//                     'name' => $event->name,
//                     'event_date' => $event->event_date->toDateString(),
//                     'event_time' => $event->event_time->format('H:i'),
//                     'venue_name' => $event->venue_name,
//                     'token_cost_per_attendee' => $event->token_cost_per_attendee,
//                     'current_total_attendees' => $this->getTotalEventAttendees($event),
//                     'max_group_size' => $event->max_group_size,
//                 ],
//             ];
//         });
//     }

/**
 * Handle user joining an event with multiple members (single API endpoint)
 */
public function joinEvent(int $userId, int $eventId, array $membersData): array
{
    return DB::transaction(function () use ($userId, $eventId, $membersData) {
        try {
            // Add logging to debug the issue
            \Log::info('EventJoinService::joinEvent called', [
                'userId' => $userId,
                'eventId' => $eventId,
                'membersCount' => count($membersData)
            ]);

            // 1. Validate event exists and is active
            $event = $this->validateEvent($eventId);
            
            // 2. Validate members data
            $this->validateMembersData($membersData);
            
            // 3. Calculate total members and cost
            $totalMembers = count($membersData);
            $costPerMember = $event->token_cost_per_attendee;
            $totalCost = $totalMembers * $costPerMember;
            
            // 4. Validate user eligibility
            $this->validateUserEligibility($userId, $event);
            
            // 5. Check if user already joined
            $this->checkExistingAttendance($userId, $eventId);
            
            // 6. Validate Olos balance for total cost
            // $this->validateOlosBalance($userId, $totalCost);
            
            // 7. Check event capacity for all members
            $this->validateEventCapacity($event, $totalMembers);
            
            // 8. Deduct total Olos cost
            $olosTransaction = $this->deductEventCost($userId, $event, $totalMembers, $totalCost);
            
            // 9. Create attendee record with member data as JSON
            $attendee = $this->createAttendeeRecord($userId, $event, $totalMembers, $costPerMember, $totalCost, $membersData);
            
            // 10. Send notification to event creator
            try {
                app(\App\Services\FirebaseNotificationService::class)->sendMemberJoinNotification(
                    $eventId, 
                    $userId, 
                    $totalMembers
                );
            } catch (\Exception $e) {
                \Log::error('Failed to send member join notification', [
                    'error' => $e->getMessage(),
                    'eventId' => $eventId,
                    'userId' => $userId
                ]);
                // Don't fail the entire transaction for notification failure
            }
            
            // 11. Update user's Olos balance info
            $userOlosSummary = $this->olosService->getUserOlosSummary($userId);
            
            // 12. Add user to event chat
            try {
                $chatService = app(\App\Services\ChatService::class);
                $chatService->addUserToEventChat($eventId, $userId);
            } catch (\Exception $e) {
                \Log::error('Failed to add user to event chat', [
                    'error' => $e->getMessage(),
                    'eventId' => $eventId,
                    'userId' => $userId
                ]);
                // Don't fail the entire transaction for chat failure
            }
            
            return [
                'success' => true,
                'message' => 'Successfully joined the event!',
                'event_id' => $eventId,
                'attendee_id' => $attendee->id,
                'status' => $attendee->status,
                'total_members' => $totalMembers,
                'cost_per_member' => $costPerMember,
                'total_cost' => $totalCost,
                'tokens_paid' => $attendee->tokens_paid, // Legacy field, same as total_cost
                'joined_at' => $attendee->joined_at->toISOString(),
                'members' => $membersData, // Return the member data directly
                'olos_balance' => [
                    'current_balance' => $userOlosSummary['current_balance'],
                    'tokens_spent' => $totalCost,
                    'transaction_id' => $olosTransaction->id,
                ],
                'event_details' => [
                    'name' => $event->name,
                    'event_date' => $event->event_date->toDateString(),
                    'event_time' => $event->event_time->format('H:i'),
                    'venue_name' => $event->venue_name,
                    'token_cost_per_attendee' => $event->token_cost_per_attendee,
                    'current_total_attendees' => $this->getTotalEventAttendees($event),
                    'max_group_size' => $event->max_group_size,
                ],
            ];

        } catch (\Exception $e) {
            \Log::error('EventJoinService::joinEvent error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'userId' => $userId ?? 'undefined',
                'eventId' => $eventId ?? 'undefined'
            ]);
            throw $e;
        }
    });
}

    /**
     * Validate event exists and is joinable
     */
    private function validateEvent(int $eventId): Event
    {
        $event = Event::with(['attendees', 'host'])->find($eventId);
        
        if (!$event) {
            throw new \Exception('Event not found');
        }
        
        if ($event->status !== 'published') {
            throw new \Exception('Event is not available for joining');
        }
        
        if ($event->event_date->isPast()) {
            throw new \Exception('Cannot join past events');
        }
        
        return $event;
    }

    /**
     * Validate user eligibility for the event
     */
    private function validateUserEligibility(int $userId, Event $event): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }
        
        // Check if user is the host
        if ($event->host_id === $userId) {
            throw new \Exception('Event host cannot join their own event');
        }
        
        // Check age restrictions
        if ($user->age && ($user->age < $event->min_age || $user->age > $event->max_age)) {
            throw new \Exception("Age requirement not met. Event is for ages {$event->min_age}-{$event->max_age}");
        }
        
        // Check gender restrictions if enabled
        if ($event->gender_rule_enabled && $event->allowed_genders) {
            $userGender = $user->gender ?? null;
            if ($userGender && !in_array($userGender, $event->allowed_genders)) {
                throw new \Exception('Gender requirements not met for this event');
            }
        }
    }

    /**
     * Check if user already joined this event
     */
    private function checkExistingAttendance(int $userId, int $eventId): void
    {
        $existingAttendee = EventAttendee::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->whereIn('status', ['interested', 'confirmed'])
            ->first();
            
        if ($existingAttendee) {
            throw new \Exception('You have already joined this event');
        }
    }

    /**
     * Validate members data array
     */
    private function validateMembersData(array $membersData): void
    {
        if (empty($membersData)) {
            throw new \Exception('At least one member is required');
        }

        foreach ($membersData as $index => $member) {
            if (empty($member['member_name'])) {
                throw new \Exception("Member name is required for member " . ($index + 1));
            }

            if (!empty($member['member_email']) && !filter_var($member['member_email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Invalid email for member " . ($index + 1));
            }
        }
    }

    /**
     * Validate user has enough Olos balance for total cost
     */
    private function validateOlosBalance(int $userId, float $totalCost): void
    {
        $userBalance = $this->olosService->getUserBalance($userId);
        
        if ($userBalance < $totalCost) {
            throw new \Exception("Insufficient Olos. Required: {$totalCost}, Available: {$userBalance}");
        }
    }

    /**
     * Check if event has capacity for additional members
     */
    private function validateEventCapacity(Event $event, int $additionalMembers = 1): void
    {
        if ($event->max_group_size) {
            $currentTotalAttendees = $this->getTotalEventAttendees($event);
                
            if (($currentTotalAttendees + $additionalMembers) > $event->max_group_size) {
                throw new \Exception('Event is full. Cannot accommodate ' . $additionalMembers . ' more attendee(s). Available slots: ' . ($event->max_group_size - $currentTotalAttendees));
            }
        }
    }

    /**
     * Get total number of individual attendees (members) for an event using existing EventAttendee
     */
    private function getTotalEventAttendees(Event $event): int
    {
        // Use the new total_members field, fallback to count for backward compatibility
        return $event->attendees()
            ->whereIn('status', ['interested', 'confirmed'])
            ->sum('total_members') ?: $event->attendees()
            ->whereIn('status', ['interested', 'confirmed'])
            ->count();
    }

    /**
     * Deduct Olos for event participation (multiple members)
     */
    private function deductEventCost(int $userId, Event $event, int $totalMembers, float $totalCost): OlosTransaction
    {
        if ($totalCost <= 0) {
            // Free event, create a zero-cost transaction for tracking
            return $this->olosService->deductOlos(
                $userId,
                0,
                OlosTransaction::TRANSACTION_TYPE_EVENT_JOIN,
                "Joined free event: {$event->name} ({$totalMembers} members)",
                (string) $event->id,
                [
                    'event_name' => $event->name,
                    'event_date' => $event->event_date->toDateString(),
                    'total_members' => $totalMembers,
                    'cost_per_member' => $event->token_cost_per_attendee,
                    'total_cost' => $totalCost,
                ]
            );
        }
        
        return $this->olosService->deductOlos(
            $userId,
            $totalCost,
            OlosTransaction::TRANSACTION_TYPE_EVENT_JOIN,
            "Joined event: {$event->name} ({$totalMembers} members)",
            (string) $event->id,
            [
                'event_name' => $event->name,
                'event_date' => $event->event_date->toDateString(),
                'total_members' => $totalMembers,
                'cost_per_member' => $event->token_cost_per_attendee,
                'total_cost' => $totalCost,
            ]
        );
    }

    /**
     * Create attendee record with member data stored as JSON
     */
    private function createAttendeeRecord(
        int $userId,
        Event $event,
        int $totalMembers,
        float $costPerMember,
        float $totalCost,
        array $membersData
    ): EventAttendee {
        return EventAttendee::create([
            'event_id' => $event->id,
            'user_id' => $userId,
            'status' => 'interested', // Default status
            'tokens_paid' => $totalCost, // Keep existing field for backward compatibility
            'total_members' => $totalMembers,
            'cost_per_member' => $costPerMember,
            'total_cost' => $totalCost,
            'members_data' => $membersData, // Store all member details as JSON
            'joined_at' => now(),
        ]);
    }

    /**
     * Get user's joined events with member details using JSON storage
     */
    public function getUserJoinedEvents(int $userId): array
    {
        $attendees = EventAttendee::with([
            'event.host', 
            'event.venueType', 
            'event.venueCategory'
        ])
            ->where('user_id', $userId)
            ->whereIn('status', ['interested', 'confirmed'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $attendees->map(function ($attendee) {
            $event = $attendee->event;
            return [
                'attendee_id' => $attendee->id,
                'status' => $attendee->status,
                'total_members' => $attendee->getTotalMembersCount(),
                'cost_per_member' => $attendee->getCostPerMemberAmount(),
                'total_cost' => $attendee->getTotalCostAmount(),
                'tokens_paid' => $attendee->tokens_paid, // For backward compatibility
                'joined_at' => $attendee->joined_at->toISOString(),
                'members' => $attendee->getMembersData(), // Get member data from JSON
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'description' => $event->description,
                    'event_date' => $event->event_date->toDateString(),
                    'event_time' => $event->event_time->format('H:i'),
                    'venue_name' => $event->venue_name,
                    'venue_address' => $event->venue_address,
                    'token_cost_per_attendee' => $event->token_cost_per_attendee,
                    'host_name' => $event->host->name ?? 'Unknown',
                    'current_total_attendees' => $this->getTotalEventAttendees($event),
                    'max_group_size' => $event->max_group_size,
                ],
            ];
        })->toArray();
    }

    /**
     * Cancel event attendance with proper refund calculation using JSON storage
     */
    public function cancelEventAttendance(int $userId, int $eventId): array
    {
        return DB::transaction(function () use ($userId, $eventId) {
            $attendee = EventAttendee::where('user_id', $userId)
                ->where('event_id', $eventId)
                ->whereIn('status', ['interested', 'confirmed'])
                ->first();

            if (!$attendee) {
                throw new \Exception('Event attendance not found or already cancelled');
            }

            $event = $attendee->event;
            
            // Check cancellation policy (you can implement your own logic here)
            if ($event->event_date->diffInHours(now()) < 24) {
                throw new \Exception('Cannot cancel within 24 hours of event');
            }

            // Update attendee status
            $attendee->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'User cancelled',
            ]);

            // Calculate refund amount - use total_cost if available, fallback to tokens_paid
            $refundAmount = $attendee->getTotalCostAmount();
            
            if ($refundAmount > 0) {
                $memberText = $attendee->getTotalMembersCount() > 1 
                    ? " ({$attendee->getTotalMembersCount()} members)" 
                    : "";
                    
                $this->olosService->refundOlos(
                    $userId,
                    $refundAmount,
                    "Refund for cancelled event: {$event->name}{$memberText}",
                    (string) $event->id,
                    [
                        'event_name' => $event->name,
                        'total_members' => $attendee->getTotalMembersCount(),
                        'original_cost' => $refundAmount,
                        'cancelled_at' => now()->toISOString(),
                    ]
                );
            }

            return [
                'success' => true,
                'message' => 'Event attendance cancelled successfully',
                'cancelled_members' => $attendee->getTotalMembersCount(),
                'refund_amount' => $refundAmount,
                'new_balance' => $this->olosService->getUserBalance($userId),
            ];
        });
    }
}
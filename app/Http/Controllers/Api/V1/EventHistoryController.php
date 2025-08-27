<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EventAttendee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventHistoryController extends Controller
{
    /**
     * Get Event History for Authenticated User
     * Returns past events that the user has joined
     * 
     * GET /api/events/history
     */
    public function getEventHistory(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        try {
            $userId = $request->user()->id;
            $limit = $request->get('limit', 20);
            $offset = $request->get('offset', 0);
            
            // Get past events that the user joined
            $eventAttendees = EventAttendee::with([
                'event' => function($query) {
                    $query->past() // Only past events
                          ->published() // Only published events
                          ->with([
                              'host:id,name',
                              'suggestedLocation:id,name,description,category',
                              'suggestedLocation.primaryImage:id,suggested_location_id,image_url,is_primary'
                          ]);
                }
            ])
            ->where('user_id', $userId)
            ->whereIn('status', ['interested', 'confirmed']) // User actually joined
            ->whereHas('event', function($query) {
                $query->past()->published(); // Ensure event is past and was published
            })
            ->orderByDesc('created_at') 
            ->offset($offset)
            ->limit($limit)
            ->get();

            $totalCount = EventAttendee::where('user_id', $userId)
                ->whereIn('status', ['interested', 'confirmed'])
                ->whereHas('event', function($query) {
                    $query->past()->published();
                })
                ->count();

            // Format events for mobile display
            $events = $this->formatEventHistoryForMobile($eventAttendees);

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $events,
                    'total_count' => $totalCount,
                    'has_more' => ($offset + $limit) < $totalCount,
                    'pagination' => [
                        'current_page' => floor($offset / $limit) + 1,
                        'per_page' => $limit,
                        'total_pages' => ceil($totalCount / $limit)
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch event history: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Format event history for mobile display - SIMPLIFIED
     */
    private function formatEventHistoryForMobile($eventAttendees): array
    {
        return $eventAttendees->filter(function ($attendee) {
            return $attendee->event !== null; // Filter out null events
        })->map(function ($attendee) {
            $event = $attendee->event;

            return [
                'id' => $event->id,
                'name' => $event->name,
                'event_type' => $this->getEventType($event),
                'date' => Carbon::parse($event->event_date)->format('M j, Y'),
                'event_time' => Carbon::parse($event->event_time)->format('H i A'),
                'venue_address' => $event->venue_address ?? 'Location not specified',
                'image_url' => $this->getEventImage($event)
            ];
        })->values()->toArray();
    }

    /**
     * Get event type display text
     */
    private function getEventType(object $event): string
    {
        $groupSize = $event->min_group_size ?? $event->max_group_size ?? 0;
        
        if ($groupSize <= 2) {
            return 'One-on-One Event';
        }
        
        return 'Group Event';
    }

    /**
     * Get event image from suggested location
     */
    private function getEventImage(object $event): ?string
    {
        // Check if SuggestedLocation primary image is loaded
        if (isset($event->suggestedLocation) && $event->suggestedLocation->primaryImage) {
            return $event->suggestedLocation->primaryImage->image_url;
        }
        
        return null; // No image available
    }
}
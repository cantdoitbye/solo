<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyEventsController extends Controller
{
    /**
     * Get My Events (Events hosted by the authenticated user)
     * Supports date filtering: today, tomorrow, or specific date
     * 
     * GET /api/my-events
     */
    public function getMyEvents(Request $request): JsonResponse
    {
        $request->validate([
            'date_filter' => 'nullable|string|in:today,tomorrow,custom',
            'date' => 'nullable|date|date_format:Y-m-d', // For specific date
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        try {
            $userId = $request->user()->id;
            $dateFilter = $request->get('date_filter', 'today'); // Default to today
            $customDate = $request->get('date');
            $limit = $request->get('limit', 50);
            $offset = $request->get('offset', 0);
            
            // Determine the target date
            $targetDate = $this->getTargetDate($dateFilter, $customDate);
            
            // Get events hosted by user for the target date
            $events = Event::published()
                ->whereDate('event_date', $targetDate)
                ->where(function($query) use ($userId) {
    $query->where('host_id', $userId)
          ->orWhereHas('attendees', function($subQuery) use ($userId) {
              $subQuery->where('user_id', $userId)
                       ->whereIn('status', ['interested', 'confirmed']);
          });
})
                ->with([
                    'host:id,name', // Include host information
                    'suggestedLocation:id,name,description,category',
                    'suggestedLocation.primaryImage:id,suggested_location_id,image_url,is_primary'
                ])
                ->withCount(['attendees as confirmed_count' => function($query) {
                    $query->whereIn('status', ['interested', 'confirmed']);
                }])
                ->orderBy('event_time', 'asc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            // Get total count for pagination
            $totalCount = Event::published()
                // ->where('host_id', $userId)
                ->whereDate('event_date', $targetDate)
                ->where(function($query) use ($userId) {
    $query->where('host_id', $userId)           // User is host
          ->orWhereHas('attendees', function($subQuery) use ($userId) {
              $subQuery->where('user_id', $userId)      // User is attendee
                       ->whereIn('status', ['interested', 'confirmed']);
          });
})
                ->count();

            // Format events for mobile display
            $formattedEvents = $this->formatMyEventsForMobile($events);

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $formattedEvents,
                    'total_count' => $totalCount,
                    'has_more' => ($offset + $limit) < $totalCount,
                    'date_filter' => $dateFilter,
                    'target_date' => Carbon::parse($targetDate)->format('M j, Y'),
                    'target_date_formatted' => $this->getDateDisplayText($dateFilter, $targetDate)
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch your events: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get target date based on filter
     */
    private function getTargetDate(string $dateFilter, ?string $customDate): string
    {
        switch ($dateFilter) {
            case 'today':
                return Carbon::today()->format('Y-m-d');
                
            case 'tomorrow':
                return Carbon::tomorrow()->format('Y-m-d');
                
            case 'custom':
                if ($customDate) {
                    return $customDate;
                }
                // Fallback to today if custom date not provided
                return Carbon::today()->format('Y-m-d');
                
            default:
                return Carbon::today()->format('Y-m-d');
        }
    }

    /**
     * Get date display text for UI
     */
    private function getDateDisplayText(string $dateFilter, string $targetDate): string
    {
        $date = Carbon::parse($targetDate);
        
        switch ($dateFilter) {
            case 'today':
                return "Today's Events";
                
            case 'tomorrow':
                return "Tomorrow's Events";
                
            case 'custom':
            default:
                if ($date->isToday()) {
                    return "Today's Events";
                } elseif ($date->isTomorrow()) {
                    return "Tomorrow's Events";
                } else {
                    return $date->format('D, M j') . " Events";
                }
        }
    }

    /**
     * Format my events for mobile display - matching the screen design
     */
    private function formatMyEventsForMobile($events): array
    {
        return $events->map(function ($event) {
            return [
                'id' => $event->id,
                'name' => $event->name,
                'time' => Carbon::parse($event->event_time)->format('H:i'), // 09:00 AM format
                'location' => $event->venue_name ?? 'Location not specified',
                'image_url' => $this->getEventImage($event),
                'attendees_count' => $event->confirmed_count ?? 0,
                'group_size' => $event->min_group_size,
                'status' => $this->getEventStatus($event),
                'attendees_info' => [
                    'confirmed' => $event->confirmed_count ?? 0,
                    'capacity' => $event->min_group_size,
                    'available_spots' => max(0, $event->min_group_size - ($event->confirmed_count ?? 0))
                ],
                'event_type' => 'Group Event', // All events are group events now
                'is_upcoming' => $event->event_date >= Carbon::today(),
                'host_name' => $event->host->name ?? 'Unknown Host' // Who created this event
            ];
        })->toArray();
    }

    /**
     * Get event status for display
     */
    private function getEventStatus(object $event): string
    {
        $confirmedCount = $event->confirmed_count ?? 0;
        $capacity = $event->min_group_size;
        
        if ($confirmedCount >= $capacity) {
            return 'Full';
        } elseif ($confirmedCount > 0) {
            return 'Partially Filled';
        } else {
            return 'No Attendees';
        }
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
        
        return null; // No image available - will show default avatar
    }
}
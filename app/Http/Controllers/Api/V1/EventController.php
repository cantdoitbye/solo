<?php
// app/Http/Controllers/Api/EventController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
  public function __construct(
        private EventService $eventService
    ) {}

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            
            // Date & Time
            'event_date' => 'required|date|after:today',
            'event_time' => 'required|date_format:H:i',
            'timezone' => 'nullable|string|max:50',
            
            // Venue & Location (Google Maps Integration)
            'venue_type_id' => 'required|integer|exists:venue_types,id',
            'venue_category_id' => 'required|integer|exists:venue_categories,id',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string|max:500',
            'google_place_id' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'google_place_details' => 'nullable|array',
            
            // Attendees Setup
            'min_group_size' => 'required|integer|min:2|max:1000',
            'max_group_size' => 'nullable|integer|min:2|max:1000|gte:min_group_size',
            'gender_rule_enabled' => 'boolean',
            'gender_composition' => 'nullable|string|max:500',
            'min_age' => 'required|integer|min:18|max:100',
            'max_age' => 'required|integer|min:18|max:100|gte:min_age',
            'allowed_genders' => 'nullable|array',
            'allowed_genders.*' => Rule::in(['male', 'female', 'gay', 'lesbian', 'trans', 'bisexual']),
            
            // Token & Payment
            'token_cost_per_attendee' => 'required|numeric|min:0|max:1000',
            
            // Event History & Media
            'past_event_description' => 'nullable|string|max:2000',
            'media_session_id' => 'nullable|string|uuid', // Session ID for pre-uploaded media
            
            // Host Responsibilities
            'cancellation_policy' => 'required|string|in:no_refund,partial_refund,full_refund',
            'host_responsibilities_accepted' => 'required|boolean|accepted'
        ]);

        try {
            $data = $request->all();
            $data['host_id'] = $request->user()->id;
            
            $result = $this->eventService->createEvent($data, $request->media_session_id);

            return response()->json([
                'success' => true,
                'data' => $result
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:2000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            
            // Date & Time (can only be updated for draft events)
            'event_date' => 'sometimes|date|after:today',
            'event_time' => 'sometimes|date_format:H:i',
            
            // Venue & Location
            'venue_type_id' => 'sometimes|integer|exists:venue_types,id',
            'venue_category_id' => 'sometimes|integer|exists:venue_categories,id',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            
            // Attendees Setup
            'min_group_size' => 'sometimes|integer|min:2|max:1000',
            'max_group_size' => 'nullable|integer|min:2|max:1000',
            'gender_rule_enabled' => 'boolean',
            'gender_composition' => 'nullable|string|max:500',
            'min_age' => 'sometimes|integer|min:18|max:100',
            'max_age' => 'sometimes|integer|min:18|max:100',
            'allowed_genders' => 'nullable|array',
            'allowed_genders.*' => Rule::in(['male', 'female', 'gay', 'lesbian', 'trans', 'bisexual']),
            
            // Token & Payment
            'token_cost_per_attendee' => 'sometimes|numeric|min:0|max:1000',
            
            // Event History & Media
            'past_event_description' => 'nullable|string|max:2000',
            
            // Host Responsibilities
            'cancellation_policy' => 'sometimes|string|in:no_refund,partial_refund,full_refund',
        ]);

        try {
            $result = $this->eventService->updateEvent(
                $eventId,
                $request->user()->id,
                $request->all()
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

    public function show(Request $request, int $eventId): JsonResponse
    {
        try {
            $hostId = $request->user()->id;
            $event = $this->eventService->getEventDetails($eventId, $hostId);

            return response()->json([
                'success' => true,
                'data' => $event
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    // Location search endpoints for Google Maps integration
    public function searchPlaces(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|max:255',
            'location' => 'nullable|string|max:255' // lat,lng for bias
        ]);

        try {
            $result = $this->eventService->searchPlaces($request->query, $request->location);

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

    public function getPlaceDetails(Request $request): JsonResponse
    {
        $request->validate([
            'place_id' => 'required|string|max:255'
        ]);

        try {
            $result = $this->eventService->getPlaceDetails($request->place_id);

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


    public function publish(Request $request, int $eventId): JsonResponse
    {
        try {
            $result = $this->eventService->publishEvent($eventId, $request->user()->id);

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

    public function cancel(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'cancellation_reason' => 'nullable|string|max:500'
        ]);

        try {
            $result = $this->eventService->cancelEvent(
                $eventId,
                $request->user()->id,
                $request->cancellation_reason
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

    public function myEvents(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string|in:draft,published,cancelled,completed'
        ]);

        try {
            $events = $this->eventService->getHostEvents(
                $request->user()->id,
                $request->status
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $events,
                    'total_count' => count($events)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function uploadMedia(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'media' => 'required|array|max:10', // Maximum 10 files at once
            'media.*' => 'required|file|mimes:jpeg,png,webp,mp4,mov|max:51200' // 50MB max
        ]);

        try {
            $result = $this->eventService->uploadEventMedia(
                $eventId,
                $request->user()->id,
                $request->file('media')
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

    public function uploadItinerary(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'itinerary' => 'required|file|mimes:pdf,doc,docx|max:10240' // 10MB max
        ]);

        try {
            $result = $this->eventService->uploadItinerary(
                $eventId,
                $request->user()->id,
                $request->file('itinerary')
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

    // Public methods for browsing events (no authentication required)
    public function browse(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'venue_type_id' => 'nullable|integer|exists:venue_types,id',
            'venue_category_id' => 'nullable|integer|exists:venue_categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'min_spots' => 'nullable|integer|min:1',
            'age' => 'nullable|integer|min:18|max:100',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $filters = $request->only([
                'start_date', 'end_date', 'city', 'state',
                'venue_type_id', 'venue_category_id', 'tags',
                'min_spots', 'age'
            ]);

            if ($request->has('limit')) {
                $events = $this->eventService->getUpcomingEvents($request->limit);
            } else {
                $events = $this->eventService->searchEvents($filters);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $events,
                    'total_count' => count($events),
                    'filters_applied' => array_filter($filters)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function showPublic(int $eventId): JsonResponse
    {
        try {
            $event = $this->eventService->getEventDetails($eventId);

            // Only return published events for public view
            if ($event['status'] !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $event
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }
    }
}

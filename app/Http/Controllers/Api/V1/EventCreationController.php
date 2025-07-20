<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\EventCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventCreationController extends Controller
{
    private EventCreationService $eventCreationService;

    public function __construct(EventCreationService $eventCreationService)
    {
        $this->eventCreationService = $eventCreationService;
    }

  /**
 * Combined API for Steps 1-5 (Create OR Edit)
 */
public function createEventBulk(Request $request, ?int $eventId = null): JsonResponse
{
  $request->validate([
    // Step 1
    'name' => 'required|string|max:255',
    'description' => 'required|string|max:2000',
    'tags' => 'nullable|array',
    'tags.*' => 'string|max:50',
    
    // Step 2 (ALL LOCATION FIELDS)
        'venue_type' => 'required|boolean',
    // 'venue_type_id' => 'required|integer|exists:venue_types,id',
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
    
    // Step 3
    'event_date' => 'required|date|after:today',
    'event_time' => 'required|date_format:H:i',
    'timezone' => 'nullable|string|max:50',
    
    // Step 4
    'min_group_size' => 'required|integer|min:1|max:1000',
    // 'max_group_size' => 'nullable|integer|min:2|max:1000|gte:min_group_size',
    'min_age' => 'required|integer|min:18|max:100',
    'max_age' => 'required|integer|min:18|max:100|gte:min_age',
    'gender_rule_enabled' => 'nullable|boolean',
 
    'allowed_genders' => 'nullable|array',
    'allowed_genders.*' => Rule::in(['male', 'female', 'gay', 'lesbian', 'trans', 'bisexual']),
    
    // Step 5
    'token_cost_per_attendee' => 'required|numeric|min:0|max:1000'
]);


    try {
        $result = $this->eventCreationService->createEventBulk(
            $request->user()->id,
            $request->all(),
            $eventId  // <-- Add this parameter
        );

        return response()->json(['success' => true, 'data' => $result]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

    /**
     * Step 1: Handle Basic Info (Create or Edit)
     */
    public function handleBasicInfo(Request $request, ?int $eventId = null): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ]);

        try {
            $result = $this->eventCreationService->handleBasicInfo(
                $request->user()->id,
                $request->all(),
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
     * Step 2: Handle Venue & Location
     */
    public function handleVenueLocation(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
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
            'google_place_details' => 'nullable|array'
        ]);

        try {
            $result = $this->eventCreationService->handleVenueLocation(
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

    /**
     * Step 3: Handle Date & Time
     */
    public function handleDateTime(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'event_date' => 'required|date|after:today',
            'event_time' => 'required|date_format:H:i',
            'timezone' => 'nullable|string|max:50'
        ]);

        try {
            $result = $this->eventCreationService->handleDateTime(
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

    /**
     * Step 4: Handle Attendees Setup with Gender Logic
     */
    public function handleAttendeesSetup(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'min_group_size' => 'required|integer|min:2|max:1000',
            'max_group_size' => 'nullable|integer|min:2|max:1000|gte:min_group_size',
            'min_age' => 'required|integer|min:18|max:100',
            'max_age' => 'required|integer|min:18|max:100|gte:min_age',
            'gender_rule_enabled' => 'nullable|boolean', 
            'allowed_genders' => 'nullable|array',
            'allowed_genders.*' => Rule::in(['male', 'female', 'gay', 'lesbian', 'trans', 'bisexual'])
        ]);

        try {
            $result = $this->eventCreationService->handleAttendeesSetup(
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

    /**
     * Step 5: Handle Token & Payment
     */
    public function handleTokenPayment(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'token_cost_per_attendee' => 'required|numeric|min:0|max:1000'
        ]);

        try {
            $result = $this->eventCreationService->handleTokenPayment(
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

    /**
     * Step 6: Handle Event History & Media
     */
    public function handleEventHistory(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'past_event_description' => 'nullable|string|max:2000',
            'media_session_id' => 'nullable|string'
        ]);

        try {

            
            $result = $this->eventCreationService->handleEventHistory(
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

    /**
     * Step 7: Handle Host Responsibilities
     */
    public function handleHostResponsibilities(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'cancellation_policy' => 'required|string|in:no_refund,partial_refund,full_refund',
            'host_responsibilities_accepted' => 'required|boolean',
            'itinerary_session_id' => 'nullable|string'
        ]);

        try {
            $result = $this->eventCreationService->handleHostResponsibilities(
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

    /**
     * Step 8: Generate Preview
     */
    public function generatePreview(Request $request, int $eventId): JsonResponse
    {
        try {
            $result = $this->eventCreationService->generatePreview(
                $eventId,
                $request->user()->id
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
     * Final Step: Publish Event
     */
    public function publishEvent(Request $request, int $eventId): JsonResponse
    {
        try {
            $result = $this->eventCreationService->publishEvent(
                $eventId,
                $request->user()->id
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
     * Get Event Progress and Data
     */
    public function getEventProgress(Request $request, int $eventId): JsonResponse
    {
        try {
            $result = $this->eventCreationService->getEventProgress(
                $eventId,
                $request->user()->id
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
     * Delete Draft Event
     */
    public function deleteDraftEvent(Request $request, int $eventId): JsonResponse
    {
        try {
            $result = $this->eventCreationService->deleteDraftEvent(
                $eventId,
                $request->user()->id
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
}
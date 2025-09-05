<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\EventCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventCreationController extends Controller
{
    private EventCreationService $eventCreationService;

    public function __construct(EventCreationService $eventCreationService)
    {
        $this->eventCreationService = $eventCreationService;
    }

    /**
     * Single API to create complete event (using SuggestedLocation)
     * POST /api/v1/events/create
     */
    public function createEvent(Request $request): JsonResponse
    {
        $request->validate([
            // Basic Info
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'group_size' => 'required|integer|min:2|max:20',
            
            'location_id' => 'required|integer|exists:suggested_locations,id',
            
            // Date & Time
            'event_date' => 'required|date|after:today',
            'event_time' => 'required|date_format:H:i',
            'timezone' => 'nullable|string|max:50',
            
            // Gender Balance Settings
            'gender_balanced' => 'nullable|boolean',
            
            // Optional settings
            'disable_age_restriction' => 'nullable|boolean',
            'cancellation_policy' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->eventCreationService->createCompleteEvent(
                $request->user()->id,
                $request->all()
            );

            return response()->json([
                'success' => true, 
                'data' => $result,
                'message' => 'Event created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Publish event immediately after creation
     * POST /api/v1/events/{eventId}/publish
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
                'data' => $result,
                'message' => 'Event published successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete draft event
     * DELETE /api/v1/events/{eventId}/draft
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
                'data' => $result,
                'message' => 'Event deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
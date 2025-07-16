<?php


// app/Http/Controllers/Api/EventDataController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventDataController extends Controller
{
   public function __construct(
        private EventService $eventService
    ) {}

    public function getVenueTypes(): JsonResponse
    {
        try {
            $venueTypes = $this->eventService->getVenueTypes();

            return response()->json([
                'success' => true,
                'data' => [
                    'venue_types' => $venueTypes,
                    'total_count' => count($venueTypes)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getVenueCategories(Request $request): JsonResponse
    {
        $request->validate([
            'venue_type_id' => 'nullable|integer|exists:venue_types,id'
        ]);

        try {
            $venueCategories = $this->eventService->getVenueCategories($request->venue_type_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'venue_categories' => $venueCategories,
                    'total_count' => count($venueCategories),
                    'filtered_by_venue_type' => $request->venue_type_id ? true : false
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getEventTags(): JsonResponse
    {
        try {
            $tags = $this->eventService->getEventTags();

            return response()->json([
                'success' => true,
                'data' => [
                    'featured_tags' => $tags['featured'],
                    'categories' => $tags['categories'],
                    'all_tags' => $tags['all_tags'],
                    'total_count' => count($tags['all_tags'])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getGenderOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'gender_options' => [
                    ['value' => 'male', 'label' => 'Male'],
                    ['value' => 'female', 'label' => 'Female'],
                    ['value' => 'gay', 'label' => 'Gay'],
                    ['value' => 'lesbian', 'label' => 'Lesbian'],
                    ['value' => 'trans', 'label' => 'Trans'],
                    ['value' => 'bisexual', 'label' => 'Bisexual']
                ],
                'cancellation_policies' => [
                    ['value' => 'no_refund', 'label' => 'No Refund'],
                    ['value' => 'partial_refund', 'label' => 'Partial Refund'],
                    ['value' => 'full_refund', 'label' => 'Full Refund']
                ]
            ]
        ]);
    }
}
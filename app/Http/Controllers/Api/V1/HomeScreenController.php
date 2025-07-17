<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\HomeScreenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeScreenController extends Controller
{
  private HomeScreenService $homeScreenService;

    public function __construct(HomeScreenService $homeScreenService)
    {
        $this->homeScreenService = $homeScreenService;
    }

    /**
     * Get Home Screen Data with Dynamic Categories
     */
    public function getHomeScreen(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $result = $this->homeScreenService->getHomeScreenData($userId);

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
     * Get Events by Category ID (Dynamic)
     */
    public function getEventsByCategoryId(Request $request, int $categoryId): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        try {
            $userId = $request->user()->id;
            $limit = $request->get('limit', 10);
            $offset = $request->get('offset', 0);
            
            $result = $this->homeScreenService->getEventsByCategoryId(
                $categoryId, 
                $userId, 
                $limit, 
                $offset
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
     * Apply Filters to Events (Updated for Categories)
     */
    public function applyFilters(Request $request): JsonResponse
    {
        $request->validate([
            'event_type' => 'nullable|string|in:one-on-one,group',
            'venue_category_ids' => 'nullable|array',
            'venue_category_ids.*' => 'integer|exists:venue_categories,id',
            'select_sex' => 'nullable|array',
            'select_sex.*' => 'string|in:male,female,gay,lesbian,trans,bisexual',
            'age_min' => 'nullable|integer|min:15|max:100',
            'age_max' => 'nullable|integer|min:15|max:100',
            'area_radius_min' => 'nullable|integer|min:0|max:100',
            'area_radius_max' => 'nullable|integer|min:0|max:100',
            'time_start' => 'nullable|date_format:H:i',
            'time_end' => 'nullable|date_format:H:i',
            'event_spot' => 'nullable|string|in:indoor,outdoor',
            'pet_friendly' => 'nullable|boolean',
            'select_gender' => 'nullable|array',
            'select_gender.*' => 'string|in:male,female,other',
            'accessibility' => 'nullable|array',
            'accessibility.*' => 'string|in:wheelchair_accessible,step_free_entrance',
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        try {
            $userId = $request->user()->id;
            $filters = $request->all();
            
            $result = $this->homeScreenService->applyFilters($filters, $userId);

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
     * Search Events
     */
    public function searchEvents(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        try {
            $userId = $request->user()->id;
            $query = $request->get('query');
            $limit = $request->get('limit', 10);
            $offset = $request->get('offset', 0);
            
            $result = $this->homeScreenService->searchEvents($query, $userId, $limit, $offset);

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

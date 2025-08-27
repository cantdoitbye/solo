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
     * Get Home Screen Data (Simplified)
     */
   public function getHomeScreen(Request $request): JsonResponse
    {
        $request->validate([
            'date_filter' => 'nullable|string|in:today,tomorrow,custom',
            'date' => 'nullable|date|date_format:Y-m-d',
            'start_date' => 'nullable|date|date_format:Y-m-d',
            'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        try {
            $userId = $request->user()->id;
            
            // Prepare date filter parameters
            $filters = [
                'date_filter' => $request->get('date_filter', 'today'), // Default to today
                'date' => $request->get('date'),
                'start_date' => $request->get('start_date'),
                'end_date' => $request->get('end_date'),
                'limit' => $request->get('limit', 20),
                'offset' => $request->get('offset', 0)
            ];
            
            $result = $this->homeScreenService->getHomeScreenData($userId, $filters);

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
     * Apply Filters to Events (Simplified - No Venue Categories)
     */
    public function applyFilters(Request $request): JsonResponse
    {
        $request->validate([
            'event_type' => 'nullable|string|in:one-on-one,group',
            'age_min' => 'nullable|integer|min:18|max:100',
            'age_max' => 'nullable|integer|min:18|max:100',
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
     * Search Events (Simplified)
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
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SuggestedLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuggestedLocationsController extends Controller
{
    /**
     * Get all suggested locations
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $query = SuggestedLocation::active()->ordered();

            // Filter by category if provided
            if ($request->filled('category')) {
                $query->byCategory($request->category);
            }

            // Apply limit if provided
            if ($request->filled('limit')) {
                $query->limit($request->limit);
            }

            $suggestedLocations = $query->get();

            // Transform the data to match your existing event location structure
            $transformedLocations = $suggestedLocations->map(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'description' => $location->description,
                    'google_maps_url' => $location->google_maps_url,
                    
                    // Location data compatible with event creation
                    'venue_name' => $location->venue_name,
                    'venue_address' => $location->venue_address,
                    'google_place_id' => $location->google_place_id,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'city' => $location->city,
                    'state' => $location->state,
                    'country' => $location->country,
                    'postal_code' => $location->postal_code,
                    'google_place_details' => $location->google_place_details,
                    
                    // Additional metadata
                    'category' => $location->category,
                    'image_url' => $location->image_url,
                    'sort_order' => $location->sort_order,
                    'created_at' => $location->created_at,
                    'updated_at' => $location->updated_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'suggested_locations' => $transformedLocations,
                    'total_count' => $transformedLocations->count(),
                    'filtered_by_category' => $request->category ?? null
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suggested locations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get suggested locations by category
     */
    public function getByCategory(Request $request, string $category): JsonResponse
    {
        try {
            $suggestedLocations = SuggestedLocation::active()
                ->byCategory($category)
                ->ordered()
                ->get();

            $transformedLocations = $suggestedLocations->map(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'description' => $location->description,
                    'google_maps_url' => $location->google_maps_url,
                    'venue_name' => $location->venue_name,
                    'venue_address' => $location->venue_address,
                    'google_place_id' => $location->google_place_id,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'city' => $location->city,
                    'state' => $location->state,
                    'country' => $location->country,
                    'postal_code' => $location->postal_code,
                    'google_place_details' => $location->google_place_details,
                    'category' => $location->category,
                    'image_url' => $location->image_url
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'suggested_locations' => $transformedLocations,
                    'total_count' => $transformedLocations->count(),
                    'category' => $category
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suggested locations for category: ' . $e->getMessage(),
            ], 500);
        }
    }
}
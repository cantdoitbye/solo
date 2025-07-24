<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OneOnOneDateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OneOnOneDateController extends Controller
{
    public function __construct(
        private OneOnOneDateService $oneOnOneDateService
    ) {}

      /**
     * Get 1:1 Date Details by ID
     */
    public function getOneOnOneDateById(Request $request, int $dateId): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $result = $this->oneOnOneDateService->getOneOnOneDateById($dateId, $userId);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }
    /**
     * Create 1:1 Date with Media Upload
     */
    public function createOneOnOneDate(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'event_date' => 'required|date|after:today',
            'event_time' => 'required|date_format:H:i',
            'timezone' => 'nullable|string|max:50',
            // Google Maps location data
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string|max:500',
            'google_place_id' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'google_place_details' => 'nullable|json',
            'token_cost' => 'nullable|numeric|min:0',
            'request_approval' => 'nullable|boolean',
            // Media validation
            'media' => 'nullable|array|max:10',
            'media.*' => 'file|mimes:jpeg,jpg,png,gif,bmp,webp,svg,mp4,avi,mov,wmv,flv,webm,mkv,m4v,3gp|max:10240'
        ], [
            'venue_address.required' => 'Location is required',
            'google_place_details.json' => 'Invalid Google place data format',
            'media.max' => 'You can upload a maximum of 10 media files.',
            'media.*.file' => 'Each upload must be a valid file.',
            'media.*.mimes' => 'Only images and videos are allowed.',
            'media.*.max' => 'Each file size cannot exceed 10MB.'
        ]);

        try {
            $validatedData = $request->only([
                'name', 'description', 'event_date', 'event_time', 'timezone',
                'venue_name', 'venue_address', 'google_place_id', 'latitude', 
                'longitude', 'city', 'state', 'country', 'postal_code', 
                'google_place_details', 'token_cost', 'request_approval'
            ]);

            $result = $this->oneOnOneDateService->createOneOnOneDateWithMedia(
                $request->user()->id,
                $validatedData,
                $request->file('media') ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Date Request Sent Successfully',
                'data' => $result
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

     /**
     * Book 1:1 Date
     */
    public function bookOneOnOneDate(Request $request, int $dateId): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $result = $this->oneOnOneDateService->bookOneOnOneDate($dateId, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Date Booked Successfully!',
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
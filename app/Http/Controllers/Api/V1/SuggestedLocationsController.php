<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SuggestedLocation;
use App\Models\LocationImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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

            $suggestedLocations = $query->with(['primaryImage', 'images'])->get();

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
                    'image_url' => $location->primaryImage ? $location->primaryImage->image_url : $location->image_url,
                    'images' => $location->images->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'url' => $image->image_url,
                            'is_primary' => $image->is_primary
                        ];
                    }),
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
     * Create a new suggested location
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'google_maps_url' => 'required|string',
    'google_place_id' => 'nullable|string|unique:suggested_locations,google_place_id',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'google_place_details' => 'nullable|array',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean'
        ]);

        try {
            DB::beginTransaction();

            // Create the suggested location
            $suggestedLocation = SuggestedLocation::create([
                'name' => $request->name,
                'description' => $request->description,
                'google_maps_url' => $request->google_maps_url,
                'google_place_id' => $request->google_place_id,
                'venue_name' => $request->venue_name,
                'venue_address' => $request->venue_address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'postal_code' => $request->postal_code,
                'google_place_details' => $request->google_place_details,
                'category' => $request->category,
                'sort_order' => $request->sort_order ?? 0,
                'is_active' => $request->is_active ?? true
            ]);

            // Get a random image from public/locationimages
            $randomImageData = $this->getRandomLocationImage();
            
            if ($randomImageData) {
                // Create LocationImage record
                LocationImage::create([
                    'suggested_location_id' => $suggestedLocation->id,
                    'image_path' => $randomImageData['path'],
                    'image_url' => $randomImageData['url'],
                    'original_filename' => $randomImageData['filename'],
                    'file_size' => $randomImageData['size'],
                    'mime_type' => $randomImageData['mime_type'],
                    'width' => $randomImageData['width'],
                    'height' => $randomImageData['height'],
                    'is_primary' => true,
                    'sort_order' => 1
                ]);
            }

            DB::commit();

            // Load the created location with its primary image
            $suggestedLocation->load(['primaryImage', 'images']);

            return response()->json([
                'success' => true,
                'data' => [
                    'suggested_location' => [
                        'id' => $suggestedLocation->id,
                        'name' => $suggestedLocation->name,
                        'description' => $suggestedLocation->description,
                        'google_maps_url' => $suggestedLocation->google_maps_url,
                        'venue_name' => $suggestedLocation->venue_name,
                        'venue_address' => $suggestedLocation->venue_address,
                        'google_place_id' => $suggestedLocation->google_place_id,
                        'latitude' => $suggestedLocation->latitude,
                        'longitude' => $suggestedLocation->longitude,
                        'city' => $suggestedLocation->city,
                        'state' => $suggestedLocation->state,
                        'country' => $suggestedLocation->country,
                        'postal_code' => $suggestedLocation->postal_code,
                        'google_place_details' => $suggestedLocation->google_place_details,
                        'category' => $suggestedLocation->category,
                        'image_url' => $suggestedLocation->primaryImage ? $suggestedLocation->primaryImage->image_url : null,
                        'images' => $suggestedLocation->images->map(function ($image) {
                            return [
                                'id' => $image->id,
                                'url' => $image->image_url,
                                'is_primary' => $image->is_primary
                            ];
                        }),
                        'sort_order' => $suggestedLocation->sort_order,
                        'is_active' => $suggestedLocation->is_active,
                        'created_at' => $suggestedLocation->created_at,
                        'updated_at' => $suggestedLocation->updated_at
                    ]
                ],
                'message' => 'Suggested location created successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create suggested location: ' . $e->getMessage(),
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
                ->with(['primaryImage', 'images'])
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
                    'image_url' => $location->primaryImage ? $location->primaryImage->image_url : $location->image_url,
                    'images' => $location->images->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'url' => $image->image_url,
                            'is_primary' => $image->is_primary
                        ];
                    })
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

    /**
     * Get a random image from public/locationimages directory
     */
    private function getRandomLocationImage(): ?array
    {
        try {
            $locationImagesPath = public_path('locationimages');
            
            if (!File::exists($locationImagesPath)) {
                \Log::warning('Location images directory does not exist: ' . $locationImagesPath);
                return null;
            }

            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $imageFiles = [];

            foreach ($imageExtensions as $extension) {
                $files = File::glob($locationImagesPath . '/*.' . $extension);
                $imageFiles = array_merge($imageFiles, $files);
            }

            if (empty($imageFiles)) {
                \Log::warning('No images found in locationimages directory');
                return null;
            }

            // Get a random image
            $randomImagePath = $imageFiles[array_rand($imageFiles)];
            $filename = basename($randomImagePath);
            $relativePath = 'locationimages/' . $filename;

            // Get image dimensions and file info
            $imageInfo = getimagesize($randomImagePath);
            $fileSize = filesize($randomImagePath);
            $mimeType = $imageInfo['mime'] ?? mime_content_type($randomImagePath);

            return [
                'path' => $relativePath,
                'url' => $relativePath,
                'filename' => $filename,
                'size' => $fileSize,
                'mime_type' => $mimeType,
                'width' => $imageInfo[0] ?? null,
                'height' => $imageInfo[1] ?? null
            ];

        } catch (\Exception $e) {
            \Log::error('Error getting random location image: ' . $e->getMessage());
            return null;
        }
    }
}
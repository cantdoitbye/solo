<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EventMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventMediaController extends Controller
{
   public function __construct(
        private EventMediaService $mediaService
    ) {}

    /**
     * Upload media files
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'file|mimes:jpeg,png,gif,mp4,avi,mov|max:51200', // 50MB
            'session_id' => 'nullable|string'
        ]);

        try {
            $result = $this->mediaService->uploadMedia(
                $request->user()->id,
                $request->file('files'),
                $request->session_id
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
     * Upload itinerary file
     */
   public function uploadItinerary(Request $request): JsonResponse
{
    $request->validate([
        'files' => 'required|array',
        'files.*' => 'file|mimes:pdf,doc,docx,txt,odt,jpeg,jpg,png,gif,webp,bmp,svg|max:10240', // 10MB per file
        'session_id' => 'nullable|string'
    ], [
        'files.required' => 'At least one itinerary file is required.',
        'files.array' => 'Files must be uploaded as an array.',
        'files.max' => 'You can upload a maximum of 5 itinerary files.',
        'files.*.file' => 'Each upload must be a valid file.',
        'files.*.mimes' => 'Only PDF, Word documents, text files, ODT files, and images (JPEG, PNG, GIF, WebP, BMP, SVG) are allowed.',
        'files.*.max' => 'Each file size cannot exceed 10MB.'
    ]);

    try {
        $result = $this->mediaService->uploadItinerary(
            $request->user()->id,
            $request->file('files'),
            $request->session_id
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
     * Get session media
     */
    public function getSessionMedia(Request $request, string $sessionId): JsonResponse
    {
        try {
            $result = $this->mediaService->getSessionMedia($sessionId);

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
     * Delete session media
     */
    public function deleteSessionMedia(Request $request, string $sessionId): JsonResponse
    {
        try {
            $result = $this->mediaService->deleteSessionMedia($sessionId);

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
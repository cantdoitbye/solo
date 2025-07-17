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
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB
            'session_id' => 'nullable|string'
        ]);

        try {
            $result = $this->mediaService->uploadItinerary(
                $request->user()->id,
                $request->file('file'),
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
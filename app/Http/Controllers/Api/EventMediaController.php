<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\EventMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventMediaController extends Controller
{
    public function __construct(
        private EventMediaService $eventMediaService
    ) {}

    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'media' => 'required|array|max:10',
            'media.*' => 'required|file|mimes:jpeg,png,webp,mp4,mov|max:51200', // 50MB max
            'session_id' => 'nullable|string|uuid'
        ]);

        try {
            $sessionId = $request->session_id ?? Str::uuid()->toString();
            
            $result = $this->eventMediaService->uploadMedia(
                $request->user()->id,
                $request->file('media'),
                $sessionId
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

    public function uploadItinerary(Request $request): JsonResponse
    {
        $request->validate([
            'itinerary' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
            'session_id' => 'nullable|string|uuid'
        ]);

        try {
            $sessionId = $request->session_id ?? Str::uuid()->toString();
            
            $result = $this->eventMediaService->uploadItinerary(
                $request->user()->id,
                $request->file('itinerary'),
                $sessionId
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

    public function getSessionMedia(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|uuid'
        ]);

        try {
            $result = $this->eventMediaService->getSessionMedia($request->session_id);

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

    public function deleteSessionMedia(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|uuid'
        ]);

        try {
            $result = $this->eventMediaService->deleteSessionMedia($request->session_id);

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
<?php
// app/Services/EventMediaService.php

namespace App\Services;

use App\Models\EventMedia;
use App\Models\EventItinerary;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class EventMediaService
{
    public function uploadMedia(int $userId, array $files, string $sessionId = null): array
    {
        $sessionId = $sessionId ?? Str::uuid()->toString();
        $uploadedMedia = [];

        foreach ($files as $file) {
            $this->validateMediaFile($file);
            
            $mediaData = $this->processAndStoreMedia($file, $userId, $sessionId);
            $media = EventMedia::create($mediaData);
            
            $uploadedMedia[] = [
                'id' => $media->id,
                'url' => $media->file_url,
                'type' => $media->media_type,
                'filename' => $media->original_filename
            ];
        }

        return [
            'session_id' => $sessionId,
            'uploaded_media' => $uploadedMedia,
            'total_count' => count($uploadedMedia)
        ];
    }

    public function uploadItinerary(int $userId, $file, string $sessionId = null): array
    {
        $sessionId = $sessionId ?? Str::uuid()->toString();
        
        $this->validateItineraryFile($file);
        
        $itineraryData = $this->processAndStoreItinerary($file, $userId, $sessionId);
        $itinerary = EventItinerary::create($itineraryData);

        return [
            'session_id' => $sessionId,
            'itinerary' => [
                'id' => $itinerary->id,
                'url' => $itinerary->file_url,
                'filename' => $itinerary->original_filename
            ]
        ];
    }

    public function attachMediaToEvent(int $eventId, string $sessionId): array
    {
        // Attach all media files from the session to the event
        $mediaFiles = EventMedia::forSession($sessionId)->unattached()->get();
        $itineraryFiles = EventItinerary::forSession($sessionId)->unattached()->get();

        $mediaFiles->each(function ($media) use ($eventId) {
            $media->update([
                'event_id' => $eventId,
                'is_attached_to_event' => true
            ]);
        });

        $itineraryFiles->each(function ($itinerary) use ($eventId) {
            $itinerary->update([
                'event_id' => $eventId,
                'is_attached_to_event' => true
            ]);
        });

        return [
            'attached_media_count' => $mediaFiles->count(),
            'attached_itinerary_count' => $itineraryFiles->count(),
            'media_files' => $mediaFiles->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->file_url,
                    'type' => $media->media_type
                ];
            }),
            'itinerary_files' => $itineraryFiles->map(function ($itinerary) {
                return [
                    'id' => $itinerary->id,
                    'url' => $itinerary->file_url,
                    'filename' => $itinerary->original_filename
                ];
            })
        ];
    }

    public function getSessionMedia(string $sessionId): array
    {
        $mediaFiles = EventMedia::forSession($sessionId)->unattached()->get();
        $itineraryFiles = EventItinerary::forSession($sessionId)->unattached()->get();

        return [
            'session_id' => $sessionId,
            'media_files' => $mediaFiles->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->file_url,
                    'type' => $media->media_type,
                    'filename' => $media->original_filename
                ];
            }),
            'itinerary_files' => $itineraryFiles->map(function ($itinerary) {
                return [
                    'id' => $itinerary->id,
                    'url' => $itinerary->file_url,
                    'filename' => $itinerary->original_filename
                ];
            }),
            'total_media' => $mediaFiles->count(),
            'total_itinerary' => $itineraryFiles->count()
        ];
    }

    public function deleteSessionMedia(string $sessionId): array
    {
        $mediaFiles = EventMedia::forSession($sessionId)->unattached()->get();
        $itineraryFiles = EventItinerary::forSession($sessionId)->unattached()->get();

        // Delete files from storage
        foreach ($mediaFiles as $media) {
            Storage::disk('public')->delete($media->file_path);
            $media->delete();
        }

        foreach ($itineraryFiles as $itinerary) {
            Storage::disk('public')->delete($itinerary->file_path);
            $itinerary->delete();
        }

        return [
            'deleted_media_count' => $mediaFiles->count(),
            'deleted_itinerary_count' => $itineraryFiles->count(),
            'message' => 'Session media cleaned up successfully'
        ];
    }

    private function validateMediaFile($file): void
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/mov', 'video/quicktime'];
        
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid file type. Only images (JPEG, PNG, WebP) and videos (MP4, MOV) are allowed.');
        }

        if ($file->getSize() > 50 * 1024 * 1024) { // 50MB limit
            throw new \Exception('File size too large. Maximum size is 50MB.');
        }
    }

    private function validateItineraryFile($file): void
    {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid file type. Only PDF and Word documents are allowed for itinerary.');
        }

        if ($file->getSize() > 10 * 1024 * 1024) { // 10MB limit
            throw new \Exception('File size too large. Maximum size is 10MB.');
        }
    }

    private function processAndStoreMedia($file, int $userId, string $sessionId): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $storedName = Str::uuid() . '.' . $extension;
        $mediaType = strpos($file->getMimeType(), 'image/') === 0 ? 'image' : 'video';
        
        // Store file
        $path = $file->storeAs('events/media/' . $sessionId, $storedName, 'public');
        $url = Storage::url($path);

        $mediaData = [
            'user_id' => $userId,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $path,
            'file_url' => $url,
            'media_type' => $mediaType,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'upload_session_id' => $sessionId
        ];

        // Get image/video dimensions
        if ($mediaType === 'image') {
            try {
                $dimensions = getimagesize($file->getPathname());
                if ($dimensions) {
                    $mediaData['width'] = $dimensions[0];
                    $mediaData['height'] = $dimensions[1];
                }
            } catch (\Exception $e) {
                // Continue without dimensions if failed
            }
        }

        return $mediaData;
    }

    private function processAndStoreItinerary($file, int $userId, string $sessionId): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $storedName = Str::uuid() . '.' . $extension;
        
        // Store file
        $path = $file->storeAs('events/itinerary/' . $sessionId, $storedName, 'public');
        $url = Storage::url($path);

        return [
            'user_id' => $userId,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $path,
            'file_url' => $url,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'upload_session_id' => $sessionId
        ];
    }
}
<?php

namespace App\Services;

use App\Models\OneOnOneDate;
use App\Models\EventMedia;
use App\Models\OneOnOneDateMedia;
use App\Repositories\Contracts\OneOnOneDateRepositoryInterface;
use Illuminate\Support\Facades\DB;

class OneOnOneDateService
{
    public function __construct(
        private OneOnOneDateRepositoryInterface $repository
    ) {}

    /**
     * Create a new 1:1 date with media upload
     */
    public function createOneOnOneDateWithMedia(int $hostId, array $data, array $mediaFiles = []): array
    {
        return DB::transaction(function () use ($hostId, $data, $mediaFiles) {
            // Set default approval status
            $data['approval_status'] = $data['request_approval'] ?? false 
                ? OneOnOneDate::APPROVAL_PENDING 
                : OneOnOneDate::APPROVAL_APPROVED;

            // Remove media from data array
            unset($data['media']);

            $oneOnOneDate = $this->repository->create(array_merge($data, [
                'host_id' => $hostId
            ]));

            // Upload and attach media files
            $uploadedMedia = [];
            if (!empty($mediaFiles)) {
                $uploadedMedia = $this->uploadAndAttachMedia($oneOnOneDate->id, $hostId, $mediaFiles);
            }

            return [
                'id' => $oneOnOneDate->id,
                'name' => $oneOnOneDate->name,
                'description' => $oneOnOneDate->description,
                'event_date' => $oneOnOneDate->event_date->format('Y-m-d'),
                'event_time' => $oneOnOneDate->event_time->format('H:i'),
                'location' => [
                    'venue_name' => $oneOnOneDate->venue_name,
                    'venue_address' => $oneOnOneDate->venue_address,
                    'google_place_id' => $oneOnOneDate->google_place_id,
                    'latitude' => $oneOnOneDate->latitude,
                    'longitude' => $oneOnOneDate->longitude,
                    'city' => $oneOnOneDate->city,
                    'state' => $oneOnOneDate->state,
                    'country' => $oneOnOneDate->country,
                    'postal_code' => $oneOnOneDate->postal_code,
                    'google_place_details' => $oneOnOneDate->google_place_details
                ],
                'token_cost' => $oneOnOneDate->token_cost,
                'approval_status' => $oneOnOneDate->approval_status,
                'status' => $oneOnOneDate->status,
                'media_count' => count($uploadedMedia),
                'uploaded_media' => $uploadedMedia
            ];
        });
    }

    /**
     * Upload and attach media files to 1:1 date
     */
    private function uploadAndAttachMedia(int $dateId, int $userId, array $mediaFiles): array
    {
        $uploadedMedia = [];

        foreach ($mediaFiles as $file) {
            $this->validateMediaFile($file);
            
            $mediaData = $this->processAndStoreMedia($file, $userId);
            
            // Create OneOnOneDateMedia record
            $media = OneOnOneDateMedia::create(array_merge($mediaData, [
                'one_on_one_date_id' => $dateId,
                'is_attached_to_date' => true
            ]));
            
            $uploadedMedia[] = [
                'id' => $media->id,
                'url' => $media->file_url,
                'type' => $media->media_type,
                'filename' => $media->original_filename,
                'size' => $media->file_size
            ];
        }

        return $uploadedMedia;
    }

    /**
     * Process and store media file
     */
    private function processAndStoreMedia($file, int $userId): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        
        // Generate unique filename
        $storedName = time() . '_' . uniqid() . '.' . $extension;
        
        // Determine media type
        $mediaType = str_starts_with($mimeType, 'image/') ? 'image' : 'video';
        
        // Store file
        $path = $file->storeAs('one-on-one-dates/media', $storedName, 'public');
        // $url = asset('storage/' . $path);
                $fileUrl = 'storage/' . $path;

        
        // Get image/video dimensions if applicable
        $width = null;
        $height = null;
        $duration = null;
        
        if ($mediaType === 'image') {
            $imageInfo = getimagesize($file->getRealPath());
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
        }
        
        return [
            'user_id' => $userId,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $path,
            'file_url' => $fileUrl,
            'media_type' => $mediaType,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'width' => $width,
            'height' => $height,
            'duration' => $duration
        ];
    }

    /**
     * Validate media file
     */
    private function validateMediaFile($file): void
    {
        if (!$file->isValid()) {
            throw new \Exception('Invalid file uploaded');
        }
        
        $allowedMimes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 
            'image/webp', 'image/svg+xml',
            'video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/flv', 
            'video/webm', 'video/mkv', 'video/m4v', 'video/3gp'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('File type not allowed: ' . $file->getMimeType());
        }
        
        if ($file->getSize() > 10485760) { // 10MB
            throw new \Exception('File size too large: ' . $file->getClientOriginalName());
        }
    }
}
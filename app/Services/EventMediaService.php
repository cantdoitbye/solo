<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventMedia;
use App\Models\EventItinerary;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventMediaService
{
    /**
     * Upload media files with session tracking
     */
    public function uploadMedia(int $userId, array $files = [],$eventImage = null, string $sessionId = null): array
    {
        $sessionId = $sessionId ?? Str::uuid()->toString();
        $uploadedMedia = [];
            $event = Event::where('session_id', $sessionId)->first();

             if ($eventImage && $event) {
        try {
            $eventImagePath = $this->handleEventImageUpload($eventImage, $event->id);
            
            // Update the event with the new image path
            $event->update(['event_image' => $eventImagePath]);
            
            \Log::info('Event image updated successfully:', [
                'event_id' => $event->id,
                'image_path' => $eventImagePath
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to update event image:', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
          
        }
    }

        foreach ($files as $file) {

                       
            $this->validateMediaFile($file);


            
            $mediaData = $this->processAndStoreMedia($file, $userId, $sessionId);

             if ($event) {
            $mediaData['event_id'] = $event->id;
            $mediaData['is_attached_to_event'] = true;
        }
            $media = EventMedia::create($mediaData);
            
            $uploadedMedia[] = [
                'id' => $media->id,
                'url' => $media->file_url,
                'type' => $media->media_type,
                'filename' => $media->original_filename,
                'size' => $media->file_size
            ];
        }

        return [
            'session_id' => $sessionId,
            'uploaded_media' => $uploadedMedia,
            'total_count' => count($uploadedMedia),
            'message' => 'Media files uploaded successfully'
        ];
    }

    private function handleEventImageUpload($file, int $eventId): string
{
    try {
        // Get file information BEFORE moving the file
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $extension = $file->getClientOriginalExtension();
        
        // Create event_images directory in public if it doesn't exist
        $publicDir = public_path('event_images');
        if (!file_exists($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        
        // Generate unique filename
        $fileName = 'event_' . $eventId . '_' . uniqid() . '_' . time() . '.' . $extension;
        
        // Full path where file will be stored
        $destinationPath = $publicDir . '/' . $fileName;
        
        // Move file to public/event_images directory
        $file->move($publicDir, $fileName);
        
        // Return relative path for database storage
        $relativePath = 'event_images/' . $fileName;
        
        \Log::info('Event image stored successfully in public folder:', [
            'file_name' => $fileName,
            'relative_path' => $relativePath,
            'full_path' => $destinationPath,
            'file_size' => $fileSize, // Use the variable we captured before moving
            'original_name' => $originalName // Use the variable we captured before moving
        ]);
        
        return $relativePath;
        
    } catch (\Exception $e) {
        \Log::error('Failed to upload event image to public folder:', [
            'error' => $e->getMessage(),
            'event_id' => $eventId
        ]);
        throw new \Exception('Failed to upload event image: ' . $e->getMessage());
    }
}

    /**
     * Upload itinerary file with session tracking
     */
 
/**
 * Upload multiple itinerary files with session tracking
 */
public function uploadItinerary(int $userId, array $files, string $sessionId): array
{
    $sessionId = $sessionId ?? Str::uuid()->toString();
    $uploadedItineraries = [];
        $event = Event::where('session_id', $sessionId)->first();


    foreach ($files as $file) {
        $this->validateItineraryFile($file);
        
        $itineraryData = $this->processAndStoreItinerary($file, $userId, $sessionId);
         if ($event) {
            $itineraryData['event_id'] = $event->id;
            $itineraryData['is_attached_to_event'] = true;
        }
        $itinerary = EventItinerary::create($itineraryData);
        
        $uploadedItineraries[] = [
            'id' => $itinerary->id,
            'url' => $itinerary->file_url,
            'filename' => $itinerary->original_filename,
            'size' => $itinerary->file_size,
            'mime_type' => $itinerary->mime_type
        ];
    }

    return [
        'session_id' => $sessionId,
        'uploaded_itineraries' => $uploadedItineraries,
        'total_count' => count($uploadedItineraries),
        'message' => count($uploadedItineraries) > 1 
            ? 'Itinerary files uploaded successfully' 
            : 'Itinerary file uploaded successfully'
    ];
}



/**
 *  method for single file upload
 */
public function uploadSingleItinerary(int $userId, $file, string $sessionId): array
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
            'filename' => $itinerary->original_filename,
            'size' => $itinerary->file_size,
            'mime_type' => $itinerary->mime_type
        ],
        'message' => 'Itinerary uploaded successfully'
    ];
}

    /**
     * Get media files for a session
     */
    public function getSessionMedia(string $sessionId): array
    {
        $mediaFiles = EventMedia::forSession($sessionId)->unattached()->get();
        $itineraryFiles = EventItinerary::forSession($sessionId)->unattached()->get();

        return [
            'session_id' => $sessionId,
            'media_files' => $mediaFiles->map(function ($media) {
                return [
                    'id' => $media->id,
                    'type' => $media->media_type,
                    'url' => $media->file_url,
                    'filename' => $media->original_filename,
                    'size' => $media->file_size
                ];
            }),
            'itinerary_files' => $itineraryFiles->map(function ($itinerary) {
                return [
                    'id' => $itinerary->id,
                    'url' => $itinerary->file_url,
                    'filename' => $itinerary->original_filename,
                    'size' => $itinerary->file_size
                ];
            }),
            'total_media_count' => $mediaFiles->count(),
            'total_itinerary_count' => $itineraryFiles->count()
        ];
    }


    public function attachMediaToEventBySession(string $sessionId): array
{
    // Find event by session_id
    $event = Event::where('session_id', $sessionId)->first();
    
    if (!$event) {
        throw new \Exception('Event not found for this session');
    }
    
    // Attach media to the found event
    return $this->attachMediaToEvent($event->id, $sessionId);
}
    /**
     * Attach media to event (called in step 6)
     */
    public function attachMediaToEvent(int $eventId, string $sessionId): array
    {
        $mediaFiles = EventMedia::forSession($sessionId)->unattached()->get();

        $attachedCount = 0;
        foreach ($mediaFiles as $media) {
            $media->update([
                'event_id' => $eventId,
                'is_attached_to_event' => true
            ]);
            $attachedCount++;
        }

        return [
            'attached_media_count' => $attachedCount,
            'message' => "Successfully attached {$attachedCount} media files to event"
        ];
    }

    /**
     * Attach itinerary to event (called in step 7)
     */
    public function attachItineraryToEvent(int $eventId, string $sessionId): array
    {
        $itineraryFiles = EventItinerary::forSession($sessionId)->unattached()->get();

        $attachedCount = 0;
        foreach ($itineraryFiles as $itinerary) {
            $itinerary->update([
                'event_id' => $eventId,
                'is_attached_to_event' => true
            ]);
            $attachedCount++;
        }

        return [
            'attached_itinerary_count' => $attachedCount,
            'message' => "Successfully attached {$attachedCount} itinerary files to event"
        ];
    }

    /**
     * Delete session media (cleanup)
     */
    public function deleteSessionMedia(string $sessionId): array
    {
        $mediaFiles = EventMedia::forSession($sessionId)->unattached()->get();
        $itineraryFiles = EventItinerary::forSession($sessionId)->unattached()->get();

        $deletedMedia = 0;
        $deletedItinerary = 0;

        foreach ($mediaFiles as $media) {
            Storage::delete($media->file_path);
            $media->delete();
            $deletedMedia++;
        }

        foreach ($itineraryFiles as $itinerary) {
            Storage::delete($itinerary->file_path);
            $itinerary->delete();
            $deletedItinerary++;
        }

        return [
            'deleted_media_count' => $deletedMedia,
            'deleted_itinerary_count' => $deletedItinerary,
            'message' => 'Session media deleted successfully'
        ];
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

   private function validateMediaFile($file): void
{
    $allowedTypes = [
        // Image types
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/svg+xml',
        
        // Video types
        'video/mp4',
        'video/avi',
        'video/mov',
        'video/quicktime', // Alternative MIME for .mov
        'video/x-msvideo', // Alternative MIME for .avi
        'video/wmv',
        'video/x-ms-wmv',
        'video/flv',
        'video/x-flv',
        'video/webm',
        'video/x-matroska', // For .mkv files
        'video/3gpp',       // For .3gp files
        
        // Document types
        'application/pdf',
        'application/msword',                                               // .doc
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' // .docx
    ];

    // Different size limits based on file type
    $mimeType = $file->getMimeType();
    $fileSize = $file->getSize();
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new \Exception('Invalid file type. Only images, videos, and PDF documents are allowed.');
    }

    // Set different size limits based on file type
    if (strpos($mimeType, 'image/') === 0) {
        $maxSize = 10 * 1024 * 1024; // 10MB for images
        $fileType = 'Image';
    } elseif (strpos($mimeType, 'video/') === 0) {
        $maxSize = 100 * 1024 * 1024; // 100MB for videos
        $fileType = 'Video';
    } elseif ($mimeType === 'application/pdf' || strpos($mimeType, 'application/msword') === 0 || strpos($mimeType, 'application/vnd.openxml') === 0) {
        $maxSize = 25 * 1024 * 1024; // 25MB for documents
        $fileType = 'Document';
    } else {
        $maxSize = 50 * 1024 * 1024; // Default 50MB
        $fileType = 'File';
    }

    if ($fileSize > $maxSize) {
        $maxSizeMB = $maxSize / (1024 * 1024);
        throw new \Exception("{$fileType} size too large. Maximum size is {$maxSizeMB}MB.");
    }
}
private function validateItineraryFile($file): void
{
    $allowedTypes = [
        // Document formats
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'application/vnd.oasis.opendocument.text',
        // Image formats
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
        'image/svg+xml'
    ];
    $maxSize = 10 * 1024 * 1024; // 10MB

    if (!in_array($file->getMimeType(), $allowedTypes)) {
        throw new \Exception('Invalid file type. Only PDF, Word documents, text files, ODT files, and images (JPEG, PNG, GIF, WebP, BMP, SVG) are allowed.');
    }

    if ($file->getSize() > $maxSize) {
        throw new \Exception('File size too large. Maximum size is 10MB per file.');
    }

    // Additional validation for file name
    $fileName = $file->getClientOriginalName();
    if (strlen($fileName) > 255) {
        throw new \Exception('File name is too long. Maximum 255 characters allowed.');
    }
}

  private function processAndStoreMedia($file, int $userId, string $sessionId): array
{
    $originalName = $file->getClientOriginalName();
    $extension = $file->getClientOriginalExtension();
    $storedName = Str::uuid() . '.' . $extension;

    $path = "event-media/{$userId}/{$sessionId}";
    $publicPath = public_path($path);

    $mediaType = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'video';

    // Prepare media data
    $mediaData = [
        'user_id' => $userId,
        'original_filename' => $originalName,
        'stored_filename' => $storedName,
        'file_path' => $path,
        'file_url' => '', // will be filled after move
        'media_type' => $mediaType,
        'mime_type' => $file->getMimeType(),
        'file_size' => $file->getSize(),
        'upload_session_id' => $sessionId,
        'is_attached_to_event' => false
    ];

    // Get dimensions for images before move
    if ($mediaType === 'image') {
        try {
            [$width, $height] = getimagesize($file->getPathname());
            $mediaData['width'] = $width;
            $mediaData['height'] = $height;
        } catch (\Exception $e) {
            // Ignore if dimensions can't be fetched
        }
    }

    // Create directory if it doesn't exist
    if (!file_exists($publicPath)) {
        mkdir($publicPath, 0755, true);
    }

    // Move uploaded file to public directory
    $file->move($publicPath, $storedName);

    // Full path and URL
    $fullPath = "{$path}/{$storedName}";
    // $mediaData['file_url'] = $fullPath;
    $mediaData['file_url'] = $fullPath;


    return $mediaData;
}

private function processAndStoreItinerary($file, int $userId, string $sessionId): array
{
    $originalName = $file->getClientOriginalName();
    $extension = $file->getClientOriginalExtension();
    $storedName = Str::uuid() . '.' . $extension;

    // File path and public path
    $path = "event-itinerary/{$userId}/{$sessionId}";
    $fullPath = "{$path}/{$storedName}";
    $publicPath = public_path($path);

    // Prepare data before moving the file
    $itineraryData = [
        'user_id' => $userId,
        'original_filename' => $originalName,
        'stored_filename' => $storedName,
        'file_path' => $path,
        'file_url' => '', // will be set after move
        'mime_type' => $file->getMimeType(),
        'file_size' => $file->getSize(),
        'upload_session_id' => $sessionId,
        'is_attached_to_event' => false,
        'uploaded_at' => now()
    ];

    // If it's an image, get dimensions before move()
    if (str_starts_with($file->getMimeType(), 'image/')) {
        try {
            [$width, $height] = getimagesize($file->getPathname());
            $itineraryData['width'] = $width;
            $itineraryData['height'] = $height;
        } catch (\Exception $e) {
            // Ignore if can't get dimensions
        }
    }

    // Ensure directory exists
    if (!file_exists($publicPath)) {
        mkdir($publicPath, 0755, true);
    }

    // Move the file after all reads
    $file->move($publicPath, $storedName);

    // Set the full URL
    $itineraryData['file_url'] =  $fullPath;

    return $itineraryData;
}

}
<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventMenu;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventMenuService
{
    /**
     * Upload multiple menu images with session tracking
     */
    public function uploadMenuImages(int $userId, array $files, string $sessionId = null): array
    {
        $sessionId = $sessionId ?? Str::uuid()->toString();
        $uploadedMenus = [];
        $event = Event::where('session_id', $sessionId)->first();

        foreach ($files as $index => $file) {
            $this->validateMenuImageFile($file);
            
            $menuData = $this->processAndStoreMenuImage($file, $userId, $sessionId, $index);

            if ($event) {
                $menuData['event_id'] = $event->id;
                $menuData['is_attached_to_event'] = true;
            }

            $menu = EventMenu::create($menuData);
            
            $uploadedMenus[] = [
                'id' => $menu->id,
                'url' => $menu->file_url,
                'filename' => $menu->original_filename,
                'size' => $menu->file_size,
                'sort_order' => $menu->sort_order
            ];
        }

        return [
            'session_id' => $sessionId,
            'uploaded_menus' => $uploadedMenus,
            'total_count' => count($uploadedMenus),
            'message' => count($uploadedMenus) > 1 
                ? 'Menu images uploaded successfully' 
                : 'Menu image uploaded successfully'
        ];
    }

    /**
     * Upload single menu image
     */
    public function uploadSingleMenuImage(int $userId, $file, string $sessionId = null, int $sortOrder = 0): array
    {
        $sessionId = $sessionId ?? Str::uuid()->toString();
        
        $this->validateMenuImageFile($file);
        
        $menuData = $this->processAndStoreMenuImage($file, $userId, $sessionId, $sortOrder);
        $event = Event::where('session_id', $sessionId)->first();

        if ($event) {
            $menuData['event_id'] = $event->id;
            $menuData['is_attached_to_event'] = true;
        }

        $menu = EventMenu::create($menuData);

        return [
            'session_id' => $sessionId,
            'menu' => [
                'id' => $menu->id,
                'url' => $menu->file_url,
                'filename' => $menu->original_filename,
                'size' => $menu->file_size,
                'sort_order' => $menu->sort_order
            ],
            'message' => 'Menu image uploaded successfully'
        ];
    }

    /**
     * Get menu images for a session
     */
    public function getSessionMenuImages(string $sessionId): array
    {
        $menuImages = EventMenu::forSession($sessionId)->unattached()->ordered()->get();

        return [
            'session_id' => $sessionId,
            'menu_images' => $menuImages->map(function ($menu) {
                return [
                    'id' => $menu->id,
                    'url' => $menu->file_url,
                    'filename' => $menu->original_filename,
                    'size' => $menu->file_size,
                    'sort_order' => $menu->sort_order
                ];
            }),
            'total_count' => $menuImages->count()
        ];
    }

    /**
     * Get menu images for an event
     */
    public function getEventMenuImages(int $eventId): array
    {
        $menuImages = EventMenu::where('event_id', $eventId)->attached()->ordered()->get();

        return [
            'event_id' => $eventId,
            'menu_images' => $menuImages->map(function ($menu) {
                return [
                    'id' => $menu->id,
                    'url' => $menu->file_url,
                    'filename' => $menu->original_filename,
                    'size' => $menu->file_size,
                    'sort_order' => $menu->sort_order,
                    'width' => $menu->width,
                    'height' => $menu->height
                ];
            }),
            'total_count' => $menuImages->count()
        ];
    }

    /**
     * Attach menu images to event by session
     */
    public function attachMenuImagesToEventBySession(string $sessionId): array
    {
        $event = Event::where('session_id', $sessionId)->first();
        
        if (!$event) {
            throw new \Exception('Event not found for the given session ID');
        }

        $menuImages = EventMenu::forSession($sessionId)->unattached()->get();

        if ($menuImages->isEmpty()) {
            return [
                'event_id' => $event->id,
                'attached_count' => 0,
                'message' => 'No menu images found to attach'
            ];
        }

        // Update menu images to attach them to the event
        EventMenu::forSession($sessionId)->unattached()->update([
            'event_id' => $event->id,
            'is_attached_to_event' => true
        ]);

        return [
            'event_id' => $event->id,
            'attached_count' => $menuImages->count(),
            'message' => 'Menu images attached to event successfully'
        ];
    }

    /**
     * Update menu images sort order
     */
    public function updateMenuImagesSortOrder(int $eventId, array $sortOrderData): array
    {
        foreach ($sortOrderData as $item) {
            EventMenu::where('id', $item['id'])
                ->where('event_id', $eventId)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return [
            'event_id' => $eventId,
            'updated_count' => count($sortOrderData),
            'message' => 'Menu images order updated successfully'
        ];
    }

    /**
     * Delete menu image
     */
    public function deleteMenuImage(int $menuId, int $userId): array
    {
        $menu = EventMenu::where('id', $menuId)->where('user_id', $userId)->first();

        if (!$menu) {
            throw new \Exception('Menu image not found or access denied');
        }

        // Delete physical file
        $fullPath = public_path($menu->file_path . '/' . $menu->stored_filename);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $menu->delete();

        return [
            'message' => 'Menu image deleted successfully'
        ];
    }

    /**
     * Delete session menu images
     */
    public function deleteSessionMenuImages(string $sessionId): array
    {
        $menuImages = EventMenu::forSession($sessionId)->unattached()->get();

        foreach ($menuImages as $menu) {
            // Delete physical file
            $fullPath = public_path($menu->file_path . '/' . $menu->stored_filename);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $deletedCount = $menuImages->count();
        EventMenu::forSession($sessionId)->unattached()->delete();

        return [
            'session_id' => $sessionId,
            'deleted_count' => $deletedCount,
            'message' => 'Session menu images deleted successfully'
        ];
    }

    /**
     * Validate menu image file
     */
    private function validateMenuImageFile($file): void
    {
        $allowedTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/svg+xml'
        ];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid file type. Only images (JPEG, PNG, GIF, WebP, BMP, SVG) are allowed for menu images.');
        }

        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size too large. Maximum size is 5MB per menu image.');
        }

        // Additional validation for file name
        $fileName = $file->getClientOriginalName();
        if (strlen($fileName) > 255) {
            throw new \Exception('File name is too long. Maximum 255 characters allowed.');
        }
    }

    /**
     * Process and store menu image
     */
    private function processAndStoreMenuImage($file, int $userId, string $sessionId, int $sortOrder = 0): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $storedName = Str::uuid() . '.' . $extension;

        $path = "event-menus/{$userId}/{$sessionId}";
        $publicPath = public_path($path);

        // Prepare menu data
        $menuData = [
            'user_id' => $userId,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $path,
            'file_url' => '', // will be filled after move
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'upload_session_id' => $sessionId,
            'is_attached_to_event' => false,
            'sort_order' => $sortOrder
        ];

        // Get dimensions for images
        try {
            [$width, $height] = getimagesize($file->getPathname());
            $menuData['width'] = $width;
            $menuData['height'] = $height;
        } catch (\Exception $e) {
            // Ignore if dimensions can't be fetched
        }

        // Create directory if it doesn't exist
        if (!file_exists($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        // Move uploaded file to public directory
        $file->move($publicPath, $storedName);

        // Full path and URL
        $fullPath = "{$path}/{$storedName}";
        $menuData['file_url'] = $fullPath;

        return $menuData;
    }
}
<?php

namespace App\Services;

use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\VenueTypeRepositoryInterface;
use App\Repositories\Contracts\VenueCategoryRepositoryInterface;
use Carbon\Carbon;
use App\Repositories\Contracts\OneOnOneDateRepositoryInterface;


class HomeScreenService
{
    private EventRepositoryInterface $eventRepository;
    private VenueTypeRepositoryInterface $venueTypeRepository;
    private VenueCategoryRepositoryInterface $venueCategoryRepository;
    private OneOnOneDateRepositoryInterface $oneOnOneDateRepository;

    public function __construct(
        EventRepositoryInterface $eventRepository,
        VenueTypeRepositoryInterface $venueTypeRepository,
        VenueCategoryRepositoryInterface $venueCategoryRepository,
        OneOnOneDateRepositoryInterface $oneOnOneDateRepository

    ) {
        $this->eventRepository = $eventRepository;
        $this->venueTypeRepository = $venueTypeRepository;
        $this->venueCategoryRepository = $venueCategoryRepository;
        $this->oneOnOneDateRepository = $oneOnOneDateRepository;

    }

    /**
     * Get Home Screen Data with Dynamic Categories
     */
    public function getHomeScreenData(int $userId): array
    {
        // Get hot/trending events
        $hotEvents = $this->getHotInDemandEvents();
        
        // Get recent events
        $recentEvents = $this->getRecentEvents($userId);
        
        // Get dynamic categories from venue_categories table
        $categories = $this->getDynamicCategories();
        
        // Get featured events by category
        $categoryEvents = $this->getCategoryEvents();

        return [
            'hot_in_demand' => $hotEvents,
            'recent_events' => $recentEvents,
            'categories' => $categories,
            // 'category_events' => $categoryEvents,
            'total_events_count' => $this->getTotalEventsCount()
        ];
    }

    /**
     * Get Events by Specific Category ID
     */
    public function getEventsByCategoryId(int $categoryId, int $userId, int $limit = 10, int $offset = 0): array
    {
        $category = $this->venueCategoryRepository->findById($categoryId);
        
        if (!$category) {
            throw new \Exception('Category not found');
        }

        $events = $this->eventRepository->getEventsByCategoryId($categoryId, $limit, $offset);
        $totalCount = $this->eventRepository->getEventsCategoryIdCount($categoryId);

        return [
            'category' => [
                'id' => is_array($category) ? $category['id'] : $category->id,
                'name' => is_array($category) ? $category['name'] : $category->name,
                'slug' => is_array($category) ? $category['slug'] : $category->slug,
                'description' => is_array($category) ? ($category['description'] ?? null) : $category->description,
                'icon' => is_array($category) ? ($category['icon'] ?? null) : $category->icon
            ],
            'events' => $this->formatEventsForMobile($events, $userId),
            'total_count' => $totalCount,
            'has_more' => ($offset + $limit) < $totalCount
        ];
    }

    /**
     * Apply Filters and Get Events
     */
    // public function applyFilters(array $filters, int $userId): array
    // {
    //     $limit = $filters['limit'] ?? 10;
    //     $offset = $filters['offset'] ?? 0;
        
    //     $events = $this->eventRepository->getFilteredEvents($filters, $limit, $offset);
    //     $totalCount = $this->eventRepository->getFilteredEventsCount($filters);

    //     return [
    //         'applied_filters' => $this->formatAppliedFilters($filters),
    //         'events' => $this->formatEventsForMobile($events, $userId),
    //         'total_count' => $totalCount,
    //         'has_more' => ($offset + $limit) < $totalCount
    //     ];
    // }

      public function applyFilters(array $filters, int $userId): array
    {
        $limit = $filters['limit'] ?? 10;
        $offset = $filters['offset'] ?? 0;
        
        $result = [
            'applied_filters' => $this->formatAppliedFilters($filters),
            'events' => [],
            'one_on_one_dates' => [],
            'total_count' => 0,
            'has_more' => false
        ];

        // Check what type of events to fetch
        if (isset($filters['event_type'])) {
            if ($filters['event_type'] === 'one-on-one') {
                // Fetch only one-on-one dates
                $oneOnOneDates = $this->oneOnOneDateRepository->getOneOnOneDates($filters, $limit, $offset);
                $result['one_on_one_dates'] = $this->formatOneOnOneDatesForMobile($oneOnOneDates, $userId);
                $result['total_count'] = count($oneOnOneDates);
                $result['has_more'] = ($offset + $limit) < $result['total_count'];
            } else {
                // Fetch group events (regular events)
                $events = $this->eventRepository->getFilteredEvents($filters, $limit, $offset);
                $totalCount = $this->eventRepository->getFilteredEventsCount($filters);
                
                $result['events'] = $this->formatEventsForMobile($events, $userId);
                $result['total_count'] = $totalCount;
                $result['has_more'] = ($offset + $limit) < $totalCount;
            }
        } else {
            // No specific type filter - return both
            $events = $this->eventRepository->getFilteredEvents($filters, $limit, $offset);
            $totalCount = $this->eventRepository->getFilteredEventsCount($filters);
            
            $result['events'] = $this->formatEventsForMobile($events, $userId);
            $result['total_count'] = $totalCount;
            $result['has_more'] = ($offset + $limit) < $totalCount;
        }

        return $result;
    }

    /**
     * Search Events
     */
    public function searchEvents(string $query, int $userId, int $limit = 10, int $offset = 0): array
    {
        $events = $this->eventRepository->searchEventsByQuery($query, $limit, $offset);
        $totalCount = $this->eventRepository->getSearchCount($query);

        return [
            'search_query' => $query,
            'events' => $this->formatEventsForMobile($events, $userId),
            'total_count' => $totalCount,
            'has_more' => ($offset + $limit) < $totalCount
        ];
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    private function getHotInDemandEvents(): array
    {
        // Get events with high attendance or recent activity
        $events = $this->eventRepository->getHotEvents(5);
        return $this->formatEventsForMobile($events);
    }

    private function getRecentEvents(int $userId): array
    {
        // Get recently created or user-relevant events
        $events = $this->eventRepository->getRecentEvents($userId, 10);
        return $this->formatEventsForMobile($events);
    }

    /**
     * Get Dynamic Categories from venue_categories table
     */
    // private function getDynamicCategories(): array
    // {
    //     try {
    //         $venueTypes = $this->venueTypeRepository->getAllActive();
    //         $categories = [];

    //         if (empty($venueTypes)) {
    //             return $categories;
    //         }

    //         foreach ($venueTypes as $venueType) {
    //             $venueTypeId = is_array($venueType) ? $venueType['id'] : $venueType->id;
    //             $venueTypeName = is_array($venueType) ? $venueType['name'] : $venueType->name;
                
    //             $venueCategories = $this->venueCategoryRepository->getByVenueType($venueTypeId);
                
    //             if (empty($venueCategories)) {
    //                 continue;
    //             }
                
    //             foreach ($venueCategories as $category) {
    //                 $categoryId = is_array($category) ? $category['id'] : $category->id;
    //                 $categoryName = is_array($category) ? $category['name'] : $category->name;
    //                 $categorySlug = is_array($category) ? ($category['slug'] ?? strtolower(str_replace(' ', '_', $categoryName))) : ($category->slug ?? strtolower(str_replace(' ', '_', $categoryName)));
    //                 $categoryDescription = is_array($category) ? ($category['description'] ?? null) : $category->description;
    //                 $categoryIcon = is_array($category) ? ($category['icon'] ?? null) : $category->icon;
    //                 $categorySortOrder = is_array($category) ? ($category['sort_order'] ?? 0) : ($category->sort_order ?? 0);
                    
    //                 $eventCount = $this->eventRepository->getCategoryIdCount($categoryId);
                    
    //                 $categories[] = [
    //                     'id' => $categoryId,
    //                     'name' => $categoryName,
    //                     'slug' => $categorySlug,
    //                     'description' => $categoryDescription,
    //                     'icon' => $categoryIcon ?: $this->getDefaultCategoryIcon($categoryName),
    //                     'color' => $this->getDefaultCategoryColor($categoryName),
    //                     'venue_type' => [
    //                         'id' => $venueTypeId,
    //                         'name' => $venueTypeName
    //                     ],
    //                     'event_count' => $eventCount,
    //                     'sort_order' => $categorySortOrder
    //                 ];
    //             }
    //         }

    //         // Sort by sort_order and event_count
    //         usort($categories, function($a, $b) {
    //             if ($a['sort_order'] === $b['sort_order']) {
    //                 return $b['event_count'] - $a['event_count'];
    //             }
    //             return $a['sort_order'] - $b['sort_order'];
    //         });

    //         return $categories;

    //     } catch (\Exception $e) {
    //         // Return empty array if there's an error, log it
    //         \Log::error('Error getting dynamic categories: ' . $e->getMessage());
    //         return [];
    //     }
    // }

    private function getDynamicCategories(): array
{
    try {
        $venueTypes = $this->venueTypeRepository->getAllActive();
        $categories = [];

        if (empty($venueTypes)) {
            return $categories;
        }

        foreach ($venueTypes as $venueType) {
            $venueTypeId = is_array($venueType) ? $venueType['id'] : $venueType->id;
            $venueTypeName = is_array($venueType) ? $venueType['name'] : $venueType->name;

            $venueCategories = $this->venueCategoryRepository->getByVenueType($venueTypeId);

            foreach ($venueCategories as $category) {
                $categoryId = is_array($category) ? $category['id'] : $category->id;
                $categoryName = is_array($category) ? $category['name'] : $category->name;
                $categorySlug = is_array($category) ? $category['slug'] : $category->slug;
                $categoryDescription = is_array($category) ? ($category['description'] ?? null) : $category->description;
                $categoryIcon = is_array($category) ? ($category['icon'] ?? null) : $category->icon;
                $categorySortOrder = is_array($category) ? ($category['sort_order'] ?? 999) : ($category->sort_order ?? 999);

                $eventCount = $this->eventRepository->getCategoryIdCount($categoryId);
                
                // Get emoji icon and combine with existing icon
                $emojiIcon = $this->getDefaultCategoryIcon($categoryName);
                $combinedIcon = $categoryIcon ? "{$emojiIcon} {$categoryIcon}" : $emojiIcon;

                $categories[] = [
                    'id' => $categoryId,
                    'name' => $categoryName,
                    'slug' => $categorySlug,
                    'description' => $categoryDescription,
                    'icon' => $combinedIcon,
                    'color' => $this->getDefaultCategoryColor($categoryName),
                    'venue_type' => [
                        'id' => $venueTypeId,
                        'name' => $venueTypeName
                    ],
                    'event_count' => $eventCount,
                    'sort_order' => $categorySortOrder
                ];
            }
        }

        usort($categories, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        return $categories;

    } catch (\Exception $e) {
        \Log::error('Error getting dynamic categories: ' . $e->getMessage());
        return [];
    }
}

    /**
     * Get Category Events using dynamic categories
     */
    private function getCategoryEvents(): array
    {
        try {
            $categories = $this->getDynamicCategories();
            $categoryEvents = [];

            // Get top 6 categories with most events
            $topCategories = array_slice($categories, 0, 6);

            foreach ($topCategories as $category) {
                $events = $this->eventRepository->getEventsByCategoryId($category['id'], 3, 0);
                $categoryEvents[$category['slug']] = [
                    'category_info' => $category,
                    'events' => $this->formatEventsForMobile($events)
                ];
            }

            return $categoryEvents;

        } catch (\Exception $e) {
            \Log::error('Error getting category events: ' . $e->getMessage());
            return [];
        }
    }

    private function formatEventsForMobile(array $events, int $userId = null): array
    {
        return array_map(function ($event) use ($userId) {
            // Handle array vs object conversion
            $eventData = is_array($event) ? $event : $event->toArray();
            
            $maxGroupSize = $eventData['max_group_size'] ?? $eventData['min_group_size'] ?? 0;
            $attendeesCount = $eventData['attendees_count'] ?? 0;
            $availableSpots = $maxGroupSize - $attendeesCount;
            
            return [
                'id' => $eventData['id'],
                'name' => $eventData['name'],
                'description' => $eventData['description'],
                'host_name' => $eventData['host']['name'] ?? 'Unknown Host',
                'date' => Carbon::parse($eventData['event_date'])->format('M j, Y'),
                'time' => Carbon::parse($eventData['event_time'])->format('H:i'),
                'location' => [
                    'venue_name' => $eventData['venue_name'] ?? null,
                    'city' => $eventData['city'] ?? null,
                    'address' => $eventData['venue_address'] ?? null
                ],
                'venue_category' => [
                    'id' => $eventData['venue_category_id'] ?? null,
                    'name' => $eventData['venue_category']['name'] ?? null,
                    'icon' => $eventData['venue_category']['icon'] ?? null
                ],
                'attendees' => [
                    'current_count' => $attendeesCount,
                    'min_size' => $eventData['min_group_size'],
                    'max_size' => $eventData['max_group_size'],
                    'available_spots' => $availableSpots,
                    'spots_text' => $availableSpots > 0 ? "{$availableSpots} Spots left" : "Full"
                ],
                'cost' => [
                    'token_cost' => $eventData['token_cost_per_attendee'] ?? 0,
                    'currency' => 'Olas',
                    'is_free' => ($eventData['token_cost_per_attendee'] ?? 0) == 0
                ],
                'tags' => $eventData['tags'] ?? [],
                'event_image' => $eventData['event_image'],
                'image_url' => $this->getEventImageUrl($eventData),
                'is_hot' => $this->isHotEvent($eventData),
                'is_user_joined' => $userId ? $this->isUserJoined($eventData['id'], $userId) : false,
                'can_join' => $availableSpots > 0 && ($eventData['status'] ?? '') === 'published'
            ];
        }, $events);
    }

    private function formatAppliedFilters(array $filters): array
    {
        $applied = [];
        
        if (!empty($filters['event_type'])) {
            $applied[] = ['type' => 'event_type', 'value' => $filters['event_type'], 'label' => ucfirst($filters['event_type'])];
        }
        
        if (!empty($filters['venue_category_ids'])) {
            foreach ($filters['venue_category_ids'] as $categoryId) {
                try {
                    $category = $this->venueCategoryRepository->findById($categoryId);
                    if ($category) {
                        $categoryName = is_array($category) ? $category['name'] : $category->name;
                        $applied[] = ['type' => 'category', 'value' => $categoryId, 'label' => $categoryName];
                    }
                } catch (\Exception $e) {
                    // Skip if category not found
                    continue;
                }
            }
        }
        
        if (!empty($filters['age_min']) || !empty($filters['age_max'])) {
            $min = $filters['age_min'] ?? 18;
            $max = $filters['age_max'] ?? 100;
            $applied[] = ['type' => 'age', 'value' => "{$min}-{$max}", 'label' => "Age {$min}-{$max}"];
        }
        
        if (!empty($filters['event_spot'])) {
            $applied[] = ['type' => 'spot', 'value' => $filters['event_spot'], 'label' => ucfirst($filters['event_spot'])];
        }
        
        return $applied;
    }

    /**
     * Get default icon based on category name
     */
    private function getDefaultCategoryIcon(string $categoryName): string
    {
        $icons = [
            'cafe' => '☕',
            'restaurant' => '🍽️',
            'bar' => '🍺',
            'park' => '🌳',
            'gym' => '💪',
            'club' => '🎵',
            'cinema' => '🎬',
            'library' => '📚',
            'mall' => '🏪',
            'beach' => '🏖️',
            'sports' => '⚽',
            'outdoor' => '🌲',
            'indoor' => '🏠'
        ];

        $categoryLower = strtolower($categoryName);
        
        foreach ($icons as $keyword => $icon) {
            if (strpos($categoryLower, $keyword) !== false) {
                return $icon;
            }
        }
        
        return '📍'; // Default icon
    }

    /**
     * Get default color based on category name
     */
    private function getDefaultCategoryColor(string $categoryName): string
    {
        $colors = [
            'cafe' => '#8B4513',
            'restaurant' => '#FF6B35',
            'bar' => '#9B59B6',
            'park' => '#27AE60',
            'gym' => '#E74C3C',
            'club' => '#F39C12',
            'cinema' => '#3498DB',
            'library' => '#34495E',
            'mall' => '#E67E22',
            'beach' => '#1ABC9C',
            'sports' => '#FF6B6B',
            'outdoor' => '#2ECC71',
            'indoor' => '#95A5A6'
        ];

        $categoryLower = strtolower($categoryName);
        
        foreach ($colors as $keyword => $color) {
            if (strpos($categoryLower, $keyword) !== false) {
                return $color;
            }
        }
        
        return '#7F8C8D'; // Default color
    }

    private function getEventImageUrl(array $event): ?string
    {
        // Get first image from event media or return default
        if (!empty($event['media']) && is_array($event['media'])) {
            $images = array_filter($event['media'], fn($media) => ($media['media_type'] ?? '') === 'image');
            if (!empty($images)) {
                return array_values($images)[0]['file_url'] ?? null;
            }
        }
        
        // return $images;
        // Return default image based on venue category
        // $categoryName = $event['venue_category']['name'] ?? 'default';
        // return $this->getDefaultImageByCategory($categoryName);
        return null;
    }

    private function getDefaultImageByCategory(string $categoryName): string
    {
        $defaults = [
            'cafe' => '/images/defaults/cafe.jpg',
            'restaurant' => '/images/defaults/restaurant.jpg',
            'bar' => '/images/defaults/bar.jpg',
            'park' => '/images/defaults/park.jpg',
            'gym' => '/images/defaults/gym.jpg',
            'club' => '/images/defaults/club.jpg',
            'cinema' => '/images/defaults/cinema.jpg',
            'library' => '/images/defaults/library.jpg',
            'mall' => '/images/defaults/mall.jpg',
            'beach' => '/images/defaults/beach.jpg'
        ];
        
        $categoryLower = strtolower($categoryName);
        
        foreach ($defaults as $keyword => $image) {
            if (strpos($categoryLower, $keyword) !== false) {
                return $image;
            }
        }
        
        return '/images/defaults/event.jpg';
    }

    private function isHotEvent(array $event): bool
    {
        // Consider event "hot" if it has high attendance or recent activity
        $attendeesCount = $event['attendees_count'] ?? 0;
        $maxSize = $event['max_group_size'] ?? $event['min_group_size'] ?? 1;
        $attendanceRate = $maxSize > 0 ? ($attendeesCount / $maxSize) : 0;
        
        return $attendanceRate > 0.7; // 70% filled
    }

    private function isUserJoined(int $eventId, int $userId): bool
    {
        return $this->eventRepository->isUserJoinedEvent($eventId, $userId);
    }

    private function getTotalEventsCount(): int
    {
        return $this->eventRepository->getTotalPublishedEventsCount();
    }

    private function formatOneOnOneDatesForMobile(array $dates, int $userId = null): array
    {
        return array_map(function ($date) use ($userId) {
            // Handle array vs object conversion
            $dateData = is_array($date) ? $date : $date->toArray();
            
            return [
                'id' => $dateData['id'],
                'name' => $dateData['name'],
                'description' => $dateData['description'],
                'host_name' => $dateData['host']['name'] ?? 'Unknown Host',
                'date' => Carbon::parse($dateData['event_date'])->format('M j, Y'),
                'time' => Carbon::parse($dateData['event_time'])->format('H:i'),
                'location' => [
                    'venue_name' => $dateData['venue_name'] ?? null,
                    'venue_address' => $dateData['venue_address'] ?? null,
                    'city' => $dateData['city'] ?? null,
                    'address' => $dateData['venue_address'] ?? null,
                    'google_place_id' => $dateData['google_place_id'] ?? null,
                    'latitude' => $dateData['latitude'] ?? null,
                    'longitude' => $dateData['longitude'] ?? null
                ],
                'cost' => [
                    'token_cost' => $dateData['token_cost'] ?? 0,
                    'currency' => 'Olas',
                    'is_free' => ($dateData['token_cost'] ?? 0) == 0
                ],
                'image_url' => $this->getOneOnOneDateImageUrl($dateData),
                'is_user_joined' => false, // For now, always false
                'can_join' => $dateData['status'] === 'published' && $dateData['approval_status'] === 'approved',
                'approval_status' => $dateData['approval_status'],
                'type' => 'one_on_one_date' // Identifier for frontend
            ];
        }, $dates);
    }

      /**
     * Get image URL for one-on-one date
     */
    private function getOneOnOneDateImageUrl(array $date): ?string
    {
        // Get first image from date media or return null
        if (!empty($date['media']) && is_array($date['media'])) {
            $images = array_filter($date['media'], fn($media) => ($media['media_type'] ?? '') === 'image');
            if (!empty($images)) {
                return array_values($images)[0]['file_url'] ?? null;
            }
        }
        
        return null;
    }
}
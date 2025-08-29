<?php

namespace App\Services;

use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\OneOnOneDateRepositoryInterface;
use Carbon\Carbon;

class HomeScreenService
{
    private EventRepositoryInterface $eventRepository;
    private OneOnOneDateRepositoryInterface $oneOnOneDateRepository;

    public function __construct(
        EventRepositoryInterface $eventRepository,
        OneOnOneDateRepositoryInterface $oneOnOneDateRepository
    ) {
        $this->eventRepository = $eventRepository;
        $this->oneOnOneDateRepository = $oneOnOneDateRepository;
    }

    /**
     * Get Home Screen Data (Simplified for Index Page)
     */
   public function getHomeScreenData(int $userId, array $filters = []): array
    {
        $limit = $filters['limit'] ?? 20;
        $offset = $filters['offset'] ?? 0;
        
        // Prepare date filters based on the filter type
        $dateFilters = $this->prepareDateFilters($filters);
        
        // Get events with date filtering
        $events = $this->eventRepository->getFilteredEvents($dateFilters, $limit, $offset);
        $totalCount = $this->eventRepository->getFilteredEventsCount($dateFilters);
                $banners = $this->getBannerImages($dateFilters);

        return [
            'banners' => $banners,
            'events' => $this->formatEventsForMobile($events, $userId),
            'total_count' => $totalCount,
            'has_more' => ($offset + $limit) < $totalCount,
            'date_filter' => $filters['date_filter'] ?? 'today',
            'applied_date_range' => $this->getAppliedDateRangeText($filters)
        ];
    }

    /**
     * Apply Filters and Get Events (Simplified - No Venue Categories)
     */
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
            // No specific type filter - return events only
            $events = $this->eventRepository->getFilteredEvents($filters, $limit, $offset);
            $totalCount = $this->eventRepository->getFilteredEventsCount($filters);
            
            $result['events'] = $this->formatEventsForMobile($events, $userId);
            $result['total_count'] = $totalCount;
            $result['has_more'] = ($offset + $limit) < $totalCount;
        }

        return $result;
    }

    /**
     * Search Events (Simplified)
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

   private function getBannerImages(array $dateFilters): array
    {
        // Get all image files from public/locationImages/ folder
        $locationImagesPath = public_path('locationImages');
        $banners = [];
        
        if (is_dir($locationImagesPath)) {
            // Get all image files from the directory
            $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $allImages = [];
            
            foreach (scandir($locationImagesPath) as $file) {
                $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($fileExtension, $imageExtensions)) {
                    $allImages[] = $file;
                }
            }
            
            if (!empty($allImages)) {
                // Randomly select 2-3 images
                $numberOfImages = rand(2, min(3, count($allImages)));
                $selectedImages = array_rand($allImages, $numberOfImages);
                
                // If only one image is selected, make it an array
                if (!is_array($selectedImages)) {
                    $selectedImages = [$selectedImages];
                }
                
                foreach ($selectedImages as $index) {
                    $banners[] = ('locationImages/' . $allImages[$index]);
                }
            }
        }
        
        // Fallback to empty array if no images found
        return $banners;
    }

    private function prepareDateFilters(array $filters): array
    {
        $dateFilter = $filters['date_filter'] ?? 'today';
        $dateFilters = [];
        
        switch ($dateFilter) {
            case 'today':
                $dateFilters['start_date'] = Carbon::today()->format('Y-m-d');
                $dateFilters['end_date'] = Carbon::today()->format('Y-m-d');
                break;
                
            case 'tomorrow':
                $dateFilters['start_date'] = Carbon::tomorrow()->format('Y-m-d');
                $dateFilters['end_date'] = Carbon::tomorrow()->format('Y-m-d');
                break;
                
            case 'custom':
                if (!empty($filters['date'])) {
                    // Single custom date
                    $dateFilters['start_date'] = $filters['date'];
                    $dateFilters['end_date'] = $filters['date'];
                } elseif (!empty($filters['start_date'])) {
                    // Date range
                    $dateFilters['start_date'] = $filters['start_date'];
                    $dateFilters['end_date'] = $filters['end_date'] ?? $filters['start_date'];
                }
                break;
                
            default:
                // Default to today if invalid filter
                $dateFilters['start_date'] = Carbon::today()->format('Y-m-d');
                $dateFilters['end_date'] = Carbon::today()->format('Y-m-d');
                break;
        }
        
        return $dateFilters;
    }

    /**
     * Get applied date range text for display (NEW)
     */
    private function getAppliedDateRangeText(array $filters): string
    {
        $dateFilter = $filters['date_filter'] ?? 'today';
        
        switch ($dateFilter) {
            case 'today':
                return 'Today (' . Carbon::today()->format('M j, Y') . ')';
                
            case 'tomorrow':
                return 'Tomorrow (' . Carbon::tomorrow()->format('M j, Y') . ')';
                
            case 'custom':
                if (!empty($filters['date'])) {
                    return Carbon::parse($filters['date'])->format('M j, Y');
                } elseif (!empty($filters['start_date'])) {
                    $startDate = Carbon::parse($filters['start_date'])->format('M j, Y');
                    if (!empty($filters['end_date']) && $filters['end_date'] !== $filters['start_date']) {
                        $endDate = Carbon::parse($filters['end_date'])->format('M j, Y');
                        return "{$startDate} - {$endDate}";
                    }
                    return $startDate;
                }
                break;
        }
        
        return 'Today';
    }
    private function getHotInDemandEvents(int $userId = null): array
    {
        $events = $this->eventRepository->getHotEvents(5);
        return $this->formatEventsForMobile($events, $userId);
    }

    private function getRecentEvents(int $userId): array
    {
        $events = $this->eventRepository->getRecentEvents($userId, 10);
        return $this->formatEventsForMobile($events, $userId);
    }

    /**
     * SIMPLIFIED: Format Events for Mobile Index Page (Minimal Data)
     */
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
                
                // SIMPLIFIED: Basic location info only
                'location' => [
                    'venue_name' => $eventData['venue_name'] ?? null,
                    'city' => $eventData['city'] ?? null,
                ],
                
                // SIMPLIFIED: Basic attendee info (UPDATED with gender balance)
                'attendees' => $this->getAttendeeInfo($eventData),
                
                // SIMPLIFIED: Fixed cost info
                'cost' => [
                    'token_cost' => 5.00, // Fixed 5 olos
                    'currency' => 'Olos'
                ],
                
                // SIMPLIFIED: Primary image only from LocationImage
                'image_url' => $this->getPrimaryLocationImage($eventData),
                
                // SIMPLIFIED: Age range display
                'age_range' => $this->getSimpleAgeRange($eventData),
                
                // Basic status
                'is_user_joined' => $userId ? $this->isUserJoined($eventData['id'], $userId) : false,
                'can_join' => $availableSpots > 0 && ($eventData['status'] ?? '') === 'published'
            ];
        }, $events);
    }

    /**
     * Get ONLY primary image from LocationImage table
     */
    private function getPrimaryLocationImage(array $eventData): ?string
    {
        // Check if SuggestedLocation primary image is loaded
        if (isset($eventData['suggested_location']['primary_image']['image_url'])) {
            return $eventData['suggested_location']['primary_image']['image_url'];
        }
        
        // Alternative path if primary_image relation isn't loaded but images array exists
        if (isset($eventData['suggested_location']['images']) && !empty($eventData['suggested_location']['images'])) {
            foreach ($eventData['suggested_location']['images'] as $image) {
                if ($image['is_primary'] ?? false) {
                    return $image['image_url'];
                }
            }
        }
        
        return null; // No image available - clean UI
    }

    /**
     * Simple age range display
     */
    private function getSimpleAgeRange(array $eventData): string
    {
        if ($eventData['age_restriction_disabled'] ?? false) {
            return 'All ages';
        }
        
        $minAge = $eventData['min_age'] ?? 18;
        $maxAge = $eventData['max_age'] ?? 100;
        
        return "{$minAge}-{$maxAge}";
    }

    /**
     * SIMPLIFIED: Applied filters (removed venue categories)
     */
   private function formatAppliedFilters(array $filters): array
    {
        $applied = [];
        
        if (!empty($filters['event_type'])) {
            $applied[] = [
                'type' => 'event_type', 
                'value' => $filters['event_type'], 
                'label' => ucfirst($filters['event_type'])
            ];
        }
        
        if (!empty($filters['age_min']) || !empty($filters['age_max'])) {
            $min = $filters['age_min'] ?? 18;
            $max = $filters['age_max'] ?? 100;
            $applied[] = [
                'type' => 'age', 
                'value' => "{$min}-{$max}", 
                'label' => "Age {$min}-{$max}"
            ];
        }
        
        return $applied;
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
                    'city' => $dateData['city'] ?? null,
                ],
                'cost' => [
                    'token_cost' => $dateData['token_cost'] ?? 0,
                    'currency' => 'Olos'
                ],
                'status' => $dateData['status'] ?? 'available',
                'is_user_booked' => $userId ? false : false, // Placeholder
                'can_book' => ($dateData['status'] ?? '') === 'available'
            ];
        }, $dates);
    }

    /**
     * Get attendee information including gender balance (NEW)
     */
    private function getAttendeeInfo(array $eventData): array
    {
        $maxGroupSize = $eventData['max_group_size'] ?? $eventData['min_group_size'] ?? 0;
        $attendeesCount = $eventData['attendees_count'] ?? 0;
        $availableSpots = $maxGroupSize - $attendeesCount;
        
        $baseInfo = [
            'current_count' => $attendeesCount,
            'group_size' => $eventData['min_group_size'],
            'available_spots' => $availableSpots,
            'spots_text' => $availableSpots > 0 ? "{$availableSpots} spots left" : "Full"
        ];

        // Add gender balance information if enabled
        if ($this->isGenderBalancedEvent($eventData)) {
            $genderSlots = $this->calculateGenderSlots($eventData);
            $currentGenderCount = $this->getCurrentGenderCount($eventData);
            
            $baseInfo['gender_balance'] = [
                'enabled' => true,
                'ratio' => "{$genderSlots['male']}:{$genderSlots['female']}", // Show actual numbers (3:3)
                'male_spots_left' => max(0, $genderSlots['male'] - $currentGenderCount['male']),
                'female_spots_left' => max(0, $genderSlots['female'] - $currentGenderCount['female']),
                'male_spots_total' => $genderSlots['male'],
                'female_spots_total' => $genderSlots['female'],
                'spots_text' => $this->getGenderSpotsText($genderSlots, $currentGenderCount)
            ];
        } else {
            $baseInfo['gender_balance'] = [
                'enabled' => false
            ];
        }

        return $baseInfo;
    }

    /**
     * Check if event is gender balanced (based on existing database fields)
     */
    private function isGenderBalancedEvent(array $eventData): bool
    {
        // Check if gender rules are enabled
        if (!($eventData['gender_rule_enabled'] ?? false)) {
            return false;
        }
        
        // Check if composition contains "Gender balanced" text
        $composition = $eventData['gender_composition'] ?? '';
        return strpos($composition, 'Gender balanced') !== false;
    }

    /**
     * Calculate gender slots based on ratio (FIXED - Equal split for gender balanced)
     */
    private function calculateGenderSlots(array $eventData): array
    {
        $totalSlots = $eventData['min_group_size'] ?? 0;
        
        // For gender balanced events, ALWAYS equal split (ignore composition_value)
        if ($this->isGenderBalancedEvent($eventData)) {
            $halfSlots = $totalSlots / 2;
            return [
                'male' => $halfSlots,
                'female' => $halfSlots
            ];
        }
        
        // For non-gender balanced events
        return [
            'male' => $totalSlots,
            'female' => 0
        ];
    }

    /**
     * Get current gender count (placeholder - needs attendee gender data)
     */
    private function getCurrentGenderCount(array $eventData): array
    {
        // TODO: Implement actual gender counting from attendees
        // For now, returning zeros as placeholder
        return [
            'male' => 0,
            'female' => 0
        ];
        
        // This would require joining with attendees and users tables
        // to get actual gender counts from current attendees
    }

    /**
     * Generate gender spots text for display
     */
    private function getGenderSpotsText(array $genderSlots, array $currentGenderCount): string
    {
        $maleSpotsLeft = max(0, $genderSlots['male'] - $currentGenderCount['male']);
        $femaleSpotsLeft = max(0, $genderSlots['female'] - $currentGenderCount['female']);
        
        if ($maleSpotsLeft === 0 && $femaleSpotsLeft === 0) {
            return "Full";
        } elseif ($maleSpotsLeft === 0) {
            return "{$femaleSpotsLeft} female spots left";
        } elseif ($femaleSpotsLeft === 0) {
            return "{$maleSpotsLeft} male spots left";
        } else {
            return "{$maleSpotsLeft}M, {$femaleSpotsLeft}F spots left";
        }
    }
   
}
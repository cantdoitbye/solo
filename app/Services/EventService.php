<?php

namespace App\Services;

use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\VenueTypeRepositoryInterface;
use App\Repositories\Contracts\VenueCategoryRepositoryInterface;
use App\Repositories\Contracts\EventTagRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EventService
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private VenueTypeRepositoryInterface $venueTypeRepository,
        private VenueCategoryRepositoryInterface $venueCategoryRepository,
        private EventTagRepositoryInterface $eventTagRepository
    ) {}

    public function createEvent(array $data, string $sessionId = null): array
    {
        // Validate venue type and category
        $venueType = $this->venueTypeRepository->findById($data['venue_type_id']);
        if (!$venueType) {
            throw new \Exception('Invalid venue type selected');
        }

        $venueCategory = $this->venueCategoryRepository->findById($data['venue_category_id']);
        if (!$venueCategory) {
            throw new \Exception('Invalid venue category selected');
        }

        // Validate Google Place ID if provided
        if (isset($data['google_place_id']) && !empty($data['google_place_id'])) {
            $this->validateGooglePlaceId($data['google_place_id']);
        }

        // Process tags
        if (isset($data['tags']) && is_array($data['tags'])) {
            $validTags = $this->eventTagRepository->findByNames($data['tags']);
            $data['tags'] = array_column($validTags, 'name');
        }

        // Calculate total tokens display
        if (isset($data['token_cost_per_attendee']) && isset($data['max_group_size'])) {
            $data['total_tokens_display'] = $data['max_group_size'] * $data['token_cost_per_attendee'];
        } elseif (isset($data['token_cost_per_attendee']) && isset($data['min_group_size'])) {
            $data['total_tokens_display'] = $data['min_group_size'] * $data['token_cost_per_attendee'];
        }

        // Set default status and approval
        $data['status'] = 'draft';
        $data['is_approved'] = true; // Default to approved, can be changed later

        $event = $this->eventRepository->create($data);

        // Attach media files if session ID provided
        $attachResult = null;
        if ($sessionId) {
            $mediaService = app(EventMediaService::class);
            $attachResult = $mediaService->attachMediaToEvent($event->id, $sessionId);
        }

        return [
            'event_id' => $event->id,
            'status' => $event->status,
            'is_approved' => $event->is_approved,
            'media_attached' => $attachResult,
            'message' => 'Event created successfully as draft'
        ];
    }

    public function updateEvent(int $eventId, int $hostId, array $data): array
    {
        $event = $this->eventRepository->findByIdAndHost($eventId, $hostId);
        
        if (!$event) {
            throw new \Exception('Event not found or you do not have permission to edit this event');
        }

        // Prevent editing published events (except for specific fields)
        if ($event->status === 'published') {
            $allowedFields = ['description', 'past_event_description'];
            $data = array_intersect_key($data, array_flip($allowedFields));
            
            if (empty($data)) {
                throw new \Exception('Cannot modify this event as it is already published');
            }
        }

        // Process tags if provided
        if (isset($data['tags']) && is_array($data['tags'])) {
            $validTags = $this->eventTagRepository->findByNames($data['tags']);
            $data['tags'] = array_column($validTags, 'name');
        }

        // Recalculate total tokens if relevant fields changed
        if (isset($data['token_cost_per_attendee']) || isset($data['max_group_size']) || isset($data['min_group_size'])) {
            $maxSize = $data['max_group_size'] ?? $event->max_group_size;
            $minSize = $data['min_group_size'] ?? $event->min_group_size;
            $cost = $data['token_cost_per_attendee'] ?? $event->token_cost_per_attendee;
            
            if ($maxSize) {
                $data['total_tokens_display'] = $maxSize * $cost;
            } else {
                $data['total_tokens_display'] = $minSize * $cost;
            }
        }

        $updatedEvent = $this->eventRepository->update($eventId, $data);

        return [
            'event_id' => $updatedEvent->id,
            'status' => $updatedEvent->status,
            'message' => 'Event updated successfully'
        ];
    }

    public function publishEvent(int $eventId, int $hostId): array
    {
        $event = $this->eventRepository->findByIdAndHost($eventId, $hostId);
        
        if (!$event) {
            throw new \Exception('Event not found or you do not have permission to publish this event');
        }

        if ($event->status !== 'draft') {
            throw new \Exception('Only draft events can be published');
        }

        // Validate required fields for publishing
        $this->validateEventForPublishing($event);

        $publishedEvent = $this->eventRepository->publishEvent($eventId);

        return [
            'event_id' => $publishedEvent->id,
            'status' => $publishedEvent->status,
            'published_at' => $publishedEvent->published_at,
            'message' => 'Event published successfully! Your event is now live.'
        ];
    }

    public function getEventDetails(int $eventId, int $hostId = null): array
    {
        if ($hostId) {
            $event = $this->eventRepository->findByIdAndHost($eventId, $hostId);
        } else {
            $event = $this->eventRepository->findById($eventId);
        }

        if (!$event) {
            throw new \Exception('Event not found');
        }

        $eventArray = $event->toArray();
        
        // Add calculated fields
        $eventArray['available_spots'] = $event->available_spots;
        $eventArray['has_space'] = $event->hasSpace();
        $eventArray['attendee_count'] = $event->confirmedAttendees()->count();

        return $eventArray;
    }

    public function cancelEvent(int $eventId, int $hostId, string $reason = null): array
    {
        $event = $this->eventRepository->findByIdAndHost($eventId, $hostId);
        
        if (!$event) {
            throw new \Exception('Event not found or you do not have permission to cancel this event');
        }

        if ($event->status === 'cancelled') {
            throw new \Exception('Event is already cancelled');
        }

        if ($event->status === 'completed') {
            throw new \Exception('Cannot cancel a completed event');
        }

        $cancelledEvent = $this->eventRepository->cancelEvent($eventId, $reason);

        return [
            'event_id' => $cancelledEvent->id,
            'status' => $cancelledEvent->status,
            'cancelled_at' => $cancelledEvent->cancelled_at,
            'message' => 'Event cancelled successfully. All attendees have been notified.'
        ];
    }

    public function getHostEvents(int $hostId, string $status = null): array
    {
        return $this->eventRepository->getByHost($hostId, $status);
    }

    public function searchPlaces(string $query, string $location = null): array
    {
        // This method would integrate with Google Places API
        // For now, returning a mock structure
        return [
            'predictions' => [
                [
                    'place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
                    'description' => 'Sydney NSW, Australia',
                    'structured_formatting' => [
                        'main_text' => 'Sydney',
                        'secondary_text' => 'NSW, Australia'
                    ]
                ]
            ],
            'status' => 'OK'
        ];
    }

    public function getPlaceDetails(string $placeId): array
    {
        // This method would integrate with Google Places API
        // For now, returning a mock structure
        return [
            'result' => [
                'place_id' => $placeId,
                'name' => 'Sample Place',
                'formatted_address' => '123 Main St, City, State, Country',
                'geometry' => [
                    'location' => [
                        'lat' => -33.8670522,
                        'lng' => 151.1957362
                    ]
                ],
                'address_components' => [
                    ['long_name' => 'City', 'types' => ['locality']],
                    ['long_name' => 'State', 'types' => ['administrative_area_level_1']],
                    ['long_name' => 'Country', 'types' => ['country']],
                    ['long_name' => '12345', 'types' => ['postal_code']]
                ]
            ]
        ];
    }

    private function validateGooglePlaceId(string $placeId): void
    {
        // Basic validation - in real implementation, you'd call Google Places API
        if (strlen($placeId) < 20) {
            throw new \Exception('Invalid Google Place ID format');
        }
    }

    private function validateEventForPublishing($event): void
    {
        $requiredFields = [
            'name' => 'Event name',
            'description' => 'Event description',
            'event_date' => 'Event date',
            'event_time' => 'Event time',
            'venue_type_id' => 'Venue type',
            'venue_category_id' => 'Venue category',
            'min_group_size' => 'Minimum group size',
            'min_age' => 'Minimum age',
            'max_age' => 'Maximum age',
            'token_cost_per_attendee' => 'Token cost per attendee',
            'cancellation_policy' => 'Cancellation policy'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($event->$field)) {
                throw new \Exception("Please complete all required fields. Missing: $label");
            }
        }

        // Validate host responsibilities acceptance
        if (!$event->host_responsibilities_accepted) {
            throw new \Exception('Please accept host responsibilities before publishing the event');
        }

        // Validate admin approval
        if (!$event->is_approved) {
            throw new \Exception('Event must be approved by admin before publishing');
        }

        // Validate event date is in the future
        $eventDateTime = Carbon::parse($event->event_date . ' ' . $event->event_time);
        if ($eventDateTime->isPast()) {
            throw new \Exception('Event date and time must be in the future');
        }

        // Validate group sizes
        if ($event->max_group_size && $event->max_group_size < $event->min_group_size) {
            throw new \Exception('Maximum group size cannot be less than minimum group size');
        }

        // Validate age range
        if ($event->max_age < $event->min_age) {
            throw new \Exception('Maximum age cannot be less than minimum age');
        }

        // Validate location (either address or Google Place ID required)
        if (empty($event->venue_address) && empty($event->google_place_id)) {
            throw new \Exception('Event location is required - please provide venue address or select from location search');
        }
    }

    // Methods for getting dropdown data
    public function getVenueTypes(): array
    {
        return $this->venueTypeRepository->getAllActive();
    }

    public function getVenueCategories(int $venueTypeId = null): array
    {
        if ($venueTypeId) {
            return $this->venueCategoryRepository->getByVenueType($venueTypeId);
        }
        
        return $this->venueCategoryRepository->getAllActive();
    }

    public function getEventTags(): array
    {
        $featured = $this->eventTagRepository->getFeatured();
        $allByCategory = $this->eventTagRepository->getAllActive();

        return [
            'featured' => $featured,
            'categories' => $allByCategory,
            'all_tags' => array_reduce($allByCategory, function($carry, $categoryTags) {
                return array_merge($carry, $categoryTags);
            }, [])
        ];
    }

    public function searchEvents(array $filters): array
    {
        return $this->eventRepository->searchEvents($filters);
    }

    public function getUpcomingEvents(int $limit = 10): array
    {
        return $this->eventRepository->getUpcomingEvents($limit);
    }
}
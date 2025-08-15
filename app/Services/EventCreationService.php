<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Models\SuggestedLocation;
use App\Models\VenueType;
use App\Models\VenueCategory;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EventCreationService
{
    private EventRepositoryInterface $eventRepository;

    public function __construct(EventRepositoryInterface $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * Create complete event with SuggestedLocation reference
     */
    public function createCompleteEvent(int $hostId, array $data): array
    {
        return DB::transaction(function () use ($hostId, $data) {
            $sessionId = Str::uuid()->toString();
            
            // Get creator's details for auto-calculations
            $creator = User::find($hostId);
            if (!$creator) {
                throw new \Exception('User not found');
            }
            
            // Get the suggested location
            $suggestedLocation = SuggestedLocation::with(['primaryImage'])->find($data['location_id']);
            if (!$suggestedLocation) {
                throw new \Exception('Selected location not found');
            }
            
            if (!$suggestedLocation->is_active) {
                throw new \Exception('Selected location is not available');
            }
            
            // Auto-calculate age range (±8 years from creator's age)
            $ageRange = $this->calculateAgeRange($creator, $data['disable_age_restriction'] ?? false);
            
            // Auto-determine gender rules based on creator
            $genderRules = $this->determineGenderRules($creator);
            
            // Create complete event data
            $eventData = [
                'host_id' => $hostId,
                'session_id' => $sessionId,
                
                // Basic Info
                'name' => $data['name'],
                'description' => $data['description'],
                
                // Group size (simplified - min and max are same)
                'min_group_size' => $data['group_size'],
                'max_group_size' => $data['group_size'],
                
                // Auto-calculated age restrictions
                'min_age' => $ageRange['min_age'],
                'max_age' => $ageRange['max_age'],
                'age_restriction_disabled' => $data['disable_age_restriction'] ?? false,
                
                // Auto-determined gender rules
                'gender_rule_enabled' => $genderRules['enabled'],
                'gender_composition' => $genderRules['composition'],
                'gender_composition_value' => $genderRules['value'],
                'allowed_genders' => $genderRules['allowed_genders'],
                
                // Location - Reference to SuggestedLocation
                'suggested_location_id' => $suggestedLocation->id,
                'venue_type_id' => $suggestedLocation->venue_type_id,
                'venue_category_id' => $suggestedLocation->venue_category_id,
                'venue_name' => $suggestedLocation->venue_name,
                'venue_address' => $suggestedLocation->venue_address,
                'google_place_id' => $suggestedLocation->google_place_id,
                'latitude' => $suggestedLocation->latitude,
                'longitude' => $suggestedLocation->longitude,
                'city' => $suggestedLocation->city,
                'state' => $suggestedLocation->state,
                'country' => $suggestedLocation->country,
                'postal_code' => $suggestedLocation->postal_code,
                'google_place_details' => $suggestedLocation->google_place_details,
                
                // Date & Time
                'event_date' => $data['event_date'],
                'event_time' => $data['event_time'],
                'timezone' => $data['timezone'] ?? $creator->timezone ?? 'UTC',
                
                // Token Cost - FIXED AT 5 OLOS (backend handling)
                'token_cost_per_attendee' => 5.00, // Fixed 5 olos as per requirement
                'total_tokens_display' => $data['group_size'] * 5.00,
                
                // Default policies and settings
                'cancellation_policy' => $data['cancellation_policy'] ?? 'Standard cancellation policy applies',
                'host_responsibilities_accepted' => true, // Auto-accept since no UI step
                
                // Status and completion
                'status' => 'draft',
                'current_step' => 'completed', // All steps done in one API
                'step_completed_at' => json_encode([
                    'basic_info' => now()->toISOString(),
                    'completed' => now()->toISOString(),
                ]),
                
                // Remove fields that are no longer needed
                'tags' => [], // No tags as per requirement
                'media_urls' => [], // No media upload
                'past_event_description' => null, // No event history step
                'itinerary_url' => [], // No itinerary
            ];

            // Create the event
            $event = $this->eventRepository->create($eventData);

            return [
                'event_id' => $event->id,
                'session_id' => $sessionId,
                'status' => 'draft',
                'name' => $event->name,
                'description' => $event->description,
                'group_size' => $event->min_group_size,
                'event_date' => $event->event_date->toDateString(),
                'event_time' => $event->event_time->format('H:i'),
                'location' => [
                    'suggested_location_id' => $suggestedLocation->id,
                    'name' => $suggestedLocation->name,
                    'venue_name' => $suggestedLocation->venue_name,
                    'venue_address' => $suggestedLocation->venue_address,
                    'city' => $suggestedLocation->city,
                    'state' => $suggestedLocation->state,
                    'country' => $suggestedLocation->country,
                    'category' => $suggestedLocation->category,
                    'image_url' => $suggestedLocation->primaryImage ? $suggestedLocation->primaryImage->image_url : $suggestedLocation->image_url,
                ],
                'token_cost_per_attendee' => $event->token_cost_per_attendee,
                'total_cost' => $event->total_tokens_display,
                'age_range' => [
                    'min_age' => $event->min_age,
                    'max_age' => $event->max_age,
                    'auto_calculated' => !($data['disable_age_restriction'] ?? false),
                    'creator_age' => $creator->age
                ],
                'gender_settings' => [
                    'gender_rule_enabled' => $event->gender_rule_enabled,
                    'composition' => $event->gender_composition,
                    'allowed_genders' => $event->allowed_genders,
                    'creator_gender' => $creator->gender
                ],
                'created_at' => $event->created_at->toISOString(),
                'ready_to_publish' => true,
                'message' => 'Event created with auto-calculated age and gender restrictions'
            ];
        });
    }

    /**
     * Publish event (simplified - no complex validation)
     */
    public function publishEvent(int $eventId, int $hostId): array
    {
        $event = $this->validateEventAccess($eventId, $hostId);
        
        if ($event->status === 'published') {
            throw new \Exception('Event is already published');
        }
        
        // Simple validation - just check required fields exist
        $this->validateEventForPublishing($event);
        
        // Publish the event
        $this->eventRepository->update($eventId, [
            'status' => 'published',
            'published_at' => now(),
            'preview_generated_at' => now(),
        ]);
        
        $publishedEvent = $this->eventRepository->findById($eventId);
        
        // Load suggested location for response
        $suggestedLocation = SuggestedLocation::find($publishedEvent->suggested_location_id);
        
        return [
            'event_id' => $publishedEvent->id,
            'status' => 'published',
            'published_at' => $publishedEvent->published_at->toISOString(),
            'name' => $publishedEvent->name,
            'event_date' => $publishedEvent->event_date->toDateString(),
            'event_time' => $publishedEvent->event_time->format('H:i'),
            'location' => [
                'name' => $suggestedLocation->name ?? $publishedEvent->venue_name,
                'venue_name' => $publishedEvent->venue_name,
                'city' => $publishedEvent->city,
                'category' => $suggestedLocation->category ?? null,
            ],
            'group_size' => $publishedEvent->min_group_size,
            'token_cost_per_attendee' => $publishedEvent->token_cost_per_attendee,
            'age_range' => "{$publishedEvent->min_age}-{$publishedEvent->max_age}",
            'message' => 'Event published successfully and is now live!'
        ];
    }

    /**
     * Delete draft event
     */
    public function deleteDraftEvent(int $eventId, int $hostId): array
    {
        $event = $this->validateEventAccess($eventId, $hostId);
        
        if ($event->status !== 'draft') {
            throw new \Exception('Only draft events can be deleted');
        }
        
        $eventName = $event->name;
        $this->eventRepository->delete($eventId);
        
        return [
            'message' => "Draft event '{$eventName}' deleted successfully"
        ];
    }

    // PRIVATE HELPER METHODS

    /**
     * Auto-calculate age range based on creator's age (±8 years)
     */
    private function calculateAgeRange(User $creator, bool $disableRestriction = false): array
    {
        if ($disableRestriction) {
            // No age restriction - allow all adults
            return [
                'min_age' => 18,
                'max_age' => 100
            ];
        }
        
        $creatorAge = $creator->age ?? 25; // Default to 25 if age not set
        
        return [
            'min_age' => max(18, $creatorAge - 8), // Minimum 18 years old
            'max_age' => min(100, $creatorAge + 8)  // Maximum 100 years old
        ];
    }

    /**
     * Auto-determine gender rules based on creator's preferences
     */
    private function determineGenderRules(User $creator): array
    {
        // Default to inclusive settings
        $defaultGenders = ['male', 'female', 'gay', 'lesbian', 'trans', 'bisexual'];
        
        // You can customize this logic based on creator's preferences
        // For now, keeping it simple and inclusive
        return [
            'enabled' => false, // Default to no gender restrictions
            'composition' => null,
            'value' => null,
            'allowed_genders' => $defaultGenders
        ];
    }

    /**
     * Validate user has access to event
     */
    private function validateEventAccess(int $eventId, int $hostId): Event
    {
        $event = $this->eventRepository->findById($eventId);
        
        if (!$event) {
            throw new \Exception('Event not found');
        }
        
        if ($event->host_id !== $hostId) {
            throw new \Exception('You do not have permission to access this event');
        }
        
        return $event;
    }

    /**
     * Validate event is ready for publishing (simplified)
     */
    private function validateEventForPublishing(Event $event): void
    {
        $requiredFields = [
            'name' => 'Event name',
            'description' => 'Event description',
            'event_date' => 'Event date',
            'event_time' => 'Event time',
            'min_group_size' => 'Group size',
            'suggested_location_id' => 'Event location',
            'min_age' => 'Minimum age',
            'max_age' => 'Maximum age',
            'token_cost_per_attendee' => 'Token cost per attendee',
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($event->$field)) {
                throw new \Exception("Please complete all required fields. Missing: {$label}");
            }
        }
        
        // Validate date is in future
        if ($event->event_date->isPast()) {
            throw new \Exception('Event date must be in the future');
        }
        
        // Validate group size
        if ($event->min_group_size < 2) {
            throw new \Exception('Group size must be at least 2 people');
        }
        
        // Validate suggested location still exists and is active
        $suggestedLocation = SuggestedLocation::find($event->suggested_location_id);
        if (!$suggestedLocation || !$suggestedLocation->is_active) {
            throw new \Exception('Selected location is no longer available');
        }
    }
}
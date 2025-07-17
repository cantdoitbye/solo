<?php

namespace App\Services;

use App\Repositories\Contracts\EventRepositoryInterface;
use App\Services\EventMediaService;
use Illuminate\Support\Str;

class EventCreationService
{
    private EventRepositoryInterface $eventRepository;
    private EventMediaService $mediaService;

    public function __construct(
        EventRepositoryInterface $eventRepository,
        EventMediaService $mediaService
    ) {
        $this->eventRepository = $eventRepository;
        $this->mediaService = $mediaService;
    }


  /**
 * Combined Steps 1-5 (Create OR Edit)
 */
public function createEventBulk(int $hostId, array $data, ?int $eventId = null): array
{
    // Process gender rules
    $genderRuleData = $this->processGenderRules($data);
    // $groupSize = $data['max_group_size'] ?? $data['min_group_size'];
        $minGroupSize = $data['min_group_size'];

    $maxGroupSize = null; 
    $eventData = [
    // Step 1: Basic Info
    'name' => $data['name'],
    'description' => $data['description'],
    'tags' => $data['tags'] ?? [],
    
    // Step 2: Venue & Location (ALL FIELDS)
    'venue_type_id' => $data['venue_type_id'],
    'venue_category_id' => $data['venue_category_id'],
    'venue_name' => $data['venue_name'] ?? null,
    'venue_address' => $data['venue_address'] ?? null,
    'google_place_id' => $data['google_place_id'] ?? null,
    'latitude' => $data['latitude'] ?? null,
    'longitude' => $data['longitude'] ?? null,
    'city' => $data['city'] ?? null,
    'state' => $data['state'] ?? null,
    'country' => $data['country'] ?? null,
    'postal_code' => $data['postal_code'] ?? null,
    'google_place_details' => $data['google_place_details'] ?? null,
    
    // Step 3: Date & Time
    'event_date' => $data['event_date'],
    'event_time' => $data['event_time'],
    'timezone' => $data['timezone'] ?? 'UTC',
    
    // Step 4: Attendees Setup
     'min_group_size' => $minGroupSize,
        'max_group_size' => $maxGroupSize,
    'min_age' => $data['min_age'],
    'max_age' => $data['max_age'],
    'gender_rule_enabled' => $genderRuleData['gender_rule_enabled'],
    'gender_composition' => $genderRuleData['gender_composition'],
    'gender_composition_value' => $genderRuleData['gender_composition_value'],
    'allowed_genders' => $genderRuleData['allowed_genders'],
    
    // Step 5: Token & Payment
    'token_cost_per_attendee' => $data['token_cost_per_attendee'],
        'total_tokens_display' => $minGroupSize * $data['token_cost_per_attendee'],
];
    
    if ($eventId) {
        // EDIT MODE - Update existing event
        $event = $this->validateEventAccess($eventId, $hostId);
        $sessionId = $event->session_id;
        
        // Update with completed steps
        $eventData['current_step'] = 'token_payment';
        $eventData['step_completed_at'] = json_encode([
            'basic_info' => now()->toISOString(),
            'venue_location' => now()->toISOString(),
            'date_time' => now()->toISOString(),
            'attendees_setup' => now()->toISOString(),
            'token_payment' => now()->toISOString(),
        ]);
        
        $this->eventRepository->update($eventId, $eventData);
        $message = 'Event updated successfully (Steps 1-5)';
        
    } else {
        // CREATE MODE - New event
        $sessionId = Str::uuid()->toString();
        $eventData['host_id'] = $hostId;
        $eventData['session_id'] = $sessionId;
        $eventData['current_step'] = 'token_payment';
        $eventData['step_completed_at'] = json_encode([
            'basic_info' => now()->toISOString(),
            'venue_location' => now()->toISOString(),
            'date_time' => now()->toISOString(),
            'attendees_setup' => now()->toISOString(),
            'token_payment' => now()->toISOString(),
        ]);
        $eventData['status'] = 'draft';
        
        $event = $this->eventRepository->create($eventData);
        $eventId = $event->id;
        $message = 'Event created successfully (Steps 1-5)';
    }

    return [
        'event_id' => $eventId,
        'session_id' => $sessionId,
        'current_step' => 'token_payment',
        'next_step' => 'event_history',
        'completed_steps' => ['basic_info', 'venue_location', 'date_time', 'attendees_setup', 'token_payment'],
        'progress_percentage' => 62.5,
        'message' => $message
    ];
}

    /**
     * Step 1: Handle Basic Info (Create or Edit)
     */
    public function handleBasicInfo(int $hostId, array $data, ?int $eventId = null): array
    {
        if ($eventId) {
            // Edit existing event
            $event = $this->validateEventAccess($eventId, $hostId);
            $this->updateEventStep($event, [
                'name' => $data['name'],
                'description' => $data['description'],
                'tags' => $data['tags'] ?? [],
            ], 'basic_info');
            
            $sessionId = $event->session_id;
        } else {
            // Create new event
            $sessionId = Str::uuid()->toString();
            $eventData = [
                'host_id' => $hostId,
                'session_id' => $sessionId,
                'name' => $data['name'],
                'description' => $data['description'],
                'tags' => $data['tags'] ?? [],
                'current_step' => 'basic_info',
                'step_completed_at' => json_encode(['basic_info' => now()->toISOString()]),
                'status' => 'draft'
            ];
            $event = $this->eventRepository->create($eventData);
            $eventId = $event->id;
        }

        return [
            'event_id' => $eventId,
            'session_id' => $sessionId,
            'current_step' => 'basic_info',
            'next_step' => 'venue_location',
            'completed_steps' => ['basic_info'],
            'progress_percentage' => 12.5,
            'message' => $eventId ? 'Event basic info updated successfully' : 'Event creation initialized successfully'
        ];
    }

    /**
     * Step 2: Handle Venue & Location
     */
    public function handleVenueLocation(int $eventId, int $hostId, array $data): array
    {
        $event = $this->validateEventAccess($eventId, $hostId);
        
        $updateData = [
            'venue_type_id' => $data['venue_type_id'],
            'venue_category_id' => $data['venue_category_id'],
            'venue_name' => $data['venue_name'] ?? null,
            'venue_address' => $data['venue_address'] ?? null,
            'google_place_id' => $data['google_place_id'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'google_place_details' => $data['google_place_details'] ?? null,
        ];

        $this->updateEventStep($event, $updateData, 'venue_location');
        $completedSteps = $this->getCompletedSteps($event);

        return [
            'event_id' => $eventId,
            'current_step' => 'venue_location',
            'next_step' => 'date_time',
            'completed_steps' => $completedSteps,
            'progress_percentage' => (count($completedSteps) / 8) * 100,
            'message' => 'Venue and location updated successfully'
        ];
    }

    /**
     * Step 3: Handle Date & Time
     */
    public function handleDateTime(int $eventId, int $hostId, array $data): array
    {
        $event = $this->validateEventAccess($eventId, $hostId);
        
        $updateData = [
            'event_date' => $data['event_date'],
            'event_time' => $data['event_time'],
            'timezone' => $data['timezone'] ?? 'UTC',
        ];

        $this->updateEventStep($event, $updateData, 'date_time');
        $completedSteps = $this->getCompletedSteps($event);

        return [
            'event_id' => $eventId,
            'current_step' => 'date_time',
            'next_step' => 'attendees_setup',
            'completed_steps' => $completedSteps,
            'progress_percentage' => (count($completedSteps) / 8) * 100,
            'message' => 'Date and time updated successfully'
        ];
    }

    /**
     * Step 4: Handle Attendees Setup with Gender Logic
     */
    public function handleAttendeesSetup(int $eventId, int $hostId, array $data): array
    {
        $event = $this->validateEventAccess($eventId, $hostId);
        
        // Process gender rules and composition logic
        $genderRuleData = $this->processGenderRules($data);
        
        $updateData = array_merge($genderRuleData, [
            'min_group_size' => $data['min_group_size'],
            'max_group_size' => $data['max_group_size'] ?? $data['min_group_size'],
            'min_age' => $data['min_age'],
            'max_age' => $data['max_age'],
        ]);

        $this->updateEventStep($event, $updateData, 'attendees_setup');
        $completedSteps = $this->getCompletedSteps($event);

        return [
            'event_id' => $eventId,
            'current_step' => 'attendees_setup',
            'next_step' => 'token_payment',
            'completed_steps' => $completedSteps,
            'progress_percentage' => (count($completedSteps) / 8) * 100,
            'gender_composition_value' => $updateData['gender_composition_value'] ?? null,
            'gender_rule_enabled' => $updateData['gender_rule_enabled'],
            'message' => 'Attendees setup configured successfully'
        ];
    }

    /**
     * Step 5: Handle Token & Payment
     */
    public function handleTokenPayment(int $eventId, int $hostId, array $data): array
    {
        $event = $this->validateEventAccess($eventId, $hostId);
        
        $tokenCost = $data['token_cost_per_attendee'] ?? 0;
        $groupSize = $event->max_group_size ?? $event->min_group_size ?? 1;
        
        $updateData = [
            'token_cost_per_attendee' => $tokenCost,
            'total_tokens_display' => $groupSize * $tokenCost,
        ];

        $this->updateEventStep($event, $updateData, 'token_payment');
        $completedSteps = $this->getCompletedSteps($event);

        return [
            'event_id' => $eventId,
            'current_step' => 'token_payment',
            'next_step' => 'event_history',
            'completed_steps' => $completedSteps,
            'progress_percentage' => (count($completedSteps) / 8) * 100,
            'total_cost' => $groupSize * $tokenCost,
            'cost_per_person' => $tokenCost,
            'group_size' => $groupSize,
            'message' => 'Token and payment setup completed'
        ];
    }

    /**
     * Step 6: Handle Event History & Media
     */
    public function handleEventHistory(int $eventId, int $hostId, array $data): array
    {
        $event = $this->validateEventAccess($eventId, $hostId);
        
        $updateData = [
            'past_event_description' => $data['past_event_description'] ?? null,
        ];

        // Handle media attachments
        $mediaAttached = [];
        if (isset($data['media_session_id']) && !empty($data['media_session_id'])) {
            $mediaAttached = $this->mediaService->attachMediaToEvent($eventId, $data['media_session_id']);
        }

        $this->updateEventStep($event, $updateData, 'event_history');
        $completedSteps = $this->getCompletedSteps($event);

        return [
            'event_id' => $eventId,
            'current_step' => 'event_history',
            'next_step' => 'host_responsibilities',
            'completed_steps' => $completedSteps,
            'progress_percentage' => (count($completedSteps) / 8) * 100,
            'media_attached' => $mediaAttached,
            'message' => 'Event history and media updated successfully'
        ];
    }

    /**
     * Step 7: Handle Host Responsibilities
     */
    public function handleHostResponsibilities(int $eventId, int $hostId, array $data): array
    {
        $event = $this->validateEventAccess($eventId, $hostId);
        
        $updateData = [
            'cancellation_policy' => $data['cancellation_policy'] ?? 'no_refund',
            'host_responsibilities_accepted' => $data['host_responsibilities_accepted'] ?? false,
        ];

        // Handle itinerary attachment
        $itineraryAttached = [];
        if (isset($data['itinerary_session_id']) && !empty($data['itinerary_session_id'])) {
            $itineraryAttached = $this->mediaService->attachItineraryToEvent($eventId, $data['itinerary_session_id']);
        }

        $this->updateEventStep($event, $updateData, 'host_responsibilities');
        $completedSteps = $this->getCompletedSteps($event);

        return [
            'event_id' => $eventId,
            'current_step' => 'host_responsibilities',
            'next_step' => 'preview',
            'completed_steps' => $completedSteps,
            'progress_percentage' => (count($completedSteps) / 8) * 100,
            'itinerary_attached' => $itineraryAttached,
            'message' => 'Host responsibilities updated successfully'
        ];
    }

    /**
     * Step 8: Generate Preview
     */
    public function generatePreview(int $eventId, int $hostId): array
    {
        $event = $this->validateEventAccess($eventId, $hostId);
        
        // Validate all required fields are completed
        $this->validateEventForPreview($event);
        
        $previewData = $this->buildPreviewData($event);
        
        $updateData = [
            'preview_generated_at' => now()
        ];

        $this->updateEventStep($event, $updateData, 'preview');
        $completedSteps = $this->getCompletedSteps($event);

        return [
            'event_id' => $eventId,
            'current_step' => 'preview',
            'next_step' => 'publish',
            'completed_steps' => $completedSteps,
            'progress_percentage' => 100.0,
            'preview_data' => $previewData,
            'can_publish' => true,
            'message' => 'Event preview generated successfully'
        ];
    }

    /**
     * Final Step: Publish Event
     */
    public function publishEvent(int $eventId, int $hostId): array
    {
        $event = $this->validateEventAccess($eventId, $hostId);
        
        if ($event->current_step !== 'preview') {
            throw new \Exception('Event must be previewed before publishing');
        }

        if (!$event->preview_generated_at) {
            throw new \Exception('Please generate preview first');
        }

        $publishedEvent = $this->eventRepository->publishEvent($eventId);

        return [
            'event_id' => $eventId,
            'status' => 'published',
            'published_at' => $publishedEvent->published_at,
            'event_url' => url("/events/{$eventId}"),
            'message' => 'Event published successfully! Your event is now live.'
        ];
    }

    /**
     * Get Event Progress and Data
     */
    public function getEventProgress(int $eventId, int $hostId): array
    {
        $event = $this->validateEventAccess($eventId, $hostId);
        
        $completedSteps = $this->getCompletedSteps($event);
        $stepOrder = ['basic_info', 'venue_location', 'date_time', 'attendees_setup', 
                     'token_payment', 'event_history', 'host_responsibilities', 'preview'];

        $currentStepIndex = array_search($event->current_step, $stepOrder);
        $nextStep = $currentStepIndex < count($stepOrder) - 1 ? $stepOrder[$currentStepIndex + 1] : 'publish';

        // Get step data for frontend
        $stepData = $this->getStepData($event);

        return [
            'event_id' => $eventId,
            'session_id' => $event->session_id,
            'current_step' => $event->current_step,
            'next_step' => $nextStep,
            'completed_steps' => $completedSteps,
            'progress_percentage' => (count($completedSteps) / count($stepOrder)) * 100,
            'can_preview' => count($completedSteps) >= 7,
            'can_publish' => $event->current_step === 'preview' && $event->status === 'draft',
            'status' => $event->status,
            'step_data' => $stepData
        ];
    }

    /**
     * Delete Draft Event
     */
    public function deleteDraftEvent(int $eventId, int $hostId): array
    {
        $event = $this->validateEventAccess($eventId, $hostId);
        
        if ($event->status !== 'draft') {
            throw new \Exception('Only draft events can be deleted');
        }

        // Delete the event
        $this->eventRepository->delete($eventId);

        return [
            'message' => 'Draft event deleted successfully'
        ];
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    private function validateEventAccess(int $eventId, int $hostId): object
    {
        $event = $this->eventRepository->findByIdAndHost($eventId, $hostId);
        
        if (!$event) {
            throw new \Exception('Event not found or you do not have permission to edit this event');
        }

        if ($event->status === 'published') {
            throw new \Exception('Published events cannot be edited. Please create a new event.');
        }

        return $event;
    }

    private function updateEventStep(object $event, array $updateData, string $step): void
    {
        $completedSteps = json_decode($event->step_completed_at ?? '{}', true);
        $completedSteps[$step] = now()->toISOString();
        
        $updateData['step_completed_at'] = json_encode($completedSteps);
        $updateData['current_step'] = $step;

        $this->eventRepository->update($event->id, $updateData);
    }

    private function getCompletedSteps(object $event): array
    {
        $stepData = json_decode($event->step_completed_at ?? '{}', true);
        return array_keys($stepData);
    }

    /**
     * Process Gender Rules Logic
     */
  

/**
 * Process Gender Rules Logic - Updated with required composition value
 */
private function processGenderRules(array $data): array
{
    $groupSize = $data['min_group_size']; // Only min_group_size, max is infinite
    $selectedGenders = $data['allowed_genders'] ?? [];
    
    // Check if user has enabled gender rules through UI toggle
    $userEnabledGenderRules = $data['gender_rule_enabled'] ?? false;
    
    // If user has not enabled gender rules, return disabled state
    if (!$userEnabledGenderRules) {
        return [
            'gender_rule_enabled' => false,
            'gender_composition' => null,
            'gender_composition_value' => null,
            'allowed_genders' => $selectedGenders,
        ];
    }
    
    // User has enabled gender rules - composition value is now REQUIRED
    $genderCompositionValue = $data['gender_composition_value'] ?? null;
    
    if ($genderCompositionValue === null) {
        throw new \Exception('Gender composition value is required when gender rules are enabled.');
    }
    
    // Validate composition value against min group size (max is infinite)
    if ($genderCompositionValue > $groupSize) {
        throw new \Exception('Gender composition value cannot exceed the minimum group size.');
    }
    
    if ($genderCompositionValue <= 0) {
        throw new \Exception('Gender composition value must be greater than 0.');
    }
    
    // Check if special genders are selected
    $specialGenders = ['gay', 'trans', 'lesbian', 'bisexual'];
    $hasSpecialGenders = !empty(array_intersect($selectedGenders, $specialGenders));
    
    if ($hasSpecialGenders) {
        // Cannot enable gender rules for special genders
        throw new \Exception('Gender rules cannot be enabled when special genders (gay, trans, lesbian, bisexual) are selected.');
    }
    
    // Check if both male and female are selected
    $hasMale = in_array('male', $selectedGenders);
    $hasFemale = in_array('female', $selectedGenders);
    
    if ($hasMale && $hasFemale) {
        // Both genders selected - use the provided composition value
        $maleCount = $genderCompositionValue;
        $femaleCount = $groupSize - $genderCompositionValue;
        
        if ($femaleCount < 0) {
            throw new \Exception('Invalid gender composition value. It would result in negative female count.');
        }
        
        // For infinite max group size, show minimum requirements
        return [
            'gender_rule_enabled' => true,
            'gender_composition' => "Minimum: {$maleCount} males and {$femaleCount} females (additional attendees can be of any selected gender)",
            'gender_composition_value' => $genderCompositionValue,
            'allowed_genders' => $selectedGenders,
        ];
    }
    
    // Only one gender selected
    if ($hasMale && !$hasFemale) {
        return [
            'gender_rule_enabled' => true,
            'gender_composition' => "Minimum: {$genderCompositionValue} males required (group size is infinite)",
            'gender_composition_value' => $genderCompositionValue,
            'allowed_genders' => $selectedGenders,
        ];
    }
    
    if ($hasFemale && !$hasMale) {
        return [
            'gender_rule_enabled' => true,
            'gender_composition' => "Minimum: {$genderCompositionValue} females required (group size is infinite)",
            'gender_composition_value' => $genderCompositionValue,
            'allowed_genders' => $selectedGenders,
        ];
    }
    
    // No valid genders selected
    throw new \Exception('Please select at least one gender when gender rules are enabled.');
}

    private function validateEventForPreview(object $event): void
    {
        $requiredFields = [
            'name' => 'Event name is required',
            'description' => 'Event description is required',
            'venue_type_id' => 'Venue type is required',
            'venue_category_id' => 'Venue category is required',
            'event_date' => 'Event date is required',
            'event_time' => 'Event time is required',
            'min_group_size' => 'Minimum group size is required',
            'min_age' => 'Minimum age is required',
            'max_age' => 'Maximum age is required',
            'token_cost_per_attendee' => 'Token cost is required'
        ];

        $missingFields = [];
        foreach ($requiredFields as $field => $message) {
            if (empty($event->$field)) {
                $missingFields[] = $message;
            }
        }

        if (!empty($missingFields)) {
            throw new \Exception('Missing required fields: ' . implode(', ', $missingFields));
        }

        // Validate host responsibilities acceptance
        if (!$event->host_responsibilities_accepted) {
            throw new \Exception('Host responsibilities must be accepted before publishing');
        }
    }

    private function buildPreviewData(object $event): array
    {
        $groupSize = $event->max_group_size ?? $event->min_group_size;
        $totalCost = $groupSize * $event->token_cost_per_attendee;

        return [
            'event_summary' => [
                'name' => $event->name,
                'date' => $event->event_date->format('M j, Y'),
                'time' => $event->event_time,
                'location' => $event->venue_name ?: $event->venue_address,
                'group_size' => $groupSize,
                'total_cost' => $totalCost,
                'cost_per_person' => $event->token_cost_per_attendee
            ],
            'venue_details' => [
                'type' => optional($event->venueType)->name,
                'category' => optional($event->venueCategory)->name,
                'name' => $event->venue_name,
                'address' => $event->venue_address,
                'city' => $event->city
            ],
            'attendee_requirements' => [
                'group_size_range' => $event->min_group_size . ($event->max_group_size ? '-' . $event->max_group_size : ''),
                'age_range' => "{$event->min_age}-{$event->max_age}",
                'gender_rules_enabled' => $event->gender_rule_enabled,
                'gender_composition' => $event->gender_composition,
                'allowed_genders' => $event->allowed_genders ?? []
            ],
            'policies' => [
                'cancellation' => $event->cancellation_policy,
                'host_responsibilities_accepted' => $event->host_responsibilities_accepted
            ],
            'media' => [
                'images_count' => method_exists($event, 'media') ? $event->media()->where('media_type', 'image')->count() : 0,
                'videos_count' => method_exists($event, 'media') ? $event->media()->where('media_type', 'video')->count() : 0,
                'has_itinerary' => method_exists($event, 'itineraries') ? $event->itineraries()->exists() : false
            ]
        ];
    }

    private function getStepData(object $event): array
    {
        return [
            'basic_info' => [
                'name' => $event->name,
                'description' => $event->description,
                'tags' => $event->tags
            ],
            'venue_location' => [
                'venue_type_id' => $event->venue_type_id,
                'venue_category_id' => $event->venue_category_id,
                'venue_name' => $event->venue_name,
                'venue_address' => $event->venue_address,
                'city' => $event->city,
                'state' => $event->state,
                'country' => $event->country,
                'latitude' => $event->latitude,
                'longitude' => $event->longitude
            ],
            'date_time' => [
                'event_date' => $event->event_date ? $event->event_date->format('Y-m-d') : null,
                'event_time' => $event->event_time,
                'timezone' => $event->timezone
            ],
            'attendees_setup' => [
                'min_group_size' => $event->min_group_size,
                'max_group_size' => $event->max_group_size,
                'gender_rule_enabled' => $event->gender_rule_enabled,
                'gender_composition' => $event->gender_composition,
                'gender_composition_value' => $event->gender_composition_value,
                'min_age' => $event->min_age,
                'max_age' => $event->max_age,
                'allowed_genders' => $event->allowed_genders
            ],
            'token_payment' => [
                'token_cost_per_attendee' => $event->token_cost_per_attendee,
                'total_tokens_display' => $event->total_tokens_display
            ],
            'event_history' => [
                'past_event_description' => $event->past_event_description,
                'media_count' => method_exists($event, 'media') ? $event->media()->count() : 0
            ],
            'host_responsibilities' => [
                'cancellation_policy' => $event->cancellation_policy,
                'host_responsibilities_accepted' => $event->host_responsibilities_accepted,
                'itinerary_count' => method_exists($event, 'itineraries') ? $event->itineraries()->count() : 0
            ]
        ];
    }
}
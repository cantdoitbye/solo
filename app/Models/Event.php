<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'host_id',
        'name',
        'description',
        'tags',
        'event_date',
        'event_time',
        'timezone',
        'venue_type_id',
        'venue_category_id',
        'venue_name',
        'venue_address',
        'google_place_id',
        'latitude',
        'longitude',
        'city',
        'state',
        'country',
        'postal_code',
        'google_place_details',
        'min_group_size',
        'max_group_size',
        'gender_rule_enabled',
        'gender_composition',
        'min_age',
        'max_age',
        'allowed_genders',
        'token_cost_per_attendee',
        'total_tokens_display',
        'media_urls',
        'past_event_description',
        'cancellation_policy',
        'itinerary_url',
        'host_responsibilities_accepted',
        'status',
        'published_at',
        'cancelled_at',
      

         'current_step',
    'step_completed_at',
    'preview_generated_at',
    'session_id',
    'gender_composition_value',
    ];

    protected $casts = [
        'tags' => 'array',
        'allowed_genders' => 'array',
        'media_urls' => 'array',
        'itinerary_url' => 'array',
        'google_place_details' => 'array',
        'step_completed_at' => 'array',
        'event_date' => 'date',
        'event_time' => 'datetime:H:i',
        'published_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'preview_generated_at' => 'datetime',
        'gender_rule_enabled' => 'boolean',
        'host_responsibilities_accepted' => 'boolean',
        'token_cost_per_attendee' => 'decimal:2',
        'total_tokens_display' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
          'step_completed_at' => 'array',
    'preview_generated_at' => 'datetime',
    'gender_composition_value' => 'integer',
    ];

    // Event Creation Steps Constants
    const STEPS = [
        'basic_info',
        'venue_location',
        'date_time',
        'attendees_setup',
        'token_payment',
        'event_history',
        'host_responsibilities',
        'preview'
    ];

    const STEP_LABELS = [
        'basic_info' => 'Basic Information',
        'venue_location' => 'Venue & Location',
        'date_time' => 'Date & Time',
        'attendees_setup' => 'Attendees Setup',
        'token_payment' => 'Token & Payment',
        'event_history' => 'Event History',
        'host_responsibilities' => 'Host Responsibilities',
        'preview' => 'Preview'
    ];

    // Relationships
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function venueType(): BelongsTo
    {
        return $this->belongsTo(VenueType::class);
    }

    public function venueCategory(): BelongsTo
    {
        return $this->belongsTo(VenueCategory::class);
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function confirmedAttendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class)->where('status', 'confirmed');
    }

    public function media(): HasMany
    {
        return $this->hasMany(EventMedia::class);
    }

    public function itineraries(): HasMany
    {
        return $this->hasMany(EventItinerary::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString());
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('current_step', self::STEPS);
    }

    public function scopeReadyForPreview($query)
    {
        return $query->where('current_step', 'host_responsibilities')
                    ->where('status', 'draft');
    }

    public function scopeReadyForPublish($query)
    {
        return $query->where('current_step', 'preview')
                    ->where('status', 'draft')
                    ->whereNotNull('preview_generated_at');
    }

    // Accessors
    public function getEventDatetimeAttribute()
    {
        return $this->event_date->format('Y-m-d') . ' ' . $this->event_time;
    }

    public function getAvailableSpotsAttribute()
    {
        $maxSize = $this->max_group_size ?: $this->min_group_size;
        return max(0, $maxSize - $this->confirmedAttendees()->count());
    }

    public function getProgressPercentageAttribute()
    {
        $completedSteps = $this->step_completed_at ? array_keys($this->step_completed_at) : [];
        return (count($completedSteps) / count(self::STEPS)) * 100;
    }

    public function getCompletedStepsAttribute()
    {
        return $this->step_completed_at ? array_keys($this->step_completed_at) : [];
    }

    public function getNextStepAttribute()
    {
        $currentIndex = array_search($this->current_step, self::STEPS);
        if ($currentIndex === false || $currentIndex >= count(self::STEPS) - 1) {
            return 'publish';
        }
        return self::STEPS[$currentIndex + 1];
    }

    public function getCurrentStepLabelAttribute()
    {
        return self::STEP_LABELS[$this->current_step] ?? 'Unknown Step';
    }

    public function getCanPreviewAttribute()
    {
        $completedSteps = $this->completed_steps;
        $requiredStepsForPreview = array_slice(self::STEPS, 0, 7); // All except 'preview'
        return count(array_intersect($requiredStepsForPreview, $completedSteps)) >= 7;
    }

    public function getCanPublishAttribute()
    {
        return $this->current_step === 'preview' && 
               $this->status === 'draft' && 
               $this->preview_generated_at !== null;
    }

    public function getIsCompleteAttribute()
    {
        $completedSteps = $this->completed_steps;
        return count($completedSteps) === count(self::STEPS);
    }

    // Methods
    public function hasSpace(): bool
    {
        return $this->available_spots > 0;
    }

    public function isStepCompleted(string $step): bool
    {
        return in_array($step, $this->completed_steps);
    }

    public function markStepCompleted(string $step): void
    {
        if (!in_array($step, self::STEPS)) {
            throw new \InvalidArgumentException("Invalid step: $step");
        }

        $completedSteps = $this->step_completed_at ?? [];
        $completedSteps[$step] = now()->toISOString();
        
        $this->update([
            'step_completed_at' => $completedSteps,
            'current_step' => $step
        ]);
    }

    public function canMoveToStep(string $step): bool
    {
        $stepIndex = array_search($step, self::STEPS);
        $currentIndex = array_search($this->current_step, self::STEPS);
        
        if ($stepIndex === false || $currentIndex === false) {
            return false;
        }

        // Can only move to next step or stay on current step
        return $stepIndex <= $currentIndex + 1;
    }

    public function getMissingRequiredFields(): array
    {
        $missing = [];
        
        $requiredFieldsByStep = [
            'basic_info' => ['name', 'description'],
            'venue_location' => ['venue_type_id', 'venue_category_id'],
            'date_time' => ['event_date', 'event_time'],
            'attendees_setup' => ['min_group_size', 'min_age', 'max_age'],
            'token_payment' => ['token_cost_per_attendee'],
            'event_history' => [], // Optional step
            'host_responsibilities' => ['cancellation_policy', 'host_responsibilities_accepted'],
            'preview' => [] // Validation happens in service
        ];

        foreach ($requiredFieldsByStep as $step => $fields) {
            if ($this->isStepCompleted($step)) {
                continue;
            }

            foreach ($fields as $field) {
                if (empty($this->$field)) {
                    $missing[$step][] = $field;
                }
            }
        }

        return $missing;
    }

    public function generatePreviewSummary(): array
    {
        $groupSize = $this->max_group_size ?? $this->min_group_size;
        $totalCost = $groupSize * $this->token_cost_per_attendee;

        return [
            'event_summary' => [
                'name' => $this->name,
                'date' => $this->event_date->format('M j, Y'),
                'time' => $this->event_time,
                'location' => $this->venue_name ?: $this->venue_address,
                'group_size' => $groupSize,
                'total_cost' => $totalCost,
                'cost_per_person' => $this->token_cost_per_attendee
            ],
            'venue_details' => [
                'type' => $this->venueType->name ?? null,
                'category' => $this->venueCategory->name ?? null,
                'address' => $this->venue_address,
                'city' => $this->city
            ],
            'attendee_requirements' => [
                'age_range' => "{$this->min_age}-{$this->max_age}",
                'gender_rules' => $this->gender_rule_enabled ? $this->gender_composition : 'No restrictions',
                'allowed_genders' => $this->allowed_genders ?? []
            ],
            'policies' => [
                'cancellation' => $this->cancellation_policy,
                'host_responsibilities_accepted' => $this->host_responsibilities_accepted
            ],
            'media_count' => $this->media()->count(),
            'has_itinerary' => $this->itineraries()->exists()
        ];
    }


   

public function hasChatRoom(): bool
{
    return !is_null($this->chatRoom);
}

public function getChatRoomId(): ?int
{
    return $this->chatRoom?->id;
}

    
}
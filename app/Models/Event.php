<?php

namespace App\Models;

use Carbon\Carbon;
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
        'event_date',
        'event_time',
        'timezone',
        
        // SuggestedLocation reference + copied location data
        'suggested_location_id',
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
        
        // Simplified group size (min and max are same)
        'min_group_size',
        'max_group_size',
        
        // Auto-calculated age restrictions
        'min_age',
        'max_age',
        'age_restriction_disabled',
        
        // Auto-determined gender rules
        'gender_rule_enabled',
        'gender_composition',
        'allowed_genders',
        'gender_composition_value',
        
        // Fixed token cost
        'token_cost_per_attendee', // Always 5.00
        'total_tokens_display',
        
        // Basic policies
        'cancellation_policy',
        'host_responsibilities_accepted', // Always true
        
        // Status
        'status',
        'notes',
        'published_at',
        'cancelled_at',
        
        // Session tracking (simplified)
        'current_step',
        'step_completed_at',
        'preview_generated_at',
        'session_id',
    ];

    protected $casts = [
        'allowed_genders' => 'array',
        'google_place_details' => 'array',
        'step_completed_at' => 'array',
        'event_date' => 'date',
        'event_time' => 'datetime:H:i',
        'published_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'preview_generated_at' => 'datetime',
        'gender_rule_enabled' => 'boolean',
        'host_responsibilities_accepted' => 'boolean',
        'age_restriction_disabled' => 'boolean',
        'token_cost_per_attendee' => 'decimal:2',
        'total_tokens_display' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'gender_composition_value' => 'integer',
        'notes' => 'json'
    ];

    // SIMPLIFIED - Only one step
    const STEPS = ['completed'];
    const STEP_LABELS = ['completed' => 'Event Creation Complete'];

    // FIXED TOKEN COST
    const FIXED_TOKEN_COST = 5.00; // 5 olos per attendee

    // AUTO AGE RANGE
    const DEFAULT_AGE_RANGE = 8; // ±8 years from creator's age

    // Relationships
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function suggestedLocation(): BelongsTo
    {
        return $this->belongsTo(SuggestedLocation::class, 'suggested_location_id');
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

    public function scopePast($query)
    {
        return $query->where('event_date', '<', now()->toDateString());
    }

    public function scopeByHost($query, int $hostId)
    {
        return $query->where('host_id', $hostId);
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    public function scopeInRegion($query, string $state)
    {
        return $query->where('state', 'like', "%{$state}%");
    }

    public function scopeBySuggestedLocation($query, int $locationId)
    {
        return $query->where('suggested_location_id', $locationId);
    }

    /**
     * Scope for events visible to a specific user based on age
     */
    public function scopeVisibleToUser($query, User $user)
    {
        $userAge = $user->age ?? 25;
        
        return $query->where(function ($q) use ($userAge) {
            $q->where('age_restriction_disabled', true) // No age restriction
              ->orWhere(function ($subQ) use ($userAge) {
                  $subQ->where('min_age', '<=', $userAge)
                       ->where('max_age', '>=', $userAge);
              });
        });
    }

    // Helper Methods

    /**
     * Check if event is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if event is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if event is in the past
     */
    public function isPast(): bool
    {
        return $this->event_date->isPast();
    }

  public function getEventDateTimeAttribute()
{
    return \Carbon\Carbon::parse(
        $this->event_date->format('Y-m-d') . ' ' . $this->event_time->format('H:i:s')
    );
}



    /**
     * Check if event is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->event_date->isFuture();
    }

    /**
     * Get total cost for the group
     */
    public function getTotalCost(): float
    {
        return $this->min_group_size * self::FIXED_TOKEN_COST;
    }

    /**
     * Get available slots
     */
    public function getAvailableSlots(): ?int
    {
        if (!$this->max_group_size) {
            return null;
        }

        $confirmedAttendees = $this->attendees()
            ->whereIn('status', ['interested', 'confirmed'])
            ->sum('total_members') ?: $this->attendees()
            ->whereIn('status', ['interested', 'confirmed'])
            ->count();

        return max(0, $this->max_group_size - $confirmedAttendees);
    }

    /**
     * Check if event is full
     */
    public function isFull(): bool
    {
        $availableSlots = $this->getAvailableSlots();
        return $availableSlots !== null && $availableSlots <= 0;
    }

    /**
     * Check if user can see this event (age-based visibility)
     */
    public function isVisibleToUser(User $user): bool
    {
        if ($this->age_restriction_disabled) {
            return true; // No age restrictions
        }
        
        $userAge = $user->age ?? 25;
        return $userAge >= $this->min_age && $userAge <= $this->max_age;
    }

    /**
     * Get age range description
     */
    public function getAgeRangeDescription(): string
    {
        if ($this->age_restriction_disabled) {
            return 'All ages welcome';
        }
        
        return "Ages {$this->min_age}-{$this->max_age}";
    }

    /**
 * Get all reviews for this event
 */
public function reviews(): HasMany
{
    return $this->hasMany(EventReview::class);
}

/**
 * Get average rating for this event
 */
public function getAverageRatingAttribute(): float
{
    return (float) $this->reviews()->avg('rating') ?: 0;
}

/**
 * Get total review count for this event
 */
public function getTotalReviewsAttribute(): int
{
    return $this->reviews()->count();
}

/**
 * Check if this event can be reviewed (has passed)
 */
public function canBeReviewed(): bool
{
    $eventDateTime = $this->event_date->format('Y-m-d') . ' ' . $this->event_time;
    return now()->gt($eventDateTime);
}

    /**
     * Get location information from SuggestedLocation or fallback to stored data
     */
    public function getLocationInfo(): array
    {
        $suggestedLocation = $this->suggestedLocation;
        
        return [
            'suggested_location_id' => $this->suggested_location_id,
            'name' => $suggestedLocation->name ?? 'Custom Location',
            'description' => $suggestedLocation->description ?? null,
            'category' => $suggestedLocation->category ?? null,
            'venue_name' => $this->venue_name,
            'venue_address' => $this->venue_address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'image_url' => $suggestedLocation && $suggestedLocation->primaryImage 
                ? $suggestedLocation->primaryImage->image_url 
                : ($suggestedLocation->image_url ?? null),
            'google_maps_url' => $suggestedLocation->google_maps_url ?? null,
        ];
    }

    /**
     * Generate simplified event summary
     */
    public function getEventSummary(): array
    {
        $locationInfo = $this->getLocationInfo();
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'date' => $this->event_date->format('M j, Y'),
            'time' => $this->event_time->format('g:i A'),
            'location' => $locationInfo,
            'group_size' => $this->min_group_size,
            'cost_per_person' => self::FIXED_TOKEN_COST,
            'total_cost' => $this->getTotalCost(),
            'age_range' => $this->getAgeRangeDescription(),
            'available_slots' => $this->getAvailableSlots(),
            'is_full' => $this->isFull(),
            'status' => $this->status,
            'host_name' => $this->host->name ?? 'Unknown',
        ];
    }
}
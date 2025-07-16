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
        'latitude',
        'longitude',
        'city',
        'state',
        'country',
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
        'cancelled_at'
    ];

    protected $casts = [
        'tags' => 'array',
        'allowed_genders' => 'array',
        'media_urls' => 'array',
        'itinerary_url' => 'array',
        'event_date' => 'date',
        'event_time' => 'datetime:H:i',
        'published_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'gender_rule_enabled' => 'boolean',
        'host_responsibilities_accepted' => 'boolean',
        'token_cost_per_attendee' => 'decimal:2',
        'total_tokens_display' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
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

    // Accessor for full event datetime
    public function getEventDatetimeAttribute()
    {
        return $this->event_date->format('Y-m-d') . ' ' . $this->event_time->format('H:i:s');
    }

    // Calculate total tokens based on group size and cost per attendee
    public function calculateTotalTokens(): float
    {
        if ($this->max_group_size) {
            return $this->max_group_size * $this->token_cost_per_attendee;
        }
        return $this->min_group_size * $this->token_cost_per_attendee;
    }

    // Check if event has space for more attendees
    public function hasSpace(): bool
    {
        if (!$this->max_group_size) {
            return true; // No limit
        }
        
        $confirmedCount = $this->confirmedAttendees()->count();
        return $confirmedCount < $this->max_group_size;
    }

    // Get available spots
    public function getAvailableSpotsAttribute(): ?int
    {
        if (!$this->max_group_size) {
            return null; // Unlimited
        }
        
        $confirmedCount = $this->confirmedAttendees()->count();
        return max(0, $this->max_group_size - $confirmedCount);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OneOnOneDate extends Model
{
    use HasFactory;

     protected $fillable = [
        'host_id',
        'name',
        'description',
        'event_date',
        'event_time',
        'timezone',
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
        'token_cost',
        'media_session_id',
        'request_approval',
        'approval_status',
        'status'
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_time' => 'datetime:H:i',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'token_cost' => 'decimal:2',
        'request_approval' => 'boolean',
        'google_place_details' => 'array'
    ];

    // Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REJECTED = 'rejected';

    // Relationships
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(OneOnOneDateMedia::class, 'one_on_one_date_id');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString());
    }

    public function scopeByHost($query, int $hostId)
    {
        return $query->where('host_id', $hostId);
    }

    // Helper methods
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function canBeJoined(): bool
    {
        return $this->isPublished() && 
               $this->isApproved() && 
               $this->event_date >= now()->toDateString();
    }
}
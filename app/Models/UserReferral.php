<?php
// app/Models/UserReferral.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'referral_code_used',
        'referrer_was_paid',
        'is_eligible_for_free_event',
        'has_used_free_event',
        'free_event_used_at',
        'free_event_id',
        'referrer_bonus_points',
        'referred_bonus_points',
        'referred_at',
    ];

    protected $casts = [
        'referrer_was_paid' => 'boolean',
        'is_eligible_for_free_event' => 'boolean',
        'has_used_free_event' => 'boolean',
        'free_event_used_at' => 'datetime',
        'referred_at' => 'datetime',
    ];

    // Relationships
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function freeEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'free_event_id');
    }

    // Scopes
    public function scopeEligibleForFreeEvent($query)
    {
        return $query->where('is_eligible_for_free_event', true)
                    ->where('has_used_free_event', false);
    }

    public function scopeUsedFreeEvent($query)
    {
        return $query->where('has_used_free_event', true);
    }

    public function scopeByReferrer($query, int $referrerId)
    {
        return $query->where('referrer_id', $referrerId);
    }

    public function scopeByReferred($query, int $referredId)
    {
        return $query->where('referred_id', $referredId);
    }

    // Helper methods
    public function canUseFreeEvent(): bool
    {
        return $this->is_eligible_for_free_event && !$this->has_used_free_event;
    }

    public function markFreeEventAsUsed(int $eventId): bool
    {
        return $this->update([
            'has_used_free_event' => true,
            'free_event_used_at' => now(),
            'free_event_id' => $eventId
        ]);
    }

    public function isFromPaidReferrer(): bool
    {
        return $this->referrer_was_paid;
    }
}
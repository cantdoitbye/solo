<?php
// app/Models/EventReview.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'rating',
        'review_text',
        'is_anonymous'
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_anonymous' => 'boolean'
    ];

    /**
     * Get the event this review belongs to
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user who submitted this review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get reviews for a specific event
     */
    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    /**
     * Scope to get reviews by a specific user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get reviews with specific rating
     */
    public function scopeWithRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Get formatted rating as stars
     */
    public function getStarsAttribute(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Check if review has text content
     */
    public function hasReviewText(): bool
    {
        return !empty($this->review_text);
    }
}
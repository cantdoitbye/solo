<?php
// app/Models/Swipe.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Swipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_user_id',
        'action',
        'is_match',
        'matched_at',
        'metadata'
    ];

    protected $casts = [
        'is_match' => 'boolean',
        'matched_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * User who performed the swipe
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * User who was swiped on
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Check if this swipe resulted in a match
     */
    public function isMatch(): bool
    {
        return $this->is_match;
    }

    /**
     * Scope for liked swipes
     */
    public function scopeLikes($query)
    {
        return $query->where('action', 'like');
    }

    /**
     * Scope for super likes
     */
    public function scopeSuperLikes($query)
    {
        return $query->where('action', 'super_like');
    }

    /**
     * Scope for matches
     */
    public function scopeMatches($query)
    {
        return $query->where('is_match', true);
    }
}
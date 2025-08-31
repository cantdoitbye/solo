<?php
// app/Models/MessageBoardPost.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MessageBoardPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'type',
        'tags',
        'is_pinned',
        'is_active',
        'likes_count',
        'replies_count',
        'views_count',
        'last_activity_at'
    ];

    protected $casts = [
        'tags' => 'array',
        'is_pinned' => 'boolean',
        'is_active' => 'boolean',
        'last_activity_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user who created the post
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all replies for the post
     */
    public function replies(): HasMany
    {
        return $this->hasMany(MessageBoardReply::class, 'post_id')
                   ->where('is_active', true)
                   ->orderBy('created_at', 'asc');
    }

    /**
     * Get direct replies (not nested)
     */
    public function directReplies(): HasMany
    {
        return $this->hasMany(MessageBoardReply::class, 'post_id')
                   ->whereNull('parent_reply_id')
                   ->where('is_active', true)
                   ->orderBy('created_at', 'asc');
    }

    /**
     * Get all likes for the post
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(MessageBoardLike::class, 'likeable');
    }

    /**
     * Check if user has liked this post
     */
    public function isLikedBy($userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Scope to get active posts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get pinned posts
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope to get posts by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Increment views count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Update last activity timestamp
     */
    public function updateLastActivity()
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Get posts ordered by latest activity
     */
    public function scopeOrderByActivity($query)
    {
        return $query->orderByDesc('is_pinned')
                    ->orderByDesc('last_activity_at')
                    ->orderByDesc('created_at');
    }
}
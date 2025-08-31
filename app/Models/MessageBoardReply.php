<?php
// app/Models/MessageBoardReply.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MessageBoardReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_reply_id',
        'content',
        'is_active',
        'likes_count'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the post this reply belongs to
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(MessageBoardPost::class, 'post_id');
    }

    /**
     * Get the user who created the reply
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent reply (for nested replies)
     */
    public function parentReply(): BelongsTo
    {
        return $this->belongsTo(MessageBoardReply::class, 'parent_reply_id');
    }

    /**
     * Get child replies (nested replies)
     */
    public function childReplies(): HasMany
    {
        return $this->hasMany(MessageBoardReply::class, 'parent_reply_id')
                   ->where('is_active', true)
                   ->orderBy('created_at', 'asc');
    }

    /**
     * Get all likes for the reply
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(MessageBoardLike::class, 'likeable');
    }

    /**
     * Check if user has liked this reply
     */
    public function isLikedBy($userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Scope to get active replies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get direct replies (not nested)
     */
    public function scopeDirect($query)
    {
        return $query->whereNull('parent_reply_id');
    }

    /**
     * Boot method to handle events
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($reply) {
            // Increment replies count on post
            $reply->post->increment('replies_count');
            // Update last activity on post
            $reply->post->updateLastActivity();
        });

        static::deleted(function ($reply) {
            // Decrement replies count on post
            $reply->post->decrement('replies_count');
        });
    }
}
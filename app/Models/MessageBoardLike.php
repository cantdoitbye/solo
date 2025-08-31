<?php
// app/Models/MessageBoardLike.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MessageBoardLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'likeable_type',
        'likeable_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user who created the like
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the likeable model (post or reply)
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Boot method to handle events
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($like) {
            // Increment likes count on the likeable model
            $like->likeable->increment('likes_count');
        });

        static::deleted(function ($like) {
            // Decrement likes count on the likeable model
            $like->likeable->decrement('likes_count');
        });
    }
}
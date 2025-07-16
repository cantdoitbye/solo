<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EventItinerary extends Model
{
     use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_url',
        'mime_type',
        'file_size',
        'upload_session_id',
        'event_id',
        'is_attached_to_event'
    ];

    protected $casts = [
        'is_attached_to_event' => 'boolean',
        'file_size' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('upload_session_id', $sessionId);
    }

    public function scopeUnattached($query)
    {
        return $query->where('is_attached_to_event', false);
    }
}

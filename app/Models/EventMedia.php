<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EventMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_url',
        'media_type',
        'mime_type',
        'file_size',
        'width',
        'height',
        'duration',
        'upload_session_id',
        'event_id',
        'is_attached_to_event'
    ];

    protected $casts = [
        'is_attached_to_event' => 'boolean',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeImages($query)
    {
        return $query->where('media_type', 'image');
    }

    public function scopeVideos($query)
    {
        return $query->where('media_type', 'video');
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

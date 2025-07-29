<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'file_path',
        'file_url',
        'mime_type',
        'file_size',
        'width',
        'height',
        'upload_session_id',
        'is_attached_to_event',
        'sort_order'
    ];

    protected $casts = [
        'is_attached_to_event' => 'boolean',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'sort_order' => 'integer'
    ];

    /**
     * Get the event that owns the menu image
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user who uploaded the menu image
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for session-based uploads
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('upload_session_id', $sessionId);
    }

    /**
     * Scope for unattached menu images
     */
    public function scopeUnattached($query)
    {
        return $query->where('is_attached_to_event', false);
    }

    /**
     * Scope for attached menu images
     */
    public function scopeAttached($query)
    {
        return $query->where('is_attached_to_event', true);
    }

    /**
     * Scope for ordering by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'asc');
    }
}
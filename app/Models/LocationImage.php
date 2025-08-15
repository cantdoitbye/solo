<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'suggested_location_id',
        'image_path',
        'image_url',
        'original_filename',
        'file_size',
        'mime_type',
        'width',
        'height',
        'is_primary',
        'sort_order'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'sort_order' => 'integer'
    ];

    /**
     * Get the suggested location that owns this image
     */
    public function suggestedLocation(): BelongsTo
    {
        return $this->belongsTo(SuggestedLocation::class);
    }

    /**
     * Scope for primary images
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for ordering by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'asc');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SuggestedLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'google_maps_url',
        'google_place_id',
        'venue_name',
        'venue_address',
        'latitude',
        'longitude',
        'city',
        'state',
        'country',
        'postal_code',
        'google_place_details',
        'category',
        'image_url',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'google_place_details' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Scope to get only active locations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, $category)
    {
        if ($category) {
            return $query->where('category', $category);
        }
        return $query;
    }

        public function images(): HasMany
    {
        return $this->hasMany(LocationImage::class);
    }

    /**
     * Get the primary image for this suggested location
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(LocationImage::class)->where('is_primary', true);
    }


}
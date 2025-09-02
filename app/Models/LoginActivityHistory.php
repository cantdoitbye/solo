<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginActivityHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_name',
        'device_type',
        'os_name',
        'ip_address',
        'location',
        'city',
        'country',
        'latitude',
        'longitude',
        'user_agent',
        'login_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the user that owns the login activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted device info
     */
    public function getFormattedDeviceAttribute(): string
    {
        $parts = array_filter([
            $this->device_name,
            $this->os_name,        ]);

        return implode(' - ', $parts) ?: 'Unknown Device';
    }

    /**
     * Get formatted location
     */
    public function getFormattedLocationAttribute(): string
    {
        if ($this->location) {
            return $this->location;
        }

        $parts = array_filter([$this->city, $this->country]);
        return implode(', ', $parts) ?: 'Unknown Location';
    }

    /**
     * Scope to get recent login activities
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('login_at', '>=', now()->subDays($days))
                    ->orderBy('login_at', 'desc');
    }

    /**
     * Scope to filter by device type
     */
    public function scopeByDeviceType($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }
}
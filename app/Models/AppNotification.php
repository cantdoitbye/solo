<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'type',
        'data',
        'sent_to_users',
        'total_sent',
        'total_delivered',
        'total_failed',
        'is_scheduled',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_to_users' => 'array',
        'is_scheduled' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true)->whereNull('sent_at');
    }

    public function updateStats(): void
    {
        $stats = $this->logs()
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(CASE WHEN status IN ("delivered", "clicked") THEN 1 ELSE 0 END) as total_delivered,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as total_failed
            ')
            ->first();

        $this->update([
            'total_sent' => $stats->total_sent ?? 0,
            'total_delivered' => $stats->total_delivered ?? 0,
            'total_failed' => $stats->total_failed ?? 0,
        ]);
    }
}
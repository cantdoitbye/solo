<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_notification_id',
        'user_id',
        'fcm_token',
        'status',
        'error_message',
        'fcm_response',
        'sent_at',
        'delivered_at',
        'clicked_at',
    ];

    protected $casts = [
        'fcm_response' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public function appNotification(): BelongsTo
    {
        return $this->belongsTo(AppNotification::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsClicked(): void
    {
        $this->update([
            'status' => 'clicked',
            'clicked_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage, ?array $fcmResponse = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'fcm_response' => $fcmResponse,
        ]);
    }
}
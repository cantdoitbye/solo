<?php
// app/Models/OlosTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OlosTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'transaction_type',
        'description',
        'metadata',
        'reference_id',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Constants for transaction types
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';

    const TRANSACTION_TYPE_REGISTRATION_BONUS = 'registration_bonus';
    const TRANSACTION_TYPE_EVENT_JOIN = 'event_join';
    const TRANSACTION_TYPE_EVENT_REFUND = 'event_refund';
    const TRANSACTION_TYPE_PURCHASE = 'purchase';
    const TRANSACTION_TYPE_REFERRAL_BONUS = 'referral_bonus';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCredits($query)
    {
        return $query->where('type', self::TYPE_CREDIT);
    }

    public function scopeDebits($query)
    {
        return $query->where('type', self::TYPE_DEBIT);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('reference_id', $eventId)
                    ->whereIn('transaction_type', [
                        self::TRANSACTION_TYPE_EVENT_JOIN,
                        self::TRANSACTION_TYPE_EVENT_REFUND
                    ]);
    }
}
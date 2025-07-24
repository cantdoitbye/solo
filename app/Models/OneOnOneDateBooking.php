<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OneOnOneDateBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'one_on_one_date_id',
        'user_id',
        'tokens_paid',
        'status',
        'booked_at',
        'cancelled_at'
    ];

    protected $casts = [
        'tokens_paid' => 'decimal:2',
        'booked_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    public function oneOnOneDate(): BelongsTo
    {
        return $this->belongsTo(OneOnOneDate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
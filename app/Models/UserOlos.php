<?php
// app/Models/UserOlos.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserOlos extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'total_earned',
        'total_spent',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_spent' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(OlosTransaction::class, 'user_id', 'user_id');
    }

    // Helper methods
    public function hasEnoughBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function deductBalance(float $amount): void
    {
        if (!$this->hasEnoughBalance($amount)) {
            throw new \Exception('Insufficient Olos balance');
        }
        
        $this->decrement('balance', $amount);
        $this->increment('total_spent', $amount);
    }

    public function addBalance(float $amount): void
    {
        $this->increment('balance', $amount);
        $this->increment('total_earned', $amount);
    }
}
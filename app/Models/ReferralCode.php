<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
     protected $fillable = [
        'code',
        'user_id',
        'uses_count',
        'max_uses',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function canBeUsed(): bool
    {
        return $this->is_active && $this->uses_count < $this->max_uses;
    }
}

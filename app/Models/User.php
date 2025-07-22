<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Services\OlosService;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
   use HasApiTokens, Notifiable;

    protected $fillable = [
        'phone_number',
        'country_code',
        'phone_verified_at',
        'otp_code',
        'otp_expires_at',
        'connection_type',
        'search_radius',
        'referral_code',
        'used_referral_code',
        'referral_points',
        'discovery_sources',
        'interests',
        'bio',
        'introduction_answers',
        'onboarding_completed',
        'latitude',
        'longitude',
        'city',
        'state',
        'country',
    ];

    protected $hidden = [
        'otp_code',
        'otp_expires_at',
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'discovery_sources' => 'array',
        'interests' => 'array',
        'introduction_answers' => 'array',
        'onboarding_completed' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function isOtpValid(string $otp): bool
    {
        return $this->otp_code === $otp && 
               $this->otp_expires_at && 
               $this->otp_expires_at->isFuture();
    }

    public function generateReferralCode(): string
    {
        do {
            $code = 'SOLO' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    public function referralCode()
    {
        return $this->hasOne(ReferralCode::class);
    }

     public function olos(): HasOne
    {
        return $this->hasOne(UserOlos::class);
    }

    public function olosTransactions(): HasMany
    {
        return $this->hasMany(OlosTransaction::class);
    }


      // Event-related relationships
    public function hostedEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'host_id');
    }

    public function eventAttendances(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function eventMembers()
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function joinedEvents()
    {
        return $this->belongsToMany(Event::class, 'event_attendees')
                    ->withPivot(['status', 'tokens_paid', 'total_members', 'cost_per_member', 'total_cost', 'joined_at', 'confirmed_at', 'cancelled_at'])
                    ->withTimestamps();
    }

     // Helper methods for Olos
    public function getCurrentOlosBalance(): float
    {
        return $this->olos ? $this->olos->balance : 0;
    }

    public function hasEnoughOlos(float $amount): bool
    {
        return $this->getCurrentOlosBalance() >= $amount;
    }

    // Boot method to initialize Olos account when user completes onboarding
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($user) {
            // Initialize Olos when onboarding is completed
            if ($user->onboarding_completed && $user->wasChanged('onboarding_completed')) {
                app(OlosService::class)->initializeUserOlos($user->id);
            }
        });
    }
}

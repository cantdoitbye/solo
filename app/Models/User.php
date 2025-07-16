<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
}

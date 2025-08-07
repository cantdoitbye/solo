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
        'name',
        'age',
        'gender',
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
        'profile_photo',
        'introduction_answers',
        'onboarding_completed',
        'latitude',
        'longitude',
        'city',
        'state',
        'country',
        'fcm_token',
        'fcm_token_updated_at',
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
        'fcm_token_updated_at' => 'datetime',
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


 
// Chat-related relationships
public function chatRoomMemberships(): HasMany
{
    return $this->hasMany(ChatRoomMember::class);
}

public function activeChatRoomMemberships(): HasMany
{
    return $this->hasMany(ChatRoomMember::class)->where('is_active', true);
}

public function chatRooms()
{
    return $this->belongsToMany(ChatRoom::class, 'chat_room_members')
                ->withPivot(['joined_at', 'left_at', 'is_active', 'role', 'last_read_at', 'is_online', 'last_seen_at'])
                ->withTimestamps();
}

public function activeChatRooms()
{
    return $this->belongsToMany(ChatRoom::class, 'chat_room_members')
                ->wherePivot('is_active', true)
                ->withPivot(['joined_at', 'left_at', 'is_active', 'role', 'last_read_at', 'is_online', 'last_seen_at'])
                ->withTimestamps();
}

public function sentMessages(): HasMany
{
    return $this->hasMany(Message::class, 'sender_id');
}

public function createdChatRooms(): HasMany
{
    return $this->hasMany(ChatRoom::class, 'created_by');
}

// Helper methods for chat functionality
public function joinChatRoom(ChatRoom $chatRoom, string $role = ChatRoomMember::ROLE_MEMBER): ChatRoomMember
{
    return $chatRoom->addMember($this->id, $role);
}

public function leaveChatRoom(ChatRoom $chatRoom): void
{
    $chatRoom->removeMember($this->id);
}

public function isMemberOfChatRoom(ChatRoom $chatRoom): bool
{
    return $chatRoom->isMember($this->id);
}

public function isAdminOfChatRoom(ChatRoom $chatRoom): bool
{
    return $chatRoom->isAdmin($this->id);
}

  // Add notification-related relationships
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    // FCM token methods
    public function updateFcmToken(string $token): bool
    {
        return $this->update([
            'fcm_token' => $token,
            'fcm_token_updated_at' => now(),
        ]);
    }

    public function clearFcmToken(): bool
    {
        return $this->update([
            'fcm_token' => null,
            'fcm_token_updated_at' => null,
        ]);
    }

    public function hasFcmToken(): bool
    {
        return !empty($this->fcm_token);
    }

    // Scopes
    public function scopeWithFcmToken($query)
    {
        return $query->whereNotNull('fcm_token');
    }

    public function scopeOnboardingCompleted($query)
    {
        return $query->where('onboarding_completed', true);
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Services\OlosService;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
   use HasApiTokens, Notifiable, softDeletes;

    protected $fillable = [
        'name',
         'dob',
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
        'deleted_at',
        'two_factor_enabled',
        'push_notifications_enabled',
        'sound_alerts_enabled',
        'selected_theme',
        'default_language',
        'account_settings_updated_at',
         'status', // New field
        'blocked_at', // New field
        'block_reason',
        'is_paid_member',
'plan_type', 
'paid_member_since',
    ];

    protected $hidden = [
        'otp_code',
        'otp_expires_at',
    ];

   

    protected $casts = [
    'dob' => 'date:Y-m-d', // This will format it as Y-m-d in JSON responses
        'phone_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'discovery_sources' => 'array',
        'interests' => 'array',
        'introduction_answers' => 'array',
        'onboarding_completed' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'fcm_token_updated_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'push_notifications_enabled' => 'boolean',
        'sound_alerts_enabled' => 'boolean',
        'account_settings_updated_at' => 'datetime',
                'blocked_at' => 'datetime',
                'is_paid_member' => 'boolean',
'paid_member_since' => 'datetime',
    ];

       const STATUS_ACTIVE = 'active';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_INACTIVE = 'inactive';

public function referrals(): HasMany
{
    return $this->hasMany(UserReferral::class, 'referrer_id');
}

public function referredBy(): HasOne
{
    return $this->hasOne(UserReferral::class, 'referred_id');
}
      public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    public function block(string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_BLOCKED,
            'blocked_at' => now(),
            'block_reason' => $reason,
        ]);
    }

    public function unblock(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'blocked_at' => null,
            'block_reason' => null,
        ]);
    }
//     public function getDobAttribute($value): ?string
// {
//     return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
// }

    // Optional: Add a method to calculate age dynamically from DOB
public function getCalculatedAgeAttribute(): ?int
{
    return $this->dob ? $this->dob->age : null;
}

// Optional: Accessor to always return current age based on DOB
public function getAgeAttribute($value): ?int
{
    // If DOB exists, calculate age from DOB, otherwise return stored age
    return $this->dob ? $this->dob->age : $value;
}

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

    /**
     * Get login activity history for this user
     */
    public function loginActivityHistories(): HasMany
    {
        return $this->hasMany(LoginActivityHistory::class);
    }

    /**
     * Get recent login activities
     */
    public function getRecentLoginActivities(int $limit = 10)
    {
        return $this->loginActivityHistories()
                   ->orderBy('login_at', 'desc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Get account settings as array
     */
    public function getAccountSettingsAttribute(): array
    {
        return [
            'two_factor_enabled' => $this->two_factor_enabled,
            'push_notifications_enabled' => $this->push_notifications_enabled,
            'sound_alerts_enabled' => $this->sound_alerts_enabled,
            'selected_theme' => $this->selected_theme,
            'default_language' => $this->default_language,
        ];
    }



    /**
     * ADD THESE RELATIONSHIPS AND METHODS TO YOUR EXISTING USER MODEL
     */

    /**
     * Get the user's active plan
     */
    public function activePlan()
    {
        return $this->hasOne(UserPlan::class)->where('status', 'active');
    }

    /**
     * Get all user's plans
     */
    public function plans()
    {
        return $this->hasMany(UserPlan::class);
    }

    /**
     * Get all user's payments
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if user has an active plan
     */
    public function hasActivePlan(): bool
    {
        return $this->activePlan()->exists();
    }

    /**
     * Check if user has a specific plan
     */
    public function hasPlan(string $planId): bool
    {
        return $this->activePlan()
            ->where('plan_id', $planId)
            ->exists();
    }

    /**
     * Check if user has any of the given plans
     */
    public function hasAnyPlan(array $planIds): bool
    {
        return $this->activePlan()
            ->whereIn('plan_id', $planIds)
            ->exists();
    }

    /**
     * Get user's current plan details
     */
    public function getCurrentPlan(): ?array
    {
        $activePlan = $this->activePlan;
        
        if (!$activePlan) {
            return null;
        }

        return $activePlan->getPlanDetails();
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

    /**
 * Get all reviews submitted by this user
 */
public function eventReviews(): HasMany
{
    return $this->hasMany(EventReview::class);
}

/**
 * Check if user has reviewed a specific event
 */
public function hasReviewedEvent(int $eventId): bool
{
    return $this->eventReviews()->where('event_id', $eventId)->exists();
}

/**
 * Get user's review for a specific event
 */
public function getEventReview(int $eventId): ?EventReview
{
    return $this->eventReviews()->where('event_id', $eventId)->first();
}
}

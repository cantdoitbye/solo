<?php
// app/Models/ChatRoom.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'event_id',
        'created_by',
        'is_active',
        'last_message_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_message_at' => 'datetime'
    ];

    const TYPE_EVENT_GROUP = 'event_group';
    const TYPE_PERSONAL = 'personal';

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function chatRoomMembers(): HasMany
    {
        return $this->hasMany(ChatRoomMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(ChatRoomMember::class)->where('is_active', true);
    }

    public function onlineMembers(): HasMany
    {
        return $this->hasMany(ChatRoomMember::class)->where('is_online', true)->where('is_active', true);
    }

    public function adminMembers(): HasMany
    {
        return $this->hasMany(ChatRoomMember::class)->where('role', ChatRoomMember::ROLE_ADMIN);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // Many-to-many relationship through ChatRoomMember
    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_room_members')
                    ->withPivot(['joined_at', 'left_at', 'is_active', 'role', 'last_read_at', 'is_online', 'last_seen_at'])
                    ->withTimestamps();
    }

    // Add this method to your ChatRoom model
public function members()
{
    return $this->belongsToMany(User::class, 'chat_room_members')
                ->withPivot(['joined_at', 'left_at', 'is_active', 'role', 'last_read_at', 'is_online', 'last_seen_at'])
                ->withTimestamps();
}

    public function activeUsers()
    {
        return $this->belongsToMany(User::class, 'chat_room_members')
                    ->wherePivot('is_active', true)
                    ->withPivot(['joined_at', 'left_at', 'is_active', 'role', 'last_read_at', 'is_online', 'last_seen_at'])
                    ->withTimestamps();
    }

    // Scopes
    public function scopeEventGroups($query)
    {
        return $query->where('type', self::TYPE_EVENT_GROUP);
    }

    public function scopePersonalChats($query)
    {
        return $query->where('type', self::TYPE_PERSONAL);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('chatRoomMembers', function ($q) use ($userId) {
            $q->where('user_id', $userId)->where('is_active', true);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function isEventGroup(): bool
    {
        return $this->type === self::TYPE_EVENT_GROUP;
    }

    public function isPersonalChat(): bool
    {
        return $this->type === self::TYPE_PERSONAL;
    }

    public function getMemberCount(): int
    {
        return $this->activeMembers()->count();
    }

    public function getOnlineCount(): int
    {
        return $this->onlineMembers()->count();
    }

    public function isMember(int $userId): bool
    {
        return $this->chatRoomMembers()
                    ->where('user_id', $userId)
                    ->where('is_active', true)
                    ->exists();
    }

    public function isAdmin(int $userId): bool
    {
        return $this->chatRoomMembers()
                    ->where('user_id', $userId)
                    ->where('role', ChatRoomMember::ROLE_ADMIN)
                    ->where('is_active', true)
                    ->exists();
    }

    public function addMember(int $userId, string $role = ChatRoomMember::ROLE_MEMBER): ChatRoomMember
    {
        return $this->chatRoomMembers()->create([
            'user_id' => $userId,
            'role' => $role,
            'is_active' => true,
            'joined_at' => now()
        ]);
    }

    public function removeMember(int $userId): void
    {
        $this->chatRoomMembers()
             ->where('user_id', $userId)
             ->update([
                 'is_active' => false,
                 'left_at' => now()
             ]);
    }

    public function updateLastMessage(): void
    {
        $this->update(['last_message_at' => now()]);
    }
}
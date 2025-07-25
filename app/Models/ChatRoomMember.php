<?php
// app/Models/ChatRoomMember.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatRoomMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_room_id',
        'user_id',
        'joined_at',
        'left_at',
        'is_active',
        'role',
        'last_read_at',
        'is_online',
        'last_seen_at'
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'last_read_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
        'is_online' => 'boolean'
    ];

    // Constants
    const ROLE_ADMIN = 'admin';
    const ROLE_MEMBER = 'member';

    // Relationships
    public function chatRoom(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    public function scopeMembers($query)
    {
        return $query->where('role', self::ROLE_MEMBER);
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    public function markAsOnline(): void
    {
        $this->update([
            'is_online' => true,
            'last_seen_at' => now()
        ]);
    }

    public function markAsOffline(): void
    {
        $this->update([
            'is_online' => false,
            'last_seen_at' => now()
        ]);
    }

    public function updateLastRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }
}
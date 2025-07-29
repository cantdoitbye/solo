<?php
// app/Models/Message.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_room_id',
        'sender_id',
        'message_type',
        'content',
        'file_url',
        'file_name',
        'file_size',
        'duration',
        'transcription',
        'reply_to_message_id',
        'is_edited',
        'edited_at',
        'reactions',
        'metadata'
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'file_size' => 'integer',
        'duration' => 'integer',
        'reactions' => 'array',
        'metadata' => 'array'
    ];

    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_FILE = 'file';
    const TYPE_VOICE = 'voice';
    const TYPE_SYSTEM = 'system';

    

    // Relationships
    public function chatRoom(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_message_id');
    }

    // Scopes
    public function scopeText($query)
    {
        return $query->where('message_type', self::TYPE_TEXT);
    }

    public function scopeMedia($query)
    {
        return $query->whereIn('message_type', [self::TYPE_IMAGE, self::TYPE_FILE, self::TYPE_VOICE]);
    }

    public function scopeSystem($query)
    {
        return $query->where('message_type', self::TYPE_SYSTEM);
    }

    public function scopeForChatRoom($query, int $chatRoomId)
    {
        return $query->where('chat_room_id', $chatRoomId);
    }

    public function scopeFromUser($query, int $userId)
    {
        return $query->where('sender_id', $userId);
    }

    // Helper methods
    public function isText(): bool
    {
        return $this->message_type === self::TYPE_TEXT;
    }

    public function isImage(): bool
    {
        return $this->message_type === self::TYPE_IMAGE;
    }

    public function isFile(): bool
    {
        return $this->message_type === self::TYPE_FILE;
    }

    public function isVoice(): bool
    {
        return $this->message_type === self::TYPE_VOICE;
    }

    public function isSystem(): bool
    {
        return $this->message_type === self::TYPE_SYSTEM;
    }

    public function isReply(): bool
    {
        return !is_null($this->reply_to_message_id);
    }

    public function hasFile(): bool
    {
        return !is_null($this->file_url);
    }

    public function getFileSizeFormatted(): string
    {
        if (!$this->file_size) {
            return 'Unknown size';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDurationFormatted(): string
    {
        if (!$this->duration) {
            return '0:00';
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function addReaction(int $userId, string $emoji): void
    {
        $reactions = $this->reactions ?? [];
        
        if (!isset($reactions[$emoji])) {
            $reactions[$emoji] = [];
        }
        
        if (!in_array($userId, $reactions[$emoji])) {
            $reactions[$emoji][] = $userId;
        }
        
        $this->update(['reactions' => $reactions]);
    }

    public function removeReaction(int $userId, string $emoji): void
    {
        $reactions = $this->reactions ?? [];
        
        if (isset($reactions[$emoji])) {
            $reactions[$emoji] = array_values(array_filter($reactions[$emoji], fn($id) => $id !== $userId));
            
            if (empty($reactions[$emoji])) {
                unset($reactions[$emoji]);
            }
        }
        
        $this->update(['reactions' => $reactions]);
    }

    public function getReactionCount(string $emoji): int
    {
        return count($this->reactions[$emoji] ?? []);
    }

    public function hasUserReacted(int $userId, string $emoji): bool
    {
        return in_array($userId, $this->reactions[$emoji] ?? []);
    }

    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now()
        ]);
    }
}
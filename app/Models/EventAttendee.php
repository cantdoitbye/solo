<?php
// app/Models/EventAttendee.php (Updated - No EventMember dependency)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'tokens_paid',
        'total_members',        // NEW: Number of members in this booking
        'cost_per_member',      // NEW: Cost per individual member
        'total_cost',           // NEW: Total cost for all members
        'members_data',         // NEW: JSON storage for member details
        'joined_at',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason'
    ];

    protected $casts = [
        'tokens_paid' => 'decimal:2',
        'cost_per_member' => 'decimal:2',  // NEW
        'total_cost' => 'decimal:2',       // NEW
        'members_data' => 'array',         // NEW: JSON cast
        'joined_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    // Existing relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Existing scopes
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeInterested($query)
    {
        return $query->where('status', 'interested');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // NEW: Scope for active attendees (not cancelled)
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['interested', 'confirmed']);
    }

    // NEW: Helper methods for multiple members
    public function getTotalMembersCount(): int
    {
        return $this->total_members ?? 1;
    }

    public function getTotalCostAmount(): float
    {
        return $this->total_cost ?? $this->tokens_paid ?? 0;
    }

    public function getCostPerMemberAmount(): float
    {
        return $this->cost_per_member ?? 0;
    }

    // NEW: Check if this is a multi-member booking
    public function isMultiMemberBooking(): bool
    {
        return $this->total_members > 1;
    }

    // NEW: Get member details from JSON
    public function getMembersData(): array
    {
        return $this->members_data ?? [];
    }

    // NEW: Get member names for display
    public function getMemberNames(): array
    {
        $membersData = $this->getMembersData();
        return array_column($membersData, 'member_name');
    }

    // NEW: Get primary member (first member)
    public function getPrimaryMember(): ?array
    {
        $members = $this->getMembersData();
        return $members[0] ?? null;
    }

    // Backward compatibility - ensure tokens_paid shows total cost
    public function getTokensPaidAttribute($value): float
    {
        // If total_cost exists, use it; otherwise use the original tokens_paid value
        return $this->total_cost ?? $value ?? 0;
    }
}
<?php

namespace App\Repositories;

use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;

class EventRepository implements EventRepositoryInterface
{
    public function create(array $data): Event
    {
        return Event::create($data);
    }

    public function update(int $id, array $data): Event
    {
        $event = Event::findOrFail($id);
        $event->update($data);
        return $event->fresh();
    }

    public function delete(int $id): bool
    {
        $event = Event::findOrFail($id);
        return $event->delete();
    }

    public function findById(int $id): ?Event
    {
        return Event::with(['host', 'venueType', 'venueCategory', 'attendees', 'media', 'itineraries'])->find($id);
    }

    public function findByIdAndHost(int $id, int $hostId): ?Event
    {
        return Event::where('id', $id)
                   ->where('host_id', $hostId)
                   ->with(['host', 'venueType', 'venueCategory', 'attendees', 'media', 'itineraries'])
                   ->first();
    }

    public function getByHost(int $hostId, string $status = null): array
    {
        $query = Event::where('host_id', $hostId)
                     ->with(['venueType', 'venueCategory', 'attendees'])
                     ->orderBy('event_date', 'desc')
                     ->orderBy('event_time', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get()->toArray();
    }

    public function publishEvent(int $id): Event
    {
        $event = Event::findOrFail($id);
        $event->update([
            'status' => 'published',
            'published_at' => now()
        ]);
        return $event->fresh();
    }

    public function cancelEvent(int $id, string $reason = null): Event
    {
        $event = Event::findOrFail($id);
        $event->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);
        
        // Cancel all attendee registrations
        $event->attendees()->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?? 'Event cancelled by host'
        ]);
        
        return $event->fresh();
    }

    public function getUpcomingEvents(int $limit = 10): array
    {
        return Event::published()
                   ->upcoming()
                   ->with(['host', 'venueType', 'venueCategory'])
                   ->orderBy('event_date', 'asc')
                   ->orderBy('event_time', 'asc')
                   ->limit($limit)
                   ->get()
                   ->toArray();
    }

    public function searchEvents(array $filters): array
    {
        $query = Event::published()
                     ->upcoming()
                     ->with(['host', 'venueType', 'venueCategory']);

        if (isset($filters['start_date'])) {
            $query->where('event_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('event_date', '<=', $filters['end_date']);
        }

        if (isset($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        if (isset($filters['state'])) {
            $query->where('state', 'like', '%' . $filters['state'] . '%');
        }

        if (isset($filters['venue_type_id'])) {
            $query->where('venue_type_id', $filters['venue_type_id']);
        }

        if (isset($filters['venue_category_id'])) {
            $query->where('venue_category_id', $filters['venue_category_id']);
        }

        if (isset($filters['tags']) && is_array($filters['tags'])) {
            $query->whereJsonContains('tags', $filters['tags']);
        }

        if (isset($filters['min_spots'])) {
            $query->whereRaw('(max_group_size - (SELECT COUNT(*) FROM event_attendees WHERE event_id = events.id AND status = "confirmed")) >= ?', [$filters['min_spots']]);
        }

        if (isset($filters['age'])) {
            $query->where('min_age', '<=', $filters['age'])
                  ->where('max_age', '>=', $filters['age']);
        }

        return $query->orderBy('event_date', 'asc')
                    ->orderBy('event_time', 'asc')
                    ->get()
                    ->toArray();
    }

    // ========================================
    // NEW METHODS FOR HOME SCREEN API
    // ========================================

    public function getEventsByCategoryId(int $categoryId, int $limit = 10, int $offset = 0): array
    {
        return Event::published()
                    ->upcoming()
                    ->where('venue_category_id', $categoryId)
                    ->with(['host', 'venueType', 'venueCategory', 'attendees', 'media'])
                    ->withCount('attendees')
                    ->orderBy('event_date', 'asc')
                    ->limit($limit)
                    ->offset($offset)
                    ->get()
                    ->toArray();
    }

    public function getEventsCategoryIdCount(int $categoryId): int
    {
        return Event::published()
                    ->upcoming()
                    ->where('venue_category_id', $categoryId)
                    ->count();
    }

    public function getCategoryIdCount(int $categoryId): int
    {
        return $this->getEventsCategoryIdCount($categoryId);
    }

    public function getFilteredEvents(array $filters, int $limit = 10, int $offset = 0): array
    {
        $query = Event::published()
                      ->upcoming()
                      ->with(['host', 'venueType', 'venueCategory', 'attendees', 'media'])
                      ->withCount('attendees');

        // Apply filters
        if (!empty($filters['event_type'])) {
            if ($filters['event_type'] === 'one-on-one') {
                $query->where('max_group_size', '<=', 2);
            } else {
                $query->where('max_group_size', '>', 2);
            }
        }

        // Filter by venue category IDs (Dynamic)
        if (!empty($filters['venue_category_ids'])) {
            $query->whereIn('venue_category_id', $filters['venue_category_ids']);
        }

        if (!empty($filters['select_sex'])) {
            $query->where(function($q) use ($filters) {
                foreach ($filters['select_sex'] as $gender) {
                    $q->orWhereJsonContains('allowed_genders', $gender);
                }
            });
        }

        if (!empty($filters['age_min'])) {
            $query->where('max_age', '>=', $filters['age_min']);
        }

        if (!empty($filters['age_max'])) {
            $query->where('min_age', '<=', $filters['age_max']);
        }

        if (!empty($filters['event_spot'])) {
            $query->whereHas('venueType', function($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['event_spot'] . '%');
            });
        }

        return $query->orderBy('event_date', 'asc')
                     ->limit($limit)
                     ->offset($offset)
                     ->get()
                     ->toArray();
    }

    public function getFilteredEventsCount(array $filters): int
    {
        $query = Event::published()->upcoming();

        // Apply same filters as getFilteredEvents
        if (!empty($filters['event_type'])) {
            if ($filters['event_type'] === 'one-on-one') {
                $query->where('max_group_size', '<=', 2);
            } else {
                $query->where('max_group_size', '>', 2);
            }
        }

        if (!empty($filters['venue_category_ids'])) {
            $query->whereIn('venue_category_id', $filters['venue_category_ids']);
        }

        if (!empty($filters['select_sex'])) {
            $query->where(function($q) use ($filters) {
                foreach ($filters['select_sex'] as $gender) {
                    $q->orWhereJsonContains('allowed_genders', $gender);
                }
            });
        }

        if (!empty($filters['age_min'])) {
            $query->where('max_age', '>=', $filters['age_min']);
        }

        if (!empty($filters['age_max'])) {
            $query->where('min_age', '<=', $filters['age_max']);
        }

        if (!empty($filters['event_spot'])) {
            $query->whereHas('venueType', function($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['event_spot'] . '%');
            });
        }

        return $query->count();
    }

    public function getHotEvents(int $limit = 5): array
    {
        return Event::published()
                    ->upcoming()
                    ->withCount('attendees')
                    ->orderByDesc('attendees_count')
                    ->with(['host', 'venueType', 'venueCategory', 'attendees', 'media'])
                    ->limit($limit)
                    ->get()
                    ->toArray();
    }

    public function getRecentEvents(int $userId, int $limit = 10): array
    {
        return Event::published()
                    ->upcoming()
                    ->where('created_at', '>=', now()->subDays(7))
                    ->with(['host', 'venueType', 'venueCategory', 'attendees', 'media'])
                    ->withCount('attendees')
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->get()
                    ->toArray();
    }

    public function searchEventsByQuery(string $query, int $limit = 10, int $offset = 0): array
    {
        return Event::published()
                    ->upcoming()
                    ->where(function($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%")
                          ->orWhereJsonContains('tags', $query);
                    })
                    ->with(['host', 'venueType', 'venueCategory', 'attendees', 'media'])
                    ->withCount('attendees')
                    ->orderBy('event_date', 'asc')
                    ->limit($limit)
                    ->offset($offset)
                    ->get()
                    ->toArray();
    }

    public function getSearchCount(string $query): int
    {
        return Event::published()
                    ->upcoming()
                    ->where(function($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%")
                          ->orWhereJsonContains('tags', $query);
                    })
                    ->count();
    }

    public function isUserJoinedEvent(int $eventId, int $userId): bool
    {
        return \DB::table('event_attendees')
                  ->where('event_id', $eventId)
                  ->where('user_id', $userId)
                  ->whereIn('status', ['interested', 'confirmed'])
                  ->exists();
    }

    public function getTotalPublishedEventsCount(): int
    {
        return Event::published()->upcoming()->count();
    }
}
<?php
namespace App\Repositories;

use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;
use Carbon\Carbon;

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

    public function findById(int $id): ?Event
    {
        return Event::with(['host', 'venueType', 'venueCategory', 'attendees'])->find($id);
    }

    public function findByIdAndHost(int $id, int $hostId): ?Event
    {
        return Event::where('id', $id)
                   ->where('host_id', $hostId)
                   ->with(['host', 'venueType', 'venueCategory', 'attendees'])
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
        return Event::where('status', 'published')
                   ->where('event_date', '>=', now()->toDateString())
                   ->with(['host', 'venueType', 'venueCategory'])
                   ->orderBy('event_date', 'asc')
                   ->orderBy('event_time', 'asc')
                   ->limit($limit)
                   ->get()
                   ->toArray();
    }

    public function searchEvents(array $filters): array
    {
        $query = Event::where('status', 'published')
                     ->where('event_date', '>=', now()->toDateString())
                     ->with(['host', 'venueType', 'venueCategory']);

        // Filter by date range
        if (isset($filters['start_date'])) {
            $query->where('event_date', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->where('event_date', '<=', $filters['end_date']);
        }

        // Filter by location
        if (isset($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }
        
        if (isset($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        // Filter by venue type
        if (isset($filters['venue_type_id'])) {
            $query->where('venue_type_id', $filters['venue_type_id']);
        }

        // Filter by venue category
        if (isset($filters['venue_category_id'])) {
            $query->where('venue_category_id', $filters['venue_category_id']);
        }

        // Filter by tags
        if (isset($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        // Filter by group size
        if (isset($filters['min_spots'])) {
            $query->whereRaw('(max_group_size IS NULL OR max_group_size >= ?)', [$filters['min_spots']]);
        }

        // Filter by age range
        if (isset($filters['age'])) {
            $query->where('min_age', '<=', $filters['age'])
                  ->where('max_age', '>=', $filters['age']);
        }

        return $query->orderBy('event_date', 'asc')
                    ->orderBy('event_time', 'asc')
                    ->get()
                    ->toArray();
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::with(['host:id,name,phone_number', 'suggestedLocation:id,name'])
                     ->withCount('attendees');
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('venue_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhereHas('host', function($hostQuery) use ($search) {
                      $hostQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date filter
        if ($request->filled('date_filter')) {
            $dateFilter = $request->date_filter;
            $today = now()->format('Y-m-d');
            
            switch ($dateFilter) {
                case 'today':
                    $query->whereDate('event_date', $today);
                    break;
                case 'upcoming':
                    $query->where('event_date', '>=', $today);
                    break;
                case 'past':
                    $query->where('event_date', '<', $today);
                    break;
            }
        }

        $events = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Get status counts for filters
        $statusCounts = [
            'all' => Event::count(),
            'draft' => Event::where('status', 'draft')->count(),
            'pending' => Event::where('status', 'pending')->count(),
            'published' => Event::where('status', 'published')->count(),
            'rejected' => Event::where('status', 'rejected')->count(),
            'cancelled' => Event::where('status', 'cancelled')->count(),
        ];
        
        return view('admin.events.index', compact('events', 'statusCounts'));
    }

    public function show($id)
    {
        $event = Event::with([
            'host:id,name,phone_number,email',
            'suggestedLocation:id,name,venue_name,venue_address,city',
            'attendees.user:id,name,phone_number,email'
        ])->findOrFail($id);
        
        // Event statistics
        $stats = [
            'total_attendees' => $event->attendees->count(),
            'confirmed_attendees' => $event->attendees->where('status', 'confirmed')->count(),
            'interested_attendees' => $event->attendees->where('status', 'interested')->count(),
            'cancelled_attendees' => $event->attendees->where('status', 'cancelled')->count(),
            'total_revenue' => $event->attendees->sum('tokens_paid'),
        ];
        
        return view('admin.events.show', compact('event', 'stats'));
    }

    public function updateStatus(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:draft,pending,published,rejected,cancelled',
            'admin_notes' => 'nullable|string|max:1000',
            'rejection_reason' => 'required_if:status,rejected|max:500',
        ]);

        $oldStatus = $event->status;
        $newStatus = $request->status;

        $event->update([
            'status' => $newStatus,
            'admin_notes' => $request->admin_notes,
            'rejection_reason' => $request->rejection_reason,
            'reviewed_at' => now(),
            'reviewed_by' => auth('admin')->id(),
        ]);

        // If event is being published, set published_at timestamp
        if ($newStatus === 'published' && $oldStatus !== 'published') {
            $event->update(['published_at' => now()]);
        }

        // If event is being cancelled, handle attendee refunds/notifications
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            $event->update(['cancelled_at' => now()]);
            // Here you can add logic to handle refunds and notifications
        }

        $statusText = [
            'draft' => 'moved to draft',
            'pending' => 'set to pending review',
            'published' => 'approved and published',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled',
        ];

        return redirect()->back()
            ->with('success', "Event has been {$statusText[$newStatus]} successfully.");
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        
        // Check if event has attendees
        if ($event->attendees()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete event with attendees. Please cancel the event instead.');
        }
        
        $event->delete();
        
        return redirect()->route('admin.events.index')
            ->with('success', 'Event deleted successfully.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,cancel,delete',
            'event_ids' => 'required|array|min:1',
            'event_ids.*' => 'exists:events,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $eventIds = $request->event_ids;
        $action = $request->action;
        $reason = $request->reason;
        
        $count = 0;
        
        foreach ($eventIds as $eventId) {
            $event = Event::find($eventId);
            if (!$event) continue;
            
            switch ($action) {
                case 'approve':
                    if ($event->status === 'pending') {
                        $event->update([
                            'status' => 'published',
                            'published_at' => now(),
                            'reviewed_at' => now(),
                            'reviewed_by' => auth('admin')->id(),
                        ]);
                        $count++;
                    }
                    break;
                    
                case 'reject':
                    if ($event->status === 'pending') {
                        $event->update([
                            'status' => 'rejected',
                            'rejection_reason' => $reason,
                            'reviewed_at' => now(),
                            'reviewed_by' => auth('admin')->id(),
                        ]);
                        $count++;
                    }
                    break;
                    
                case 'cancel':
                    if (in_array($event->status, ['published', 'pending'])) {
                        $event->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'admin_notes' => $reason,
                        ]);
                        $count++;
                    }
                    break;
                    
                case 'delete':
                    if ($event->attendees()->count() === 0) {
                        $event->delete();
                        $count++;
                    }
                    break;
            }
        }
        
        $actionText = [
            'approve' => 'approved',
            'reject' => 'rejected',
            'cancel' => 'cancelled',
            'delete' => 'deleted',
        ];
        
        return redirect()->back()
            ->with('success', "{$count} events have been {$actionText[$action]} successfully.");
    }

    public function attendees($id)
    {
        $event = Event::with('host:id,name')->findOrFail($id);
        
        $attendees = EventAttendee::with('user:id,name,phone_number,email')
            ->where('event_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('admin.events.attendees', compact('event', 'attendees'));
    }
}
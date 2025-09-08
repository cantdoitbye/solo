<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withTrashed();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'deleted') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $status);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.users.index', compact('users'));
    }

    public function show($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        
        // Get user's events
        $userEvents = EventAttendee::with(['event' => function($query) {
            $query->with(['host:id,name,phone_number']);
        }])
        ->where('user_id', $id)
        ->orderBy('created_at', 'desc')
        ->get();

        // Get user's hosted events
        $hostedEvents = Event::where('host_id', $id)
            ->withCount('attendees')
            ->orderBy('created_at', 'desc')
            ->get();

        // User statistics
        $stats = [
            'total_events_joined' => $userEvents->count(),
            'confirmed_events' => $userEvents->where('status', 'confirmed')->count(),
            'cancelled_events' => $userEvents->where('status', 'cancelled')->count(),
            'hosted_events' => $hostedEvents->count(),
            'total_attendees_hosted' => $hostedEvents->sum('attendees_count'),
        ];

        return view('admin.users.show', compact('user', 'userEvents', 'hostedEvents', 'stats'));
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        
        $request->validate([
            'action' => 'required|in:block,unblock',
            'reason' => 'required_if:action,block|max:500',
        ]);

        if ($request->action === 'block') {
            $user->update([
                'status' => 'blocked',
                'blocked_at' => now(),
                'block_reason' => $request->reason,
            ]);
            
            // Revoke all user's tokens
            $user->tokens()->delete();
            
            $message = 'User has been blocked successfully.';
        } else {
            $user->update([
                'status' => 'active',
                'blocked_at' => null,
                'block_reason' => null,
            ]);
            
            $message = 'User has been unblocked successfully.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Soft delete the user
        $user->delete();
        
        // Revoke all tokens
        $user->tokens()->delete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User has been deleted successfully.');
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        
        return redirect()->back()
            ->with('success', 'User has been restored successfully.');
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Banner;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\SuggestedLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        
        // Check if admin user exists and is active
        $adminUser = AdminUser::where('email', $credentials['email'])->first();
        
        if (!$adminUser || !$adminUser->isActive()) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records or account is inactive.',
            ])->withInput();
        }

        if (Auth::guard('admin')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            
            // Create Sanctum token for API access
            $token = $adminUser->createToken('admin-token')->plainTextToken;
            Session::put('admin_token', $token);
            
            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Welcome back, ' . $adminUser->name . '!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        // Revoke all tokens for this admin user
        if (Auth::guard('admin')->check()) {
            Auth::guard('admin')->user()->tokens()->delete();
        }
        
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Session::forget('admin_token');

        return redirect()->route('admin.login')
            ->with('success', 'You have been logged out successfully.');
    }

  public function dashboard()
    {
        // Get dashboard statistics
        $stats = [
            'total_users' => User::count(),
            'paid_users' => User::where('is_paid_member', true)->count(),
            'free_users' => User::where('is_paid_member', false)->count(),
            'active_users' => User::where('status', 'active')->count(),
            'blocked_users' => User::where('status', 'blocked')->count(),
            'new_users_week' => User::where('created_at', '>=', now()->subWeek())->count(),
            
            'total_events' => Event::count(),
            'pending_events' => Event::where('status', 'pending')->count(),
            'published_events' => Event::where('status', 'published')->count(),
            'total_attendees' => EventAttendee::count(),
            
            'total_locations' => SuggestedLocation::count(),
            'active_locations' => SuggestedLocation::where('is_active', true)->count(),
            
            'total_banners' => Banner::count(),
            'active_banners' => Banner::where('is_active', true)->count(),
        ];

        // Get recent events
        $recentEvents = Event::with(['host:id,name,phone_number'])
            ->withCount('attendees')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentEvents'));
    }

}
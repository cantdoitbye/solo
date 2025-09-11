@extends('admin.layouts.app')

@section('title', 'Dashboard - Admin Panel')
@section('page-title', 'Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <!-- Stats Cards -->
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold">{{ number_format($stats['total_users'] ?? 0) }}</h2>
                        <p class="mb-0 opacity-75">Total Users</p>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

     <div class="col-xl-3 col-md-6">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold">{{ number_format($stats['paid_users'] ?? 0) }}</h2>
                        <p class="mb-0 opacity-75">Paid Users</p>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

     <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold">{{ number_format($stats['free_users'] ?? 0) }}</h2>
                        <p class="mb-0 opacity-75">Free Users</p>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold">{{ number_format($stats['total_events'] ?? 0) }}</h2>
                        <p class="mb-0 opacity-75">Total Events</p>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold">{{ number_format($stats['pending_events'] ?? 0) }}</h2>
                        <p class="mb-0 opacity-75">Pending Events</p>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0 fw-bold">{{ number_format($stats['total_locations'] ?? 0) }}</h2>
                        <p class="mb-0 opacity-75">Locations</p>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Events -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white border-0 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">Recent Events</h5>
                    <a href="{{ route('admin.events.index') }}" class="btn btn-sm btn-outline-primary">
                        View All <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Event Name</th>
                                <th>Host</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Attendees</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentEvents ?? [] as $event)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.events.show', $event->id) }}" class="text-decoration-none fw-semibold">
                                        {{ $event->name }}
                                    </a>
                                </td>
                                <td>{{ $event->host->name ?? 'N/A' }}</td>
                                <td>{{ $event->event_date ? date('M d, Y', strtotime($event->event_date)) : 'N/A' }}</td>
                                <td>
                                    <span class="status-badge 
                                        @if($event->status === 'published') bg-success text-white
                                        @elseif($event->status === 'pending') bg-warning text-dark
                                        @elseif($event->status === 'rejected') bg-danger text-white
                                        @else bg-secondary text-white @endif">
                                        {{ ucfirst($event->status) }}
                                    </span>
                                </td>
                                <td>{{ $event->attendees_count ?? 0 }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                    <div>No events found</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions & Stats -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header bg-white border-0 pb-0">
                <h5 class="mb-0 fw-semibold">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.locations.create') }}" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>Add Location
                    </a>
                    <a href="{{ route('admin.banners.create') }}" class="btn btn-outline-success">
                        <i class="fas fa-image me-2"></i>Add Banner
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-info">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                    <a href="{{ route('admin.events.index', ['status' => 'pending']) }}" class="btn btn-outline-warning">
                        <i class="fas fa-clock me-2"></i>Review Events
                    </a>
                </div>
            </div>
        </div>
        
        <!-- User Activity -->
        <div class="card">
            <div class="card-header bg-white border-0 pb-0">
                <h5 class="mb-0 fw-semibold">User Activity</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Active Users</small>
                        <small class="text-muted">{{ $stats['active_users'] ?? 0 }}</small>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" style="width: 75%"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Blocked Users</small>
                        <small class="text-muted">{{ $stats['blocked_users'] ?? 0 }}</small>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-danger" style="width: 15%"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">New Signups (This Week)</small>
                        <small class="text-muted">{{ $stats['new_users_week'] ?? 0 }}</small>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-info" style="width: 45%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white border-0 pb-0">
                <h5 class="mb-0 fw-semibold">System Overview</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="text-primary mb-1">{{ number_format($stats['total_banners'] ?? 0) }}</h4>
                            <p class="text-muted mb-0">Total Banners</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="text-success mb-1">{{ number_format($stats['active_banners'] ?? 0) }}</h4>
                            <p class="text-muted mb-0">Active Banners</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="text-info mb-1">{{ number_format($stats['published_events'] ?? 0) }}</h4>
                            <p class="text-muted mb-0">Published Events</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-warning mb-1">{{ number_format($stats['total_attendees'] ?? 0) }}</h4>
                        <p class="text-muted mb-0">Total Attendees</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto-refresh dashboard stats every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000);
</script>
@endsection
                        
@extends('admin.layouts.app')

@section('title', 'User Details - Admin Panel')
@section('page-title', 'User Details')

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Users
        </a>
        
        @if(!$user->deleted_at)
            @if($user->status === 'active')
                <button type="button" 
                        class="btn btn-warning"
                        data-bs-toggle="modal" 
                        data-bs-target="#blockUserModal">
                    <i class="fas fa-ban me-1"></i>Block User
                </button>
            @elseif($user->status === 'blocked')
                <form method="POST" action="{{ route('admin.users.toggle-status', $user->id) }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="action" value="unblock">
                    <button type="submit" 
                            class="btn btn-success"
                            onclick="return confirm('Are you sure you want to unblock this user?')">
                        <i class="fas fa-check me-1"></i>Unblock User
                    </button>
                </form>
            @endif
        @endif
    </div>
@endsection

@section('content')
<div class="row g-4">
    <!-- User Profile Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                     style="width: 100px; height: 100px;">
                    <span class="text-white fw-bold fs-1">
                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                    </span>
                </div>
                
                <h4 class="fw-bold mb-1">{{ $user->name ?? 'N/A' }}</h4>
                <p class="text-muted mb-3">User ID: #{{ $user->id }}</p>
                
                <div class="mb-3">
                    @if($user->deleted_at)
                        <span class="status-badge bg-secondary text-white fs-6">Deleted</span>
                    @else
                        <span class="status-badge fs-6
                            @if($user->status === 'active') bg-success text-white
                            @elseif($user->status === 'blocked') bg-danger text-white
                            @else bg-warning text-dark @endif">
                            {{ ucfirst($user->status) }}
                        </span>
                    @endif
                </div>
                
                @if($user->blocked_at)
                    <div class="alert alert-warning">
                        <strong>Blocked on:</strong> {{ $user->blocked_at->format('M d, Y \a\t g:i A') }}
                        @if($user->block_reason)
                            <br><strong>Reason:</strong> {{ $user->block_reason }}
                        @endif
                    </div>
                @endif
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fw-bold text-primary fs-4">{{ $stats['total_events_joined'] }}</div>
                        <small class="text-muted">Events Joined</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-success fs-4">{{ $stats['hosted_events'] }}</div>
                        <small class="text-muted">Events Hosted</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-info fs-4">{{ $stats['confirmed_events'] }}</div>
                        <small class="text-muted">Confirmed</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="card mt-4">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0 fw-semibold">Contact Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Phone Number</label>
                    <div class="fw-semibold">{{ $user->phone_number ?? 'N/A' }}</div>
                </div>
                
                @if($user->email)
                <div class="mb-3">
                    <label class="form-label text-muted small">Email</label>
                    <div class="fw-semibold">{{ $user->email }}</div>
                </div>
                @endif
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Location</label>
                    <div class="fw-semibold">
                        {{ $user->city ?? 'N/A' }}
                        @if($user->state), {{ $user->state }}@endif
                        @if($user->country)<br>{{ $user->country }}@endif
                    </div>
                </div>
                
                @if($user->age)
                <div class="mb-3">
                    <label class="form-label text-muted small">Age</label>
                    <div class="fw-semibold">{{ $user->age }} years old</div>
                </div>
                @endif
                
                @if($user->gender)
                <div class="mb-3">
                    <label class="form-label text-muted small">Gender</label>
                    <div class="fw-semibold">{{ ucfirst($user->gender) }}</div>
                </div>
                @endif
                
                <div class="mb-0">
                    <label class="form-label text-muted small">Member Since</label>
                    <div class="fw-semibold">{{ $user->created_at->format('M d, Y') }}</div>
                    <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Activity -->
    <div class="col-lg-8">
        <!-- Events Joined -->
        <div class="card mb-4">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-calendar-check me-2 text-primary"></i>Events Joined ({{ $userEvents->count() }})
                </h6>
            </div>
            <div class="card-body">
                @if($userEvents->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Event</th>
                                    <th>Host</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Tokens Paid</th>
                                    <th>Joined At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($userEvents as $attendance)
                                <tr>
                                    <td>
                                        @if($attendance->event)
                                            <a href="{{ route('admin.events.show', $attendance->event->id) }}" 
                                               class="text-decoration-none fw-semibold">
                                                {{ $attendance->event->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">Event not found</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $attendance->event->host->name ?? 'N/A' }}
                                    </td>
                                    <td>
                                        {{ $attendance->event ? $attendance->event->event_date->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td>
                                        <span class="status-badge 
                                            @if($attendance->status === 'confirmed') bg-success text-white
                                            @elseif($attendance->status === 'interested') bg-info text-white
                                            @elseif($attendance->status === 'cancelled') bg-danger text-white
                                            @else bg-secondary text-white @endif">
                                            {{ ucfirst($attendance->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $attendance->tokens_paid ?? 0 }} tokens</td>
                                    <td>{{ $attendance->joined_at ? $attendance->joined_at->format('M d, Y') : 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                        <div class="text-muted">No events joined yet</div>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Hosted Events -->
        <div class="card">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-crown me-2 text-warning"></i>Events Hosted ({{ $hostedEvents->count() }})
                </h6>
            </div>
            <div class="card-body">
                @if($hostedEvents->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Event Name</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Attendees</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hostedEvents as $event)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.events.show', $event->id) }}" 
                                           class="text-decoration-none fw-semibold">
                                            {{ $event->name }}
                                        </a>
                                    </td>
                                    <td>{{ $event->event_date ? $event->event_date->format('M d, Y') : 'N/A' }}</td>
                                    <td>
                                        <span class="status-badge 
                                            @if($event->status === 'published') bg-success text-white
                                            @elseif($event->status === 'pending') bg-warning text-dark
                                            @elseif($event->status === 'rejected') bg-danger text-white
                                            @elseif($event->status === 'cancelled') bg-secondary text-white
                                            @else bg-info text-white @endif">
                                            {{ ucfirst($event->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $event->attendees_count ?? 0 }}</td>
                                    <td>{{ $event->created_at->format('M d, Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-plus-circle fa-2x text-muted mb-2"></i>
                        <div class="text-muted">No events hosted yet</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Block User Modal -->
<div class="modal fade" id="blockUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.users.toggle-status', $user->id) }}">
                @csrf
                <input type="hidden" name="action" value="block">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-ban text-warning me-2"></i>Block User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Are you sure you want to block <strong>{{ $user->name }}</strong>?</p>
                    
                    <div class="mb-3">
                        <label for="blockReason" class="form-label">Reason for blocking <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="blockReason" 
                                  name="reason" 
                                  rows="3" 
                                  placeholder="Enter the reason for blocking this user..."
                                  required></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This will immediately log out the user and prevent them from accessing the application.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-ban me-2"></i>Block User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
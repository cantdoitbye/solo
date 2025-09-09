@extends('admin.layouts.app')

@section('title', 'Event Details - Admin Panel')
@section('page-title', 'Event Details')

@section('page-actions')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Events
        </a>
        
        @if($event->status === 'pending')
            <button type="button" 
                    class="btn btn-success" 
                    data-bs-toggle="modal" 
                    data-bs-target="#approveEventModal">
                <i class="fas fa-check me-1"></i>Approve
            </button>
            <button type="button" 
                    class="btn btn-danger" 
                    data-bs-toggle="modal" 
                    data-bs-target="#rejectEventModal">
                <i class="fas fa-times me-1"></i>Reject
            </button>
        @endif
        
        @if(in_array($event->status, ['published', 'pending']))
            <button type="button" 
                    class="btn btn-warning" 
                    data-bs-toggle="modal" 
                    data-bs-target="#cancelEventModal">
                <i class="fas fa-ban me-1"></i>Cancel
            </button>
        @endif
        
        @if($event->attendees_count == 0)
            <form method="POST" action="{{ route('admin.events.destroy', $event->id) }}" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="btn btn-outline-danger"
                        onclick="return confirmDelete('Are you sure you want to delete this event?')">
                    <i class="fas fa-trash me-1"></i>Delete
                </button>
            </form>
        @endif
    </div>
@endsection

@section('content')
<div class="row g-4">
    <!-- Event Information -->
    <div class="col-lg-8">
        <!-- Basic Event Details -->
        <div class="card mb-4">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>Event Information
                    </h5>
                    <div>
                        <span class="status-badge 
                            @if($event->status === 'published') bg-success text-white
                            @elseif($event->status === 'pending') bg-warning text-dark
                            @elseif($event->status === 'rejected') bg-danger text-white
                            @elseif($event->status === 'cancelled') bg-secondary text-white
                            @else bg-info text-white @endif">
                            {{ ucfirst($event->status) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h4 class="fw-bold text-primary mb-3">{{ $event->name }}</h4>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small">Description</label>
                            <p class="mb-0">{{ $event->description ?? 'No description provided' }}</p>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label text-muted small">Event Date</label>
                                <div class="fw-semibold">
                                    @if($event->event_date)
                                        <i class="fas fa-calendar me-1 text-primary"></i>
                                        {{ $event->event_date ? $event->event_date->format('M d, Y') : 'N/A' }}
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-sm-6">
                                <label class="form-label text-muted small">Event Time</label>
                                <div class="fw-semibold">
                                    @if($event->event_time)
                                        <i class="fas fa-clock me-1 text-primary"></i>
                                        {{ $event->event_time->format('g:i A') }}
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-sm-6">
                                <label class="form-label text-muted small">Group Size</label>
                                <div class="fw-semibold">
                                    <i class="fas fa-users me-1 text-primary"></i>
                                    {{ $event->min_group_size ?? 'N/A' }} - {{ $event->max_group_size ?? 'N/A' }} people
                                </div>
                            </div>
                            
                            <div class="col-sm-6">
                                <label class="form-label text-muted small">Token Cost</label>
                                <div class="fw-semibold">
                                    <i class="fas fa-coins me-1 text-warning"></i>
                                    {{ $event->token_cost_per_attendee ?? 'Free' }} tokens
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Event Image or Location Image -->
                        @if($event->suggestedLocation && $event->suggestedLocation->primaryImage)
                            <div class="mb-3">
                                <img src="{{ asset('storage/' . $event->suggestedLocation->primaryImage->image_path) }}" 
                                     alt="{{ $event->name }}"
                                     class="img-fluid rounded"
                                     style="max-height: 200px; width: 100%; object-fit: cover;">
                            </div>
                        @elseif($event->event_image)
                            <div class="mb-3">
                                <img src="{{ $event->event_image }}" 
                                     alt="{{ $event->name }}"
                                     class="img-fluid rounded"
                                     style="max-height: 200px; width: 100%; object-fit: cover;">
                            </div>
                        @else
                            <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" 
                                 style="height: 200px;">
                                <div class="text-center text-muted">
                                    <i class="fas fa-image fa-3x mb-2"></i>
                                    <div>No image available</div>
                                </div>
                            </div>
                        @endif
                        
                        <!-- Age & Gender Restrictions -->
                        @if($event->min_age || $event->max_age || $event->gender_rule_enabled)
                            <div class="alert alert-info">
                                <h6 class="fw-semibold mb-2">Restrictions</h6>
                                @if($event->min_age || $event->max_age)
                                    <div><strong>Age:</strong> {{ $event->min_age ?? 'No min' }} - {{ $event->max_age ?? 'No max' }} years</div>
                                @endif
                                @if($event->gender_rule_enabled && $event->allowed_genders)
                                    <div><strong>Gender:</strong> {{ implode(', ', $event->allowed_genders) }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Location Details -->
        <div class="card mb-4">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-map-marker-alt me-2 text-success"></i>Location Details
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Venue Name</label>
                        <div class="fw-semibold">{{ $event->venue_name ?? 'Not specified' }}</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Venue Type</label>
                        <div class="fw-semibold">
                            @if($event->venueType)
                                {{ $event->venueType->name }}
                            @else
                                <span class="text-muted">Not specified</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label text-muted small">Address</label>
                        <div class="fw-semibold">{{ $event->venue_address ?? 'Address not provided' }}</div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label text-muted small">City</label>
                        <div class="fw-semibold">{{ $event->city ?? 'N/A' }}</div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label text-muted small">State</label>
                        <div class="fw-semibold">{{ $event->state ?? 'N/A' }}</div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Country</label>
                        <div class="fw-semibold">{{ $event->country ?? 'N/A' }}</div>
                    </div>
                    
                    @if($event->latitude && $event->longitude)
                        <div class="col-12">
                            <label class="form-label text-muted small">Coordinates</label>
                            <div class="fw-semibold font-monospace">
                                {{ number_format($event->latitude, 6) }}, {{ number_format($event->longitude, 6) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Admin Notes & Review -->
        @if($event->status === 'rejected' || $event->admin_notes || $event->rejection_reason)
            <div class="card mb-4">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-clipboard-list me-2 text-warning"></i>Admin Review
                    </h6>
                </div>
                <div class="card-body">
                    @if($event->rejection_reason)
                        <div class="alert alert-danger">
                            <h6 class="fw-semibold">Rejection Reason:</h6>
                            <p class="mb-0">{{ $event->rejection_reason }}</p>
                        </div>
                    @endif
                    
                    @if($event->admin_notes)
                        <div class="mb-3">
                            <label class="form-label text-muted small">Admin Notes</label>
                            <div class="bg-light p-3 rounded">{{ $event->admin_notes }}</div>
                        </div>
                    @endif
                    
                    {{-- @if($event->reviewed_at)
                        <div class="text-muted small">
                            Reviewed on {{ $event->reviewed_at->format('M d, Y \a\t g:i A') }}
                            @if($event->reviewed_by)
                                by Admin ID: {{ $event->reviewed_by }}
                            @endif
                        </div>
                    @endif --}}
                </div>
            </div>
        @endif
    </div>
    
    <!-- Right Sidebar -->
    <div class="col-lg-4">
        <!-- Host Information -->
        <div class="card mb-4">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-user-crown me-2 text-warning"></i>Event Host
                </h6>
            </div>
            <div class="card-body">
                @if($event->host)
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                             style="width: 50px; height: 50px;">
                            <span class="text-white fw-bold">
                                {{ strtoupper(substr($event->host->name ?? 'U', 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $event->host->name ?? 'Unknown' }}</div>
                            <small class="text-muted">Host ID: #{{ $event->host->id }}</small>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <strong>Phone:</strong> {{ $event->host->phone_number ?? 'Not provided' }}
                    </div>
                    
                    @if($event->host->email)
                        <div class="mb-2">
                            <strong>Email:</strong> {{ $event->host->email }}
                        </div>
                    @endif
                    
                    <div class="mb-3">
                        <strong>Member Since:</strong> {{ $event->host->created_at ? $event->host->created_at->format('M Y') : 'N/A' }}
                    </div>
                    
                    <a href="{{ route('admin.users.show', $event->host->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-user me-1"></i>View Host Profile
                    </a>
                @else
                    <div class="text-muted">Host information not available</div>
                @endif
            </div>
        </div>
        
        <!-- Event Statistics -->
        <div class="card mb-4">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-chart-bar me-2 text-info"></i>Event Statistics
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="fw-bold text-primary fs-4">{{ $stats['total_attendees'] }}</div>
                        <small class="text-muted">Total Attendees</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="fw-bold text-success fs-4">{{ $stats['confirmed_attendees'] }}</div>
                        <small class="text-muted">Confirmed</small>
                    </div>
                    {{-- <div class="col-6 mb-3">
                        <div class="fw-bold text-info fs-4">{{ $stats['interested_attendees'] }}</div>
                        <small class="text-muted">Interested</small>
                    </div> --}}
                    <div class="col-6 mb-3">
                        <div class="fw-bold text-warning fs-4">{{ $stats['total_revenue'] }}</div>
                        <small class="text-muted">Total Tokens</small>
                    </div>
                </div>
                
                @if($stats['total_attendees'] > 0)
                    <div class="mt-3">
                        {{-- <a href="{{ route('admin.events.attendees', $event->id) }}" class="btn btn-outline-info btn-sm w-100">
                            <i class="fas fa-users me-1"></i>View All Attendees
                        </a> --}}
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Event Timeline -->
        <div class="card">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-history me-2 text-secondary"></i>Event Timeline
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <div class="fw-semibold">Event Created</div>
                            <small class="text-muted">{{ $event->created_at->format('M d, Y g:i A') }}</small>
                        </div>
                    </div>
                    
                    @if($event->published_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold">Published</div>
                                <small class="text-muted">{{ $event->published_at->format('M d, Y g:i A') }}</small>
                            </div>
                        </div>
                    @endif
                    
                    @if($event->cancelled_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-danger"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold">Cancelled</div>
                                <small class="text-muted">{{ $event->cancelled_at->format('M d, Y g:i A') }}</small>
                            </div>
                        </div>
                    @endif
                    
                    <div class="timeline-item">
                        <div class="timeline-marker bg-secondary"></div>
                        <div class="timeline-content">
                            <div class="fw-semibold">Last Updated</div>
                            <small class="text-muted">{{ $event->updated_at->format('M d, Y g:i A') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Event Modal -->
<div class="modal fade" id="approveEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.events.update-status', $event->id) }}">
                @csrf
                <input type="hidden" name="status" value="published">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-check text-success me-2"></i>Approve Event
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Are you sure you want to approve <strong>{{ $event->name }}</strong>?</p>
                    
                    <div class="mb-3">
                        <label for="approveNotes" class="form-label">Admin Notes (Optional)</label>
                        <textarea class="form-control" 
                                  id="approveNotes" 
                                  name="admin_notes" 
                                  rows="3" 
                                  placeholder="Add any notes about this approval..."></textarea>
                    </div>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle me-2"></i>
                        This event will be published and visible to users immediately. The host will be notified via push notification.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Approve Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Event Modal -->
<div class="modal fade" id="rejectEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.events.update-status', $event->id) }}">
                @csrf
                <input type="hidden" name="status" value="rejected">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-times text-danger me-2"></i>Reject Event
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Are you sure you want to reject <strong>{{ $event->name }}</strong>?</p>
                    
                    <div class="mb-3">
                        <label for="rejectionReason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="rejectionReason" 
                                  name="rejection_reason" 
                                  rows="3" 
                                  placeholder="Please provide a reason for rejecting this event..."
                                  required></textarea>
                    </div>
                    
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        The host will be notified about this rejection via push notification.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Reject Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Event Modal -->
<div class="modal fade" id="cancelEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.events.update-status', $event->id) }}">
                @csrf
                <input type="hidden" name="status" value="cancelled">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-ban text-warning me-2"></i>Cancel Event
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Are you sure you want to cancel <strong>{{ $event->name }}</strong>?</p>
                    
                    <div class="mb-3">
                        <label for="cancelReason" class="form-label">Cancellation Reason</label>
                        <textarea class="form-control" 
                                  id="cancelReason" 
                                  name="admin_notes" 
                                  rows="3" 
                                  placeholder="Enter reason for cancellation..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        All attendees will be notified and refunded if applicable.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-ban me-2"></i>Cancel Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
.timeline {
    position: relative;
    padding-left: 1.5rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -1.4rem;
    top: 1rem;
    width: 2px;
    height: calc(100% - 0.5rem);
    background: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: -1.75rem;
    top: 0.25rem;
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    padding-left: 0.5rem;
}
</style>
@endsection
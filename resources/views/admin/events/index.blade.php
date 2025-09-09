@extends('admin.layouts.app')

@section('title', 'Events Management - Admin Panel')
@section('page-title', 'Events Management')

@section('page-actions')
    <div class="d-flex gap-2 flex-wrap">
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.events.index') }}" 
               class="btn {{ !request('status') ? 'btn-primary' : 'btn-outline-primary' }}">
                All ({{ $statusCounts['all'] }})
            </a>
            <a href="{{ route('admin.events.index', ['status' => 'pending']) }}" 
               class="btn {{ request('status') === 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">
                Pending ({{ $statusCounts['pending'] }})
            </a>
            <a href="{{ route('admin.events.index', ['status' => 'published']) }}" 
               class="btn {{ request('status') === 'published' ? 'btn-success' : 'btn-outline-success' }}">
                Published ({{ $statusCounts['published'] }})
            </a>
            <a href="{{ route('admin.events.index', ['status' => 'rejected']) }}" 
               class="btn {{ request('status') === 'rejected' ? 'btn-danger' : 'btn-outline-danger' }}">
                Rejected ({{ $statusCounts['rejected'] }})
            </a>
        </div>
        
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.events.index', ['date_filter' => 'upcoming']) }}" 
               class="btn {{ request('date_filter') === 'upcoming' ? 'btn-info' : 'btn-outline-info' }}">
                Upcoming
            </a>
            <a href="{{ route('admin.events.index', ['date_filter' => 'today']) }}" 
               class="btn {{ request('date_filter') === 'today' ? 'btn-info' : 'btn-outline-info' }}">
                Today
            </a>
            <a href="{{ route('admin.events.index', ['date_filter' => 'past']) }}" 
               class="btn {{ request('date_filter') === 'past' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                Past
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="card">
    <div class="card-header bg-white border-0">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0 fw-semibold">
                    <i class="fas fa-calendar-check me-2 text-primary"></i>All Events
                </h5>
            </div>
            <div class="col-md-6">
                <form method="GET" action="{{ route('admin.events.index') }}" class="d-flex gap-2">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="date_filter" value="{{ request('date_filter') }}">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           placeholder="Search events..."
                           value="{{ request('search') }}">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.events.index', array_filter(['status' => request('status'), 'date_filter' => request('date_filter')])) }}" 
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bulk Actions -->
    @if($events->count() > 0)
    <div class="card-body border-bottom">
        <form method="POST" action="{{ route('admin.events.bulk-action') }}" id="bulkActionForm">
            @csrf
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex gap-2 align-items-center">
                        <input type="checkbox" id="selectAll" class="form-check-input">
                        <label for="selectAll" class="form-check-label me-3">Select All</label>
                        
                        <select name="action" class="form-select form-select-sm" style="width: auto;" required>
                            <option value="">Bulk Actions</option>
                            <option value="approve">Approve Selected</option>
                            <option value="reject">Reject Selected</option>
                            <option value="cancel">Cancel Selected</option>
                            <option value="delete">Delete Selected</option>
                        </select>
                        
                        <button type="submit" class="btn btn-sm btn-primary" onclick="return confirmBulkAction()">
                            Apply
                        </button>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <small class="text-muted">
                        <span id="selectedCount">0</span> events selected
                    </small>
                </div>
            </div>
            
            <!-- Reason field for reject/cancel actions -->
            <div class="mt-3 d-none" id="reasonField">
                <input type="text" 
                       name="reason" 
                       class="form-control form-control-sm" 
                       placeholder="Enter reason (required for reject/cancel)">
            </div>
        </form>
    </div>
    @endif
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">
                            <input type="checkbox" id="selectAllHeader" class="form-check-input">
                        </th>
                        <th class="py-3">Event</th>
                        <th class="py-3">Host</th>
                        <th class="py-3">Date & Time</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Attendees</th>
                        <th class="py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                    <tr>
                        <td class="px-4 py-3">
                            <input type="checkbox" 
                                   name="event_ids[]" 
                                   value="{{ $event->id }}" 
                                   class="form-check-input event-checkbox">
                        </td>
                        <td class="py-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    @if($event->suggestedLocation && $event->suggestedLocation->primaryImage)
                                        <img src="{{ asset('storage/' . $event->suggestedLocation->primaryImage->image_path) }}" 
                                             alt="{{ $event->name }}"
                                             class="rounded" 
                                             style="width: 50px; height: 35px; object-fit: cover;">
                                    @else
                                        <div class="bg-primary rounded d-flex align-items-center justify-content-center text-white fw-bold" 
                                             style="width: 50px; height: 35px; font-size: 12px;">
                                            {{ strtoupper(substr($event->name, 0, 2)) }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $event->name }}</div>
                                    <small class="text-muted">{{ $event->venue_name ?? 'N/A' }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div>{{ $event->host->name ?? 'N/A' }}</div>
                            @if($event->host && $event->host->phone_number)
                                <small class="text-muted">{{ $event->host->phone_number }}</small>
                            @endif
                        </td>
                        <td class="py-3">
                            @if($event->event_date)
                                <div>{{ $event->event_date->format('M d, Y') }}</div>
                                @if($event->event_time)
                                    <small class="text-muted">{{ $event->event_time->format('g:i A') }}</small>
                                @endif
                            @else
                                <span class="text-muted">Not set</span>
                            @endif
                        </td>
                        <td class="py-3">
                            <span class="status-badge 
                                @if($event->status === 'published') bg-success text-white
                                @elseif($event->status === 'pending') bg-warning text-dark
                                @elseif($event->status === 'rejected') bg-danger text-white
                                @elseif($event->status === 'cancelled') bg-secondary text-white
                                @else bg-info text-white @endif">
                                {{ ucfirst($event->status) }}
                            </span>
                            @if($event->status === 'rejected' && $event->rejection_reason)
                                <div class="mt-1">
                                    <small class="text-muted" title="{{ $event->rejection_reason }}">
                                        <i class="fas fa-info-circle"></i> {{ Str::limit($event->rejection_reason, 30) }}
                                    </small>
                                </div>
                            @endif
                        </td>
                        <td class="py-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users me-1 text-muted"></i>
                                <span class="fw-semibold">{{ $event->attendees_count ?? 0 }}</span>
                                @if($event->max_group_size)
                                    <span class="text-muted">/ {{ $event->max_group_size }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.events.show', $event->id) }}" 
                                   class="btn btn-outline-primary" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                @if($event->status === 'draft' || $event->status === 'completed')
                                    <button type="button" 
                                            class="btn btn-outline-success" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#approveEventModal"
                                            data-event-id="{{ $event->id }}"
                                            data-event-name="{{ $event->name }}"
                                            title="Approve Event">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#rejectEventModal"
                                            data-event-id="{{ $event->id }}"
                                            data-event-name="{{ $event->name }}"
                                            title="Reject Event">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @endif
                                
                                @if(in_array($event->status, ['published', 'pending']))
                                    <button type="button" 
                                            class="btn btn-outline-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#cancelEventModal"
                                            data-event-id="{{ $event->id }}"
                                            data-event-name="{{ $event->name }}"
                                            title="Cancel Event">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                @endif
                                
                                @if($event->attendees_count == 0)
                                    <form method="POST" action="{{ route('admin.events.destroy', $event->id) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-outline-danger"
                                                title="Delete Event"
                                                onclick="return confirmDelete('Are you sure you want to delete this event?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <div class="text-muted">
                                @if(request('search'))
                                    No events found for "{{ request('search') }}"
                                @else
                                    No events found
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($events->hasPages())
    <div class="card-footer bg-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Showing {{ $events->firstItem() }} to {{ $events->lastItem() }} of {{ $events->total() }} results
            </div>
            {{ $events->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Approve Event Modal -->
<div class="modal fade" id="approveEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="approveEventForm">
                @csrf
                <input type="hidden" name="status" value="published">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-check text-success me-2"></i>Approve Event
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Are you sure you want to approve <strong id="approveEventName"></strong>?</p>
                    
                    <div class="mb-3">
                        <label for="approveNotes" class="form-label">Admin Notes (Optional)</label>
                        <textarea class="form-control" 
                                  id="approveNotes" 
                                  name="admin_notes" 
                                  rows="2" 
                                  placeholder="Add any notes about this approval..."></textarea>
                    </div>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle me-2"></i>
                        This event will be published and visible to users immediately.
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
            <form method="POST" id="rejectEventForm">
                @csrf
                <input type="hidden" name="status" value="rejected">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-times text-danger me-2"></i>Reject Event
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Are you sure you want to reject <strong id="rejectEventName"></strong>?</p>
                    
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
                        The host will be notified about this rejection with the reason provided.
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
            <form method="POST" id="cancelEventForm">
                @csrf
                <input type="hidden" name="status" value="cancelled">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-ban text-warning me-2"></i>Cancel Event
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Are you sure you want to cancel <strong id="cancelEventName"></strong>?</p>
                    
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
<script>
    // Handle event action modals
    document.addEventListener('DOMContentLoaded', function() {
        // Approve event modal
        document.getElementById('approveEventModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const eventId = button.getAttribute('data-event-id');
            const eventName = button.getAttribute('data-event-name');
            
            document.getElementById('approveEventName').textContent = eventName;
            document.getElementById('approveEventForm').action = `/public/admin/events/${eventId}/update-status`;
        });

        // Reject event modal
        document.getElementById('rejectEventModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const eventId = button.getAttribute('data-event-id');
            const eventName = button.getAttribute('data-event-name');
            
            document.getElementById('rejectEventName').textContent = eventName;
            document.getElementById('rejectEventForm').action = `/public/admin/events/${eventId}/update-status`;
        });

        // Cancel event modal
        document.getElementById('cancelEventModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const eventId = button.getAttribute('data-event-id');
            const eventName = button.getAttribute('data-event-name');
            
            document.getElementById('cancelEventName').textContent = eventName;
            document.getElementById('cancelEventForm').action = `/public/admin/events/${eventId}/update-status`;
        });

        // Bulk actions
        const selectAllCheckboxes = document.querySelectorAll('#selectAll, #selectAllHeader');
        const eventCheckboxes = document.querySelectorAll('.event-checkbox');
        const selectedCountSpan = document.getElementById('selectedCount');
        const actionSelect = document.querySelector('select[name="action"]');
        const reasonField = document.getElementById('reasonField');

        // Select all functionality
        selectAllCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                eventCheckboxes.forEach(eventCheckbox => {
                    eventCheckbox.checked = this.checked;
                });
                updateSelectedCount();
                syncSelectAllCheckboxes();
            });
        });

        // Individual checkbox change
        eventCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectedCount();
                syncSelectAllCheckboxes();
            });
        });

        // Show/hide reason field based on action
        if (actionSelect) {
            actionSelect.addEventListener('change', function() {
                if (this.value === 'reject' || this.value === 'cancel') {
                    reasonField.classList.remove('d-none');
                    reasonField.querySelector('input').required = true;
                } else {
                    reasonField.classList.add('d-none');
                    reasonField.querySelector('input').required = false;
                }
            });
        }

        function updateSelectedCount() {
            const selectedCount = document.querySelectorAll('.event-checkbox:checked').length;
            if (selectedCountSpan) {
                selectedCountSpan.textContent = selectedCount;
            }
        }

        function syncSelectAllCheckboxes() {
            const totalCheckboxes = eventCheckboxes.length;
            const checkedCheckboxes = document.querySelectorAll('.event-checkbox:checked').length;
            
            selectAllCheckboxes.forEach(checkbox => {
                checkbox.checked = totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0;
                checkbox.indeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
            });
        }
    });

    function confirmBulkAction() {
        const selectedEvents = document.querySelectorAll('.event-checkbox:checked');
        const action = document.querySelector('select[name="action"]').value;
        
        if (selectedEvents.length === 0) {
            alert('Please select at least one event.');
            return false;
        }
        
        if (!action) {
            alert('Please select an action.');
            return false;
        }
        
        const actionMessages = {
            'approve': 'approve',
            'reject': 'reject',
            'cancel': 'cancel',
            'delete': 'delete'
        };
        
        return confirm(`Are you sure you want to ${actionMessages[action]} ${selectedEvents.length} selected event(s)?`);
    }
</script>
@endsection
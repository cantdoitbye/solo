@extends('admin.layouts.app')

@section('title', $location->name)
@section('subtitle', 'Location Details and Events')

@section('content')
<div class="row">
    <!-- Location Details -->
    <div class="col-xl-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                    {{ $location->name }}
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge {{ $location->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $location->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($location->category)
                        <span class="badge bg-info">{{ ucfirst($location->category) }}</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($location->description)
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Description</h6>
                        <p class="mb-0">{{ $location->description }}</p>
                    </div>
                @endif

                <!-- Location Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Venue Information</h6>
                        @if($location->venue_name)
                            <div class="mb-2">
                                <small class="text-muted">Venue Name:</small>
                                <div>{{ $location->venue_name }}</div>
                            </div>
                        @endif
                        @if($location->venue_address)
                            <div class="mb-2">
                                <small class="text-muted">Address:</small>
                                <div>{{ $location->venue_address }}</div>
                            </div>
                        @endif
                        @if($location->city || $location->state || $location->country)
                            <div class="mb-2">
                                <small class="text-muted">Location:</small>
                                <div>
                                    {{ collect([$location->city, $location->state, $location->country])->filter()->implode(', ') }}
                                    @if($location->postal_code)
                                        {{ $location->postal_code }}
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Coordinates & Links</h6>
                        @if($location->latitude && $location->longitude)
                            <div class="mb-2">
                                <small class="text-muted">Coordinates:</small>
                                <div>{{ $location->latitude }}, {{ $location->longitude }}</div>
                            </div>
                        @endif
                        @if($location->google_place_id)
                            <div class="mb-2">
                                <small class="text-muted">Google Place ID:</small>
                                <div class="font-monospace small">{{ $location->google_place_id }}</div>
                            </div>
                        @endif
                        @if($location->google_maps_url)
                            <div class="mb-2">
                                <small class="text-muted">Google Maps:</small>
                                <div>
                                    <a href="{{ $location->google_maps_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-1"></i>
                                        View on Google Maps
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Location Images -->
                @if($location->images && $location->images->count() > 0)
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Images</h6>
                        <div class="row g-3">
                            @foreach($location->images->sortBy('sort_order') as $image)
                                <div class="col-md-4 col-lg-3">
                                    <div class="card border-0 shadow-sm position-relative">
                                        @if($image->is_primary)
                                            <span class="badge bg-primary position-absolute top-0 start-0 m-2" style="z-index: 10;">
                                                Primary
                                            </span>
                                        @endif
                                        <img src="{{ asset('/'.  $image->image_url ) }}" 
                                             alt="{{ $location->name }} - Image {{ $loop->iteration }}"
                                             class="card-img-top"
                                             style="height: 150px; object-fit: cover;">
                                        <div class="card-body p-2">
                                            <small class="text-muted">
                                                @if($image->width && $image->height)
                                                    {{ $image->width }}x{{ $image->height }}
                                                @endif
                                                @if($image->file_size)
                                                    • {{ number_format($image->file_size / 1024, 1) }}KB
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif($location->image_url)
                    <!-- Fallback primary image -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Primary Image</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <img src="{{ $location->image_url }}" 
                                     alt="{{ $location->name }}"
                                     class="img-fluid rounded shadow-sm"
                                     style="max-height: 200px; object-fit: cover;">
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Google Place Details -->
                @if($location->google_place_details && is_array($location->google_place_details))
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Google Place Details</h6>
                        <div class="bg-light p-3 rounded">
                            <pre class="mb-0 small">{{ json_encode($location->google_place_details, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-xl-4">
        <!-- Quick Stats -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Quick Stats
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h4 class="text-primary mb-1">{{ $events->total() ?? $events->count() }}</h4>
                            <small class="text-muted">Total Events</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-info mb-1">{{ $location->sort_order ?? 'N/A' }}</h4>
                        <small class="text-muted">Sort Order</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Events -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Recent Events
                </h6>
                @if($events->count() > 0)
                    <small class="text-muted">Last {{ $events->count() }}</small>
                @endif
            </div>
            <div class="card-body">
              
                
                @if($events->count() > 0)
                    @foreach($events as $event)
                        <div class="d-flex justify-content-between align-items-start py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="flex-grow-1">
                                <div class="fw-bold mb-1">
                                    <a href="{{ route('admin.events.show', $event->id) }}" 
                                       class="text-decoration-none">
                                        {{ $event->name }}
                                    </a>
                                </div>
                                <small class="text-muted d-block mb-1">
                                    <i class="fas fa-user me-1"></i>
                                    {{ $event->host->name ?? 'Unknown Host' }}
                                </small>
                                @if($event->event_date)
                                    <small class="text-muted d-block">
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ $event->event_date->format('M j, Y') }}
                                        @if($event->event_time)
                                            at {{ $event->event_time->format('g:i A') }}
                                        @endif
                                    </small>
                                @endif
                                @if($event->min_group_size)
                                    <small class="text-muted d-block">
                                        <i class="fas fa-users me-1"></i>
                                        {{ $event->min_group_size }} {{ $event->min_group_size == 1 ? 'person' : 'people' }}
                                    </small>
                                @endif
                            </div>
                            <div class="text-end">
                                @if($event->status)
                                    <span class="badge badge-sm 
                                        {{ $event->status === 'published' ? 'bg-success' : 
                                           ($event->status === 'cancelled' ? 'bg-danger' : 
                                           ($event->status === 'pending' ? 'bg-warning' : 'bg-secondary')) }}">
                                        {{ ucfirst($event->status) }}
                                    </span>
                                @endif
                                <br>
                                <small class="text-muted">Created {{ $event->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @endforeach
                    
                    <!-- Pagination Links -->
                    @if($events->hasPages())
                        <div class="mt-3 pt-3 border-top">
                            <nav aria-label="Events pagination">
                                <ul class="pagination pagination-sm justify-content-center mb-0">
                                    {{-- Previous Page Link --}}
                                    @if ($events->onFirstPage())
                                        <li class="page-item disabled"><span class="page-link">Previous</span></li>
                                    @else
                                        <li class="page-item"><a class="page-link" href="{{ $events->previousPageUrl() }}" rel="prev">Previous</a></li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @foreach ($events->getUrlRange(1, $events->lastPage()) as $page => $url)
                                        @if ($page == $events->currentPage())
                                            <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                                        @else
                                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                                        @endif
                                    @endforeach

                                    {{-- Next Page Link --}}
                                    @if ($events->hasMorePages())
                                        <li class="page-item"><a class="page-link" href="{{ $events->nextPageUrl() }}" rel="next">Next</a></li>
                                    @else
                                        <li class="page-item disabled"><span class="page-link">Next</span></li>
                                    @endif
                                </ul>
                            </nav>
                            
                            {{-- Pagination Info --}}
                            <div class="text-center mt-2">
                                <small class="text-muted">
                                    Showing {{ $events->firstItem() ?? 0 }} to {{ $events->lastItem() ?? 0 }} of {{ $events->total() }} events
                                </small>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No events using this location yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.locations.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Locations
                    </a>
                </div>
                <div class="d-flex gap-2">
                    @if($location->google_maps_url)
                        <a href="{{ $location->google_maps_url }}" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-map-marked-alt me-2"></i>
                            Open in Maps
                        </a>
                    @endif
                    <a href="{{ route('admin.locations.edit', $location->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>
                        Edit Location
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.badge-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 15px;
    color: white;
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.bg-light pre {
    background: transparent !important;
    color: #6c757d;
    font-size: 0.8rem;
    max-height: 200px;
    overflow-y: auto;
}

.font-monospace {
    font-family: 'Courier New', monospace;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
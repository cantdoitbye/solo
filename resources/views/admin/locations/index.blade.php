@extends('admin.layouts.app')

@section('title', 'Suggested Locations - Admin Panel')
@section('page-title', 'Suggested Locations')

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.locations.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add Location
        </a>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.locations.index') }}" 
               class="btn {{ !request('status') ? 'btn-success' : 'btn-outline-success' }}">
                All
            </a>
            <a href="{{ route('admin.locations.index', ['status' => 'active']) }}" 
               class="btn {{ request('status') === 'active' ? 'btn-success' : 'btn-outline-success' }}">
                Active
            </a>
            <a href="{{ route('admin.locations.index', ['status' => 'inactive']) }}" 
               class="btn {{ request('status') === 'inactive' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                Inactive
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
                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>All Locations
                </h5>
            </div>
            <div class="col-md-6">
                <form method="GET" action="{{ route('admin.locations.index') }}" class="d-flex gap-2">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="category" value="{{ request('category') }}">
                    
                    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                {{ ucfirst($category) }}
                            </option>
                        @endforeach
                    </select>
                    
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           placeholder="Search locations..."
                           value="{{ request('search') }}">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    @if(request('search') || request('category'))
                        <a href="{{ route('admin.locations.index', ['status' => request('status')]) }}" 
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">Location</th>
                        <th class="py-3">Venue Details</th>
                        <th class="py-3">Location</th>
                        <th class="py-3">Category</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Order</th>
                        <th class="py-3">Images</th>
                        <th class="py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $location)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    @if($location->primaryImage)
                                        <img src="{{ asset('/' . $location->primaryImage->image_path) }}" 
                                             alt="{{ $location->name }}"
                                             class="rounded" 
                                             style="width: 60px; height: 45px; object-fit: cover;">
                                    @else
                                        <div class="bg-primary rounded d-flex align-items-center justify-content-center text-white fw-bold" 
                                             style="width: 60px; height: 45px;">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $location->name }}</div>
                                    <small class="text-muted">ID: #{{ $location->id }}</small>
                                    @if($location->description)
                                        <div><small class="text-muted">{{ Str::limit($location->description, 60) }}</small></div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="fw-semibold">{{ $location->venue_name }}</div>
                            <small class="text-muted">{{ Str::limit($location->venue_address, 50) }}</small>
                            @if($location->google_place_id)
                                <br><small class="text-info"><i class="fab fa-google me-1"></i>Google Places</small>
                            @endif
                        </td>
                        <td class="py-3">
                            <div>{{ $location->city }}</div>
                            @if($location->state)
                                <small class="text-muted">{{ $location->state }}, </small>
                            @endif
                            <small class="text-muted">{{ $location->country }}</small>
                            @if($location->latitude && $location->longitude)
                                <br><small class="text-muted font-monospace">
                                    {{ number_format($location->latitude, 4) }}, {{ number_format($location->longitude, 4) }}
                                </small>
                            @endif
                        </td>
                        <td class="py-3">
                            @if($location->category)
                                <span class="badge bg-info text-white">{{ ucfirst($location->category) }}</span>
                            @else
                                <span class="text-muted">No category</span>
                            @endif
                        </td>
                        <td class="py-3">
                            <span class="status-badge 
                                @if($location->is_active) bg-success text-white
                                @else bg-secondary text-white @endif">
                                {{ $location->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-primary text-white">{{ $location->sort_order }}</span>
                        </td>
                        <td class="py-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-images me-1 text-muted"></i>
                                <span class="fw-semibold">{{ $location->images->count() }}</span>
                                @if($location->images->count() > 0)
                                    <span class="text-muted ms-1">images</span>
                                @else
                                    <span class="text-muted ms-1">no images</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.locations.show', $location->id) }}" 
                                   class="btn btn-outline-primary" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                {{-- <a href="{{ route('admin.locations.edit', $location->id) }}" 
                                   class="btn btn-outline-warning" title="Edit Location">
                                    <i class="fas fa-edit"></i>
                                </a> --}}
                                @if($location->google_maps_url)
                                    <a href="{{ $location->google_maps_url }}" 
                                       target="_blank"
                                       class="btn btn-outline-info" title="View on Google Maps">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('admin.locations.destroy', $location->id) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="btn btn-outline-danger"
                                            title="Delete Location"
                                            onclick="return confirmDelete('Are you sure you want to delete this location? This will also delete all associated images.')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                            <div class="text-muted mb-3">
                                @if(request('search'))
                                    No locations found for "{{ request('search') }}"
                                @elseif(request('category'))
                                    No locations found in "{{ ucfirst(request('category')) }}" category
                                @else
                                    No locations found
                                @endif
                            </div>
                            @if(!request('search') && !request('category'))
                                <a href="{{ route('admin.locations.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add Your First Location
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($locations->hasPages())
    <div class="card-footer bg-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Showing {{ $locations->firstItem() }} to {{ $locations->lastItem() }} of {{ $locations->total() }} results
            </div>
            {{ $locations->appends(request()->query())->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Quick Stats -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="mb-1">{{ $locations->where('is_active', true)->count() }}</h3>
                <p class="mb-0">Active Locations</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h3 class="mb-1">{{ $locations->where('is_active', false)->count() }}</h3>
                <p class="mb-0">Inactive Locations</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3 class="mb-1">{{ $categories->count() }}</h3>
                <p class="mb-0">Categories</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3 class="mb-1">{{ $locations->sum(function($location) { return $location->images->count(); }) }}</h3>
                <p class="mb-0">Total Images</p>
            </div>
        </div>
    </div>
</div>

<!-- Categories Overview -->
@if($categories->count() > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-tags me-2 text-info"></i>Categories Overview
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($categories as $category)
                        @php
                            $categoryCount = $locations->where('category', $category)->count();
                        @endphp
                        <div class="col-md-4 mb-2">
                            <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                                <div>
                                    <span class="fw-semibold">{{ ucfirst($category) }}</span>
                                </div>
                                <div>
                                    <span class="badge bg-primary">{{ $categoryCount }}</span>
                                    <a href="{{ route('admin.locations.index', ['category' => $category]) }}" 
                                       class="btn btn-sm btn-outline-primary ms-2">
                                        View
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
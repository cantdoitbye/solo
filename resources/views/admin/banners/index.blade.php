@extends('admin.layouts.app')

@section('title', 'Banners Management - Admin Panel')
@section('page-title', 'Banners Management')

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add Banner
        </a>
        <a href="{{ route('admin.banners.index', ['status' => 'active']) }}" 
           class="btn btn-sm {{ request('status') === 'active' ? 'btn-success' : 'btn-outline-success' }}">
            Active
        </a>
        <a href="{{ route('admin.banners.index', ['status' => 'inactive']) }}" 
           class="btn btn-sm {{ request('status') === 'inactive' ? 'btn-secondary' : 'btn-outline-secondary' }}">
            Inactive
        </a>
    </div>
@endsection

@section('content')
<div class="card">
    <div class="card-header bg-white border-0">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0 fw-semibold">
                    <i class="fas fa-images me-2 text-primary"></i>All Banners
                </h5>
            </div>
            <div class="col-md-6">
                <form method="GET" action="{{ route('admin.banners.index') }}" class="d-flex gap-2">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           placeholder="Search banners..."
                           value="{{ request('search') }}">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.banners.index', ['status' => request('status')]) }}" 
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
                        <th class="px-4 py-3">Banner</th>
                        <th class="py-3">Title</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Schedule</th>
                        <th class="py-3">Order</th>
                        <th class="py-3">Created</th>
                        <th class="py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banners as $banner)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <img src="{{ asset('/' . $banner->image_path) }}" 
                                         alt="{{ $banner->title }}"
                                         class="rounded" 
                                         style="width: 60px; height: 40px; object-fit: cover;">
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $banner->title }}</div>
                                    <small class="text-muted">ID: #{{ $banner->id }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="fw-semibold">{{ $banner->title }}</div>
                            @if($banner->description)
                                <small class="text-muted">{{ Str::limit($banner->description, 50) }}</small>
                            @endif
                        </td>
                        <td class="py-3">
                            <div class="d-flex flex-column">
                                <span class="status-badge mb-1
                                    @if($banner->is_active) bg-success text-white
                                    @else bg-secondary text-white @endif">
                                    {{ $banner->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @if($banner->start_date || $banner->end_date)
                                    <small class="text-muted">
                                        @if($banner->start_date && $banner->end_date)
                                            Scheduled
                                        @elseif($banner->start_date)
                                            Starts {{ $banner->start_date->format('M d') }}
                                        @elseif($banner->end_date)
                                            Ends {{ $banner->end_date->format('M d') }}
                                        @endif
                                    </small>
                                @endif
                            </div>
                        </td>
                        <td class="py-3">
                            @if($banner->start_date || $banner->end_date)
                                <div class="small">
                                    @if($banner->start_date)
                                        <div>Start: {{ $banner->start_date->format('M d, Y') }}</div>
                                    @endif
                                    @if($banner->end_date)
                                        <div>End: {{ $banner->end_date->format('M d, Y') }}</div>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">No schedule</span>
                            @endif
                        </td>
                        <td class="py-3">
                            <span class="badge bg-info text-white">{{ $banner->sort_order }}</span>
                        </td>
                        <td class="py-3">
                            <div>{{ $banner->created_at->format('M d, Y') }}</div>
                            <small class="text-muted">{{ $banner->created_at->diffForHumans() }}</small>
                        </td>
                        <td class="py-3 text-center">
                            <div class="btn-group btn-group-sm">
                             
                                <form method="POST" action="{{ route('admin.banners.toggle-status', $banner->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" 
                                            class="btn btn-outline-{{ $banner->is_active ? 'secondary' : 'success' }}"
                                            title="{{ $banner->is_active ? 'Deactivate' : 'Activate' }} Banner">
                                        <i class="fas fa-{{ $banner->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.banners.destroy', $banner->id) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="btn btn-outline-danger"
                                            title="Delete Banner"
                                            onclick="return confirmDelete('Are you sure you want to delete this banner?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fas fa-images fa-3x text-muted mb-3"></i>
                            <div class="text-muted">
                                @if(request('search'))
                                    No banners found for "{{ request('search') }}"
                                @else
                                    No banners found
                                @endif
                            </div>
                            @if(!request('search'))
                                <a href="{{ route('admin.banners.create') }}" class="btn btn-primary mt-3">
                                    <i class="fas fa-plus me-2"></i>Add Your First Banner
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($banners->hasPages())
    <div class="card-footer bg-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Showing {{ $banners->firstItem() }} to {{ $banners->lastItem() }} of {{ $banners->total() }} results
            </div>
            {{ $banners->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Quick Stats -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="mb-1">{{ $banners->where('is_active', true)->count() }}</h3>
                <p class="mb-0">Active Banners</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h3 class="mb-1">{{ $banners->where('is_active', false)->count() }}</h3>
                <p class="mb-0">Inactive Banners</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3 class="mb-1">{{ $banners->whereNotNull('start_date')->count() }}</h3>
                <p class="mb-0">Scheduled</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="mb-1">{{ $banners->count() }}</h3>
                <p class="mb-0">Total Banners</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto-refresh every 5 minutes to update schedule status
    setTimeout(function() {
        if (!document.hidden) {
            location.reload();
        }
    }, 300000);
</script>
@endsection
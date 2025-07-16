@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Overview of your tea trading operations')

@section('content')
<!-- Key Performance Indicators -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card bg-gradient-primary text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="mb-0">{{ $sellerStats['total'] }}</h3>
                    <p class="mb-0">Total Sellers</p>
                    <small class="opacity-75">{{ $sellerStats['active'] }} Active</small>
                </div>
                <div class="col-auto">
                    <div class="stats-icon bg-white bg-opacity-25">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card bg-gradient-success text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="mb-0">{{ $buyerStats['total'] }}</h3>
                    <p class="mb-0">Total Buyers</p>
                    <small class="opacity-75">{{ $buyerStats['big_buyers'] }} Big, {{ $buyerStats['small_buyers'] }} Small</small>
                </div>
                <div class="col-auto">
                    <div class="stats-icon bg-white bg-opacity-25">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card bg-gradient-info text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="mb-0">{{ $courierStats['total'] }}</h3>
                    <p class="mb-0">Courier Services</p>
                    <small class="opacity-75">{{ $courierStats['with_api'] }} API Integrated</small>
                </div>
                <div class="col-auto">
                    <div class="stats-icon bg-white bg-opacity-25">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card bg-gradient-warning text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="mb-0">{{ $activePercentage }}%</h3>
                    <p class="mb-0">Active Entities</p>
                    <small class="opacity-75">System Health</small>
                </div>
                <div class="col-auto">
                    <div class="stats-icon bg-white bg-opacity-25">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.sellers.create') }}" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="fas fa-plus-circle fa-2x mb-2"></i>
                            <span>Add New Seller</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.buyers.create') }}" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="fas fa-user-plus fa-2x mb-2"></i>
                            <span>Add New Buyer</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('admin.couriers.create') }}" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="fas fa-truck fa-2x mb-2"></i>
                            <span>Add Courier Service</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="#" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="fas fa-flask fa-2x mb-2"></i>
                            <span>Sample Management</span>
                            <small class="text-muted">Coming Soon</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities and Charts -->
<div class="row">
    <!-- Recent Sellers -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-store me-2"></i>Recent Sellers</h6>
                <a href="{{ route('admin.sellers.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                @if($recentSellers->count() > 0)
                    @foreach($recentSellers as $seller)
                        <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <div class="fw-bold">{{ $seller->seller_name }}</div>
                                <small class="text-muted">{{ $seller->tea_estate_name }}</small>
                            </div>
                            <div class="text-end">
                                <span class="status-badge {{ $seller->status ? 'status-active' : 'status-inactive' }}">
                                    {{ $seller->status_text }}
                                </span>
                                <br>
                                <small class="text-muted">{{ $seller->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-3">
                        <i class="fas fa-store fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No sellers added yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Buyers -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-users me-2"></i>Recent Buyers</h6>
                <a href="{{ route('admin.buyers.index') }}" class="btn btn-sm btn-outline-success">View All</a>
            </div>
            <div class="card-body">
                @if($recentBuyers->count() > 0)
                    @foreach($recentBuyers as $buyer)
                        <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <div class="fw-bold">{{ $buyer->buyer_name }}</div>
                                <small class="text-muted">{{ $buyer->buyer_type_text }} - {{ $buyer->billing_city }}</small>
                            </div>
                            <div class="text-end">
                                <span class="status-badge {{ $buyer->status ? 'status-active' : 'status-inactive' }}">
                                    {{ $buyer->status_text }}
                                </span>
                                <br>
                                <small class="text-muted">{{ $buyer->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-3">
                        <i class="fas fa-users fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No buyers added yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Tea Grades Distribution Chart -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Tea Grades Distribution</h6>
            </div>
            <div class="card-body">
                <canvas id="teaGradesChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-server me-2"></i>System Status</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Sellers Active</span>
                            <span class="badge bg-success">{{ $sellerStats['active'] }}/{{ $sellerStats['total'] }}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Buyers Active</span>
                            <span class="badge bg-success">{{ $buyerStats['active'] }}/{{ $buyerStats['total'] }}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Couriers Active</span>
                            <span class="badge bg-success">{{ $courierStats['active'] }}/{{ $courierStats['total'] }}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>API Integrations</span>
                            <span class="badge bg-info">{{ $courierStats['with_api'] }}</span>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="alert alert-info mb-0">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Next Steps:</strong><br>
                        Complete Module 1 setup by adding logistic companies and contract management.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Features -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-road me-2"></i>Coming Soon</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <div class="text-center p-3 border rounded">
                            <i class="fas fa-truck fa-2x text-muted mb-2"></i>
                            <h6>Logistic Companies</h6>
                            <small class="text-muted">Module 1.4</small>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="text-center p-3 border rounded">
                            <i class="fas fa-file-contract fa-2x text-muted mb-2"></i>
                            <h6>Contract Management</h6>
                            <small class="text-muted">Module 1.5</small>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="text-center p-3 border rounded">
                            <i class="fas fa-flask fa-2x text-muted mb-2"></i>
                            <h6>Sample Management</h6>
                            <small class="text-muted">Module 2</small>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="text-center p-3 border rounded">
                            <i class="fas fa-chart-bar fa-2x text-muted mb-2"></i>
                            <h6>Reports & Analytics</h6>
                            <small class="text-muted">Module 5</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
}

.stats-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Tea Grades Distribution Chart
    const ctx = document.getElementById('teaGradesChart').getContext('2d');
    const teaGradesData = @json($teaGradesData);
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(teaGradesData),
            datasets: [{
                data: Object.values(teaGradesData),
                backgroundColor: [
                    '#2c5530',
                    '#4a7c59',
                    '#8fbc8f',
                    '#28a745',
                    '#17a2b8',
                    '#ffc107'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Auto-refresh stats every 30 seconds
    setInterval(function() {
        refreshStats();
    }, 30000);
});

function refreshStats() {
    $.ajax({
        url: '{{ route("admin.dashboard") }}',
        method: 'GET',
        data: { ajax: true },
        success: function(response) {
            // Update stats if needed
            console.log('Stats refreshed');
        },
        error: function() {
            console.log('Error refreshing stats');
        }
    });
}
</script>
@endpush
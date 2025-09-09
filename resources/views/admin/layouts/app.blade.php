<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.js"></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Maps API - Will be loaded conditionally -->
    @if(isset($loadGoogleMaps) && $loadGoogleMaps)
        @if(config('services.google_maps.api_key'))
            <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places"></script>
        @else
            <script>
                console.warn('Google Maps API key not configured. Please add GOOGLE_MAPS_API_KEY to your .env file');
                // Fallback for when API key is not available
                window.google = {
                    maps: {
                        Map: function() { return { addListener: function() {}, setCenter: function() {} }; },
                        Marker: function() { return { setPosition: function() {}, addListener: function() {} }; },
                        places: {
                            Autocomplete: function() { return { addListener: function() {} }; }
                        }
                    }
                };
            </script>
        @endif
    @endif
    
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        .sidebar.collapsed {
            margin-left: -250px;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            transition: all 0.3s ease;
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
            border-radius: 8px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .main-content {
            background-color: #f8fafc;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        .main-content.expanded {
            margin-left: -250px;
        }
        .admin-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            z-index: 999;
        }
        .card {
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        .alert {
            border: none;
            border-radius: 12px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            transition: border-color 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .status-badge {
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .map-container {
            height: 300px;
            border-radius: 12px;
            overflow: hidden;
        }
        .sidebar-toggle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sidebar-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        /* Custom Pagination Styles */
        .pagination-sm .page-link {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            color: #6c757d;
            margin: 0 2px;
            transition: all 0.2s ease;
        }

        .pagination-sm .page-link:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
            color: #495057;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .pagination-sm .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
        }

        .pagination-sm .page-item.disabled .page-link {
            color: #adb5bd;
            background-color: #fff;
            border-color: #dee2e6;
            cursor: not-allowed;
        }

        .pagination {
            gap: 0;
        }

        .page-item {
            margin: 0 1px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                z-index: 1050;
                transition: left 0.3s ease;
            }
            .sidebar.show {
                left: 0;
            }
            .main-content {
                margin-left: 0 !important;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1040;
            }
            .sidebar-overlay.show {
                display: block;
            }
            
            /* Mobile header adjustments */
            .admin-header .navbar {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            
            .page-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .page-actions .btn {
                font-size: 0.875rem;
                padding: 0.375rem 0.75rem;
            }
            
            /* Hide user details on very small screens */
            @media (max-width: 576px) {
                .page-actions .btn-group .btn {
                    padding: 0.25rem 0.5rem;
                    font-size: 0.8rem;
                }
                
                .page-actions .btn .d-none.d-md-inline {
                    display: none !important;
                }
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar col-md-3 col-lg-2 d-md-block" id="sidebar">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <h4 class="text-white fw-bold">
                        <i class="fas fa-cogs me-2"></i>Admin Panel
                    </h4>
                    <small class="text-white-50">Welcome, {{ auth('admin')->user()->name }}</small>
                </div>
                
                <ul class="nav flex-column px-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                           href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" 
                           href="{{ route('admin.users.index') }}">
                            <i class="fas fa-users me-2"></i>Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}" 
                           href="{{ route('admin.locations.index') }}">
                            <i class="fas fa-map-marker-alt me-2"></i>Locations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}" 
                           href="{{ route('admin.banners.index') }}">
                            <i class="fas fa-images me-2"></i>Banners
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}" 
                           href="{{ route('admin.events.index') }}">
                            <i class="fas fa-calendar-check me-2"></i>Events
                        </a>
                    </li>

                     <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.feedbacks.*') ? 'active' : '' }}" 
                           href="{{ route('admin.feedbacks.index') }}">
                            <i class="fas fa-calendar-check me-2"></i>Feedbacks
                        </a>
                    </li>
                 
                    <li class="nav-item mt-4">
                        <form action="{{ route('admin.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <div class="flex-grow-1 main-content" id="mainContent">
            <!-- Header Navbar -->
            <header class="admin-header sticky-top">
                <nav class="navbar navbar-expand-lg navbar-light bg-white px-3 py-3">
                    <div class="d-flex align-items-center">
                        <button class="sidebar-toggle me-3 d-md-none" id="sidebarToggle" type="button">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1 class="h3 mb-0 fw-bold text-gray-800">@yield('page-title', 'Dashboard')</h1>
                    </div>
                    
                    <div class="navbar-nav ms-auto">
                      
                        
                        <!-- Page Actions -->
                        <div class="ms-3">
                            @yield('page-actions')
                        </div>
                    </div>
                </nav>
            </header>

            <main class="px-3 py-4">
                <!-- Alert Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Page Content -->
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            // Check if we're on mobile
            function isMobile() {
                return window.innerWidth < 768;
            }
            
            // Toggle sidebar
            function toggleSidebar() {
                if (isMobile()) {
                    // Mobile behavior
                    sidebar.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                }
            }
            
            // Close sidebar on mobile when clicking overlay
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                });
            }
            
            // Sidebar toggle button
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (!isMobile()) {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                }
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                });
            }, 5000);
        });

        // Confirm delete actions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }

        // Get CSRF token for AJAX requests
        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        }
    </script>
    
    @yield('scripts')
</body>
</html>
@extends('admin.layouts.app')

@section('title', 'Google Maps Test - Admin Panel')
@section('page-title', 'Google Maps API Test')

@php
    $loadGoogleMaps = true;
@endphp

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Google Maps Configuration Test</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Configuration Status</h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>API Key Configured:</span>
                                <span class="badge bg-{{ config('services.google_maps.api_key') ? 'success' : 'danger' }}">
                                    {{ config('services.google_maps.api_key') ? 'Yes' : 'No' }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Environment Variable:</span>
                                <span class="badge bg-{{ env('GOOGLE_MAPS_API_KEY') ? 'success' : 'danger' }}">
                                    {{ env('GOOGLE_MAPS_API_KEY') ? 'Set' : 'Not Set' }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Key Length:</span>
                                <span class="badge bg-info">
                                    {{ strlen(config('services.google_maps.api_key') ?? '') }} chars
                                </span>
                            </li>
                            <li class="list-group-item">
                                <span>Key Preview:</span>
                                <code class="ms-2">{{ substr(config('services.google_maps.api_key') ?? 'NOT_SET', 0, 20) }}...</code>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Troubleshooting Steps</h6>
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item">Check your .env file for GOOGLE_MAPS_API_KEY</li>
                            <li class="list-group-item">Clear config cache: <code>php artisan config:clear</code></li>
                            <li class="list-group-item">Verify API key in Google Cloud Console</li>
                            <li class="list-group-item">Enable required APIs (Maps JavaScript API, Places API)</li>
                            <li class="list-group-item">Check API key restrictions</li>
                            <li class="list-group-item">Verify billing is enabled</li>
                        </ol>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Testing Google Maps Loading</h6>
                    <p class="mb-0">The map below will show if Google Maps is working correctly:</p>
                </div>
                
                <!-- Test Map -->
                <div id="test-map" style="height: 400px; border: 2px solid #dee2e6; border-radius: 8px;"></div>
                
                <div class="mt-3">
                    <div id="map-status" class="alert alert-secondary">
                        <i class="fas fa-spinner fa-spin me-2"></i>Loading Google Maps...
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">API Key Requirements</h6>
            </div>
            <div class="card-body">
                <h6>Format:</h6>
                <p><code>AIza[35 characters]</code></p>
                
                <h6>Required APIs:</h6>
                <ul>
                    <li>Maps JavaScript API</li>
                    <li>Places API</li>
                    <li>Geocoding API (optional)</li>
                </ul>
                
                <h6>Common Issues:</h6>
                <ul>
                    <li>API key not enabled for required APIs</li>
                    <li>Domain restrictions blocking localhost</li>
                    <li>Billing not enabled</li>
                    <li>Daily quota exceeded</li>
                    <li>Config cache not cleared</li>
                </ul>
                
                <div class="mt-3">
                    <a href="https://console.cloud.google.com/google/maps-apis" 
                       target="_blank" 
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>Google Cloud Console
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Quick Commands</h6>
            </div>
            <div class="card-body">
                <p><strong>Clear Laravel cache:</strong></p>
                <code>php artisan config:clear</code><br>
                <code>php artisan cache:clear</code><br>
                <code>php artisan route:clear</code>
                
                <p class="mt-3"><strong>Check environment:</strong></p>
                <code>php artisan tinker</code><br>
                <small class="text-muted">Then run: config('services.google_maps.api_key')</small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let testMap;
    const statusDiv = document.getElementById('map-status');
    
    function initTestMap() {
        try {
            // Update status
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Initializing Google Maps...';
            statusDiv.className = 'alert alert-info';
            
            // Check if Google Maps is available
            if (typeof google === 'undefined' || !google.maps) {
                throw new Error('Google Maps API not loaded');
            }
            
            // Create map
            testMap = new google.maps.Map(document.getElementById('test-map'), {
                center: { lat: 40.7128, lng: -74.0060 }, // New York City
                zoom: 13,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true
            });
            
            // Add a marker
            const marker = new google.maps.Marker({
                position: { lat: 40.7128, lng: -74.0060 },
                map: testMap,
                title: 'Test Location - NYC',
                draggable: true
            });
            
            // Add info window
            const infoWindow = new google.maps.InfoWindow({
                content: '<div><h6>Google Maps Test</h6><p>If you can see this, Google Maps is working!</p></div>'
            });
            
            marker.addListener('click', () => {
                infoWindow.open(testMap, marker);
            });
            
            // Test Places API if available
            if (google.maps.places) {
                const service = new google.maps.places.PlacesService(testMap);
                console.log('Google Places API is available');
                
                statusDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Google Maps loaded successfully! Places API is also available.';
                statusDiv.className = 'alert alert-success';
            } else {
                statusDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Google Maps loaded successfully! But Places API might not be enabled.';
                statusDiv.className = 'alert alert-warning';
            }
            
        } catch (error) {
            console.error('Google Maps Error:', error);
            statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>Error: ${error.message}`;
            statusDiv.className = 'alert alert-danger';
            
            // Show fallback message in map container
            document.getElementById('test-map').innerHTML = `
                <div class="d-flex align-items-center justify-content-center h-100 text-center p-4">
                    <div>
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>Google Maps Failed to Load</h5>
                        <p class="text-muted">Check your API key configuration</p>
                    </div>
                </div>
            `;
        }
    }
    
    // Test API key format
    function testApiKeyFormat() {
        @if(config('services.google_maps.api_key'))
            const apiKey = '{{ config('services.google_maps.api_key') }}';
            const isValidFormat = /^AIza[0-9A-Za-z_-]{35}$/.test(apiKey);
            
            if (!isValidFormat) {
                console.warn('API key format appears to be invalid. Should start with "AIza" and be 39 characters total.');
            } else {
                console.log('API key format looks correct.');
            }
        @else
            console.error('No API key configured');
        @endif
    }
    
    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        testApiKeyFormat();
        
        // Give a moment for Google Maps to load
        setTimeout(() => {
            initTestMap();
        }, 1000);
    });
    
    // Global error handler for Google Maps
    window.gm_authFailure = function() {
        statusDiv.innerHTML = '<i class="fas fa-times-circle me-2"></i>Google Maps Authentication Failed! Check your API key.';
        statusDiv.className = 'alert alert-danger';
    };
</script>
@endsection
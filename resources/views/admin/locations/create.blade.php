@extends('admin.layouts.app')

@section('title', 'Add Suggested Location - Admin Panel')
@section('page-title', 'Add Suggested Location')
@php
    $loadGoogleMaps = true;
@endphp
@section('page-actions')
    <a href="{{ route('admin.locations.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Locations
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.locations.store') }}" enctype="multipart/form-data">
    @csrf
    
    <div class="row g-4">
        <!-- Basic Information -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Basic Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Location Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   placeholder="e.g., Central Park Adventure"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" 
                                   class="form-control @error('category') is-invalid @enderror" 
                                   id="category" 
                                   name="category" 
                                   value="{{ old('category') }}" 
                                   placeholder="e.g., Parks, Restaurants, Museums">
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Describe this location...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Location Details -->
            <div class="card mt-4">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-map-marker-alt me-2 text-success"></i>Location Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="venue_name" class="form-label">Venue Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('venue_name') is-invalid @enderror" 
                                   id="venue_name" 
                                   name="venue_name" 
                                   value="{{ old('venue_name') }}" 
                                   placeholder="e.g., Starbucks Coffee"
                                   required>
                            @error('venue_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="google_place_id" class="form-label">Google Place ID</label>
                            <input type="text" 
                                   class="form-control @error('google_place_id') is-invalid @enderror" 
                                   id="google_place_id" 
                                   name="google_place_id" 
                                   value="{{ old('google_place_id') }}" 
                                   placeholder="ChIJN1t_tDeuEmsRUsoyG83frY4">
                            @error('google_place_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label for="venue_address" class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('venue_address') is-invalid @enderror" 
                                   id="venue_address" 
                                   name="venue_address" 
                                   value="{{ old('venue_address') }}" 
                                   placeholder="123 Main Street, City, State"
                                   required>
                            @error('venue_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3">
                            <label for="latitude" class="form-label">Latitude <span class="text-danger">*</span></label>
                            <input type="number" 
                                   step="any"
                                   class="form-control @error('latitude') is-invalid @enderror" 
                                   id="latitude" 
                                   name="latitude" 
                                   value="{{ old('latitude') }}" 
                                   placeholder="40.7128"
                                   required>
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3">
                            <label for="longitude" class="form-label">Longitude <span class="text-danger">*</span></label>
                            <input type="number" 
                                   step="any"
                                   class="form-control @error('longitude') is-invalid @enderror" 
                                   id="longitude" 
                                   name="longitude" 
                                   value="{{ old('longitude') }}" 
                                   placeholder="-74.0060"
                                   required>
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3">
                            <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('city') is-invalid @enderror" 
                                   id="city" 
                                   name="city" 
                                   value="{{ old('city') }}" 
                                   placeholder="New York"
                                   required>
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3">
                            <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('country') is-invalid @enderror" 
                                   id="country" 
                                   name="country" 
                                   value="{{ old('country') }}" 
                                   placeholder="United States"
                                   required>
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="state" class="form-label">State/Province</label>
                            <input type="text" 
                                   class="form-control @error('state') is-invalid @enderror" 
                                   id="state" 
                                   name="state" 
                                   value="{{ old('state') }}" 
                                   placeholder="New York">
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" 
                                   class="form-control @error('postal_code') is-invalid @enderror" 
                                   id="postal_code" 
                                   name="postal_code" 
                                   value="{{ old('postal_code') }}" 
                                   placeholder="10001">
                            @error('postal_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label for="google_maps_url" class="form-label">Google Maps URL</label>
                            <input type="url" 
                                   class="form-control @error('google_maps_url') is-invalid @enderror" 
                                   id="google_maps_url" 
                                   name="google_maps_url" 
                                   value="{{ old('google_maps_url') }}" 
                                   placeholder="https://maps.google.com/...">
                            @error('google_maps_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Google Map -->
                    <div class="mt-4">
                        <label class="form-label">Location on Map</label>
                        <div id="map" class="map-container border rounded"></div>
                        <small class="text-muted">Click on the map to set the exact location</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Settings & Images -->
        <div class="col-lg-4">
            <!-- Settings -->
            <div class="card">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-cogs me-2 text-info"></i>Settings
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" 
                               class="form-control @error('sort_order') is-invalid @enderror" 
                               id="sort_order" 
                               name="sort_order" 
                               value="{{ old('sort_order', 0) }}" 
                               min="0">
                        <small class="text-muted">Lower numbers appear first</small>
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active Status
                            </label>
                        </div>
                        <small class="text-muted">Only active locations will be visible to users</small>
                    </div>
                </div>
            </div>
            
            <!-- Images -->
            <div class="card mt-4">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-images me-2 text-warning"></i>Images
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="images" class="form-label">Upload Images</label>
                        <input type="file" 
                               class="form-control @error('images.*') is-invalid @enderror" 
                               id="images" 
                               name="images[]" 
                               multiple 
                               accept="image/*">
                        <small class="text-muted">
                            You can upload multiple images. Max size: 5MB per image.<br>
                            Supported formats: JPEG, PNG, JPG, GIF, WebP
                        </small>
                        @error('images.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <!-- Image Preview -->
                    <div id="imagePreview" class="row g-2"></div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="card mt-4">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Location
                        </button>
                        <a href="{{ route('admin.locations.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    let map, marker;
    
    function initMap() {
        // Check if Google Maps is available
        if (typeof google === 'undefined' || !google.maps) {
            console.error('Google Maps API not loaded. Please check your API key configuration.');
            document.getElementById('map').innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Google Maps API not available. Please configure your API key in the .env file.</div>';
            return;
        }
        
        // Default location (New York City)
        const defaultLat = {{ old('latitude', 40.7128) }};
        const defaultLng = {{ old('longitude', -74.0060) }};
        
        try {
            map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: defaultLat, lng: defaultLng },
                zoom: 13
            });
            
            marker = new google.maps.Marker({
                position: { lat: defaultLat, lng: defaultLng },
                map: map,
                draggable: true
            });
            
            // Click event to place marker
            map.addListener('click', function(event) {
                placeMarker(event.latLng);
            });
            
            // Drag event for marker
            marker.addListener('dragend', function(event) {
                updateCoordinates(event.latLng.lat(), event.latLng.lng());
            });
            
            // Initialize autocomplete if available
            if (google.maps.places) {
                initAutocomplete();
            }
        } catch (error) {
            console.error('Error initializing Google Maps:', error);
            document.getElementById('map').innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Error loading Google Maps. Please check your API key and billing settings.</div>';
        }
    }
    
    function placeMarker(location) {
        if (marker) {
            marker.setPosition(location);
            updateCoordinates(location.lat(), location.lng());
        }
    }
    
    function updateCoordinates(lat, lng) {
        document.getElementById('latitude').value = lat.toFixed(8);
        document.getElementById('longitude').value = lng.toFixed(8);
    }
    
    // Address autocomplete
    function initAutocomplete() {
        const addressInput = document.getElementById('venue_address');
        
        try {
            const autocomplete = new google.maps.places.Autocomplete(addressInput);
            
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                
                if (place.geometry) {
                    const lat = place.geometry.location.lat();
                    const lng = place.geometry.location.lng();
                    
                    // Update map
                    if (map && marker) {
                        map.setCenter({ lat: lat, lng: lng });
                        marker.setPosition({ lat: lat, lng: lng });
                        
                        // Update coordinates
                        updateCoordinates(lat, lng);
                    }
                    
                    // Update place ID
                    if (place.place_id) {
                        document.getElementById('google_place_id').value = place.place_id;
                    }
                    
                    // Update address components
                    const components = place.address_components;
                    if (components) {
                        components.forEach(component => {
                            const types = component.types;
                            
                            if (types.includes('locality')) {
                                document.getElementById('city').value = component.long_name;
                            }
                            if (types.includes('administrative_area_level_1')) {
                                document.getElementById('state').value = component.long_name;
                            }
                            if (types.includes('country')) {
                                document.getElementById('country').value = component.long_name;
                            }
                            if (types.includes('postal_code')) {
                                document.getElementById('postal_code').value = component.long_name;
                            }
                        });
                    }
                }
            });
        } catch (error) {
            console.error('Error initializing autocomplete:', error);
        }
    }
    
    // Image preview
    document.getElementById('images').addEventListener('change', function(event) {
        const files = event.target.files;
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        Array.from(files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-6';
                    col.innerHTML = `
                        <div class="card">
                            <img src="${e.target.result}" class="card-img-top" style="height: 100px; object-fit: cover;">
                            <div class="card-body p-2">
                                <small class="text-muted">${file.name}</small>
                            </div>
                        </div>
                    `;
                    preview.appendChild(col);
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Add fallback coordinates update for manual entry
        document.getElementById('latitude').addEventListener('input', function() {
            if (map && marker && this.value && document.getElementById('longitude').value) {
                const lat = parseFloat(this.value);
                const lng = parseFloat(document.getElementById('longitude').value);
                if (!isNaN(lat) && !isNaN(lng)) {
                    const position = { lat: lat, lng: lng };
                    map.setCenter(position);
                    marker.setPosition(position);
                }
            }
        });
        
        document.getElementById('longitude').addEventListener('input', function() {
            if (map && marker && this.value && document.getElementById('latitude').value) {
                const lat = parseFloat(document.getElementById('latitude').value);
                const lng = parseFloat(this.value);
                if (!isNaN(lat) && !isNaN(lng)) {
                    const position = { lat: lat, lng: lng };
                    map.setCenter(position);
                    marker.setPosition(position);
                }
            }
        });
        
        // Initialize Google Maps with delay to ensure DOM is ready
        setTimeout(function() {
            initMap();
        }, 100);
    });
</script>
@endsection
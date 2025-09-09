{{-- resources/views/admin/banners/create.blade.php --}}

@extends('admin.layouts.app')

@section('title', 'Create Banner')
@section('subtitle', 'Add a new banner')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-image me-2"></i>Create New Banner</h5>
                <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to List
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Title Field -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Banner Title *</label>
                        <input type="text" 
                               class="form-control @error('title') is-invalid @enderror" 
                               id="title" 
                               name="title" 
                               value="{{ old('title') }}" 
                               placeholder="Enter banner title" 
                               required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Description Field -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3" 
                                  placeholder="Enter banner description">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Image Field -->
                    <div class="mb-3">
                        <label for="image" class="form-label">Banner Image *</label>
                        <input type="file" 
                               class="form-control @error('image') is-invalid @enderror" 
                               id="image" 
                               name="image" 
                               accept="image/*" 
                               required>
                        <div class="form-text">Supported formats: JPG, PNG, GIF. Max size: 2MB</div>
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Link URL Field -->
                    <div class="mb-4">
                        <label for="link_url" class="form-label">Link URL</label>
                        <input type="url" 
                               class="form-control @error('link_url') is-invalid @enderror" 
                               id="link_url" 
                               name="link_url" 
                               value="{{ old('link_url') }}" 
                               placeholder="https://example.com">
                        <div class="form-text">Optional: Add a link where users will be redirected when clicking the banner</div>
                        @error('link_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create Banner
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Basic form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const image = document.getElementById('image').files[0];

        if (!title) {
            e.preventDefault();
            alert('Please enter a banner title');
            return false;
        }

        if (!image) {
            e.preventDefault();
            alert('Please select an image');
            return false;
        }

        // Check image size (2MB limit)
        if (image.size > 2 * 1024 * 1024) {
            e.preventDefault();
            alert('Image size must be less than 2MB');
            return false;
        }
    });
</script>
@endpush
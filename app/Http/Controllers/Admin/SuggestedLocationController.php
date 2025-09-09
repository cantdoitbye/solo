<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SuggestedLocation;
use App\Models\LocationImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuggestedLocationController extends Controller
{
    public function index(Request $request)
    {
        $query = SuggestedLocation::with(['images', 'primaryImage']);
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('venue_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $locations = $query->orderBy('sort_order', 'asc')
                          ->orderBy('created_at', 'desc')
                          ->paginate(20);
        
        // Get unique categories for filter
        $categories = SuggestedLocation::distinct()->pluck('category')->filter()->sort();
        
        return view('admin.locations.index', compact('locations', 'categories'));
    }

    public function create()
    {
        return view('admin.locations.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'venue_name' => 'required|string|max:255',
            'venue_address' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'category' => 'nullable|string|max:255',
            'google_place_id' => 'nullable|string|max:255',
            'google_maps_url' => 'nullable|url',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        $location = SuggestedLocation::create([
            'name' => $request->name,
            'description' => $request->description,
            'venue_name' => $request->venue_name,
            'venue_address' => $request->venue_address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
            'category' => $request->category,
            'google_place_id' => $request->google_place_id,
            'google_maps_url' => $request->google_maps_url,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            $this->handleImageUploads($request->file('images'), $location);
        }

        return redirect()->route('admin.locations.index')
            ->with('success', 'Suggested location created successfully.');
    }

    public function show($id)
    {
        $location = SuggestedLocation::with(['images'])->findOrFail($id);
        
        // Get events using this location
        $events = \App\Models\Event::where('suggested_location_id', $id)
            ->with('host:id,name')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        return view('admin.locations.show', compact('location', 'events'));
    }

    public function edit($id)
    {
        $location = SuggestedLocation::with('images')->findOrFail($id);
        return view('admin.locations.edit', compact('location'));
    }

    public function update(Request $request, $id)
    {
        $location = SuggestedLocation::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'venue_name' => 'required|string|max:255',
            'venue_address' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'category' => 'nullable|string|max:255',
            'google_place_id' => 'nullable|string|max:255',
            'google_maps_url' => 'nullable|url',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $location->update([
            'name' => $request->name,
            'description' => $request->description,
            'venue_name' => $request->venue_name,
            'venue_address' => $request->venue_address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
            'category' => $request->category,
            'google_place_id' => $request->google_place_id,
            'google_maps_url' => $request->google_maps_url,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Handle new image uploads
        if ($request->hasFile('images')) {
            $this->handleImageUploads($request->file('images'), $location);
        }

        return redirect()->route('admin.locations.index')
            ->with('success', 'Suggested location updated successfully.');
    }

    public function destroy($id)
    {
        $location = SuggestedLocation::findOrFail($id);
        
        // Delete associated images
        foreach ($location->images as $image) {
            if (Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
        }
        
        $location->delete();
        
        return redirect()->route('admin.locations.index')
            ->with('success', 'Suggested location deleted successfully.');
    }

    public function deleteImage($locationId, $imageId)
    {
        $image = LocationImage::where('suggested_location_id', $locationId)
                               ->where('id', $imageId)
                               ->firstOrFail();
        
        // Delete file from storage
        if (Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }
        
        $image->delete();
        
        return response()->json(['success' => true]);
    }

    public function setPrimaryImage($locationId, $imageId)
    {
        $location = SuggestedLocation::findOrFail($locationId);
        
        // Remove primary flag from all images
        $location->images()->update(['is_primary' => false]);
        
        // Set new primary image
        $location->images()->where('id', $imageId)->update(['is_primary' => true]);
        
        return response()->json(['success' => true]);
    }

    private function handleImageUploads($images, $location)
    {
        $sortOrder = $location->images()->max('sort_order') ?? 0;
        $isPrimarySet = $location->images()->where('is_primary', true)->exists();
        
        foreach ($images as $index => $image) {
            // $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            // $path = $image->storeAs('location_images', $filename, 'public');
                  $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
  $fileSize = $image->getSize();
    $mimeType = $image->getMimeType();
        // Save to public/location_images
        $path = 'location_images/' . $filename;
        $image->move(public_path('location_images'), $filename);

            LocationImage::create([
                'suggested_location_id' => $location->id,
                'image_path' => $path,
                'image_url' => Storage::disk('public')->url($path),
                'original_filename' => $image->getClientOriginalName(),
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'width' => null, // You can add image dimensions if needed
                'height' => null,
                'is_primary' => !$isPrimarySet && $index === 0, // First image as primary if none set
                'sort_order' => ++$sortOrder,
            ]);
            
            $isPrimarySet = true; // Ensure only first image is set as primary
        }
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        $query = Banner::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $banners = $query->orderBy('sort_order', 'asc')
                        ->orderBy('created_at', 'desc')
                        ->paginate(20);
        
        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'link_url' => 'nullable|url',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Handle image upload
        // $image = $request->file('image');
        // $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
        // $path = $image->storeAs('banners', $filename, 'public');
   $uploadPath = public_path('banners');
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    // Handle image upload
    $image = $request->file('image');
    $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

    // Save to public/banners
    $path = 'banners/' . $filename;
    $image->move($uploadPath, $filename);
        Banner::create([
            'title' => $request->title,
            'description' => $request->description,
            'image_path' => $path,
            'image_url' => Storage::disk('public')->url($path),
            'link_url' => $request->link_url,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner created successfully.');
    }

    public function show($id)
    {
        $banner = Banner::findOrFail($id);
        return view('admin.banners.show', compact('banner'));
    }

    public function edit($id)
    {
        $banner = Banner::findOrFail($id);
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'link_url' => 'nullable|url',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $updateData = [
            'title' => $request->title,
            'description' => $request->description,
            'link_url' => $request->link_url,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        // Handle image upload if new image provided
        // if ($request->hasFile('image')) {
        //     // Delete old image
        //     if (Storage::disk('public')->exists($banner->image_path)) {
        //         Storage::disk('public')->delete($banner->image_path);
        //     }

        //     $image = $request->file('image');
        //     $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
        //     $path = $image->storeAs('banners', $filename, 'public');

        //     $updateData['image_path'] = $path;
        //     $updateData['image_url'] = Storage::disk('public')->url($path);
        // }

           // Handle image upload if new image provided
    if ($request->hasFile('image')) {
        // Delete old image from public/banners
        if ($banner->image_path && file_exists(public_path($banner->image_path))) {
            unlink(public_path($banner->image_path));
        }

        $image = $request->file('image');
        $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

        // Save to public/banners
        $path = 'banners/' . $filename;
        $image->move(public_path('banners'), $filename);

        $updateData['image_path'] = $path;
        $updateData['image_url'] = asset($path);
    }

        $banner->update($updateData);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner updated successfully.');
    }

    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);
        
        // Delete image file
        if (Storage::disk('public')->exists($banner->image_path)) {
            Storage::disk('public')->delete($banner->image_path);
        }
        
        $banner->delete();
        
        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $banner = Banner::findOrFail($id);
        $banner->update(['is_active' => !$banner->is_active]);
        
        $status = $banner->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
            ->with('success', "Banner has been {$status} successfully.");
    }
}
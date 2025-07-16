<?php
namespace App\Repositories;

use App\Models\VenueCategory;
use App\Repositories\Contracts\VenueCategoryRepositoryInterface;

class VenueCategoryRepository implements VenueCategoryRepositoryInterface
{
   public function getAllActive(): array
    {
        return VenueCategory::where('is_active', true)
                           ->with('venueType')
                           ->orderBy('sort_order', 'asc')
                           ->orderBy('name', 'asc')
                           ->get()
                           ->toArray();
    }

    public function getByVenueType(int $venueTypeId): array
    {
        return VenueCategory::where('is_active', true)
                           ->where('venue_type_id', $venueTypeId)
                           ->orderBy('sort_order', 'asc')
                           ->orderBy('name', 'asc')
                           ->get()
                           ->toArray();
    }

    public function findById(int $id): ?VenueCategory
    {
        return VenueCategory::where('is_active', true)->find($id);
    }

    public function findBySlug(string $slug): ?VenueCategory
    {
        return VenueCategory::where('is_active', true)->where('slug', $slug)->first();
    }
}
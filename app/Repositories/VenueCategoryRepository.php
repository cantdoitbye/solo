<?php
namespace App\Repositories;

use App\Models\VenueCategory;
use App\Repositories\Contracts\VenueCategoryRepositoryInterface;

class VenueCategoryRepository implements VenueCategoryRepositoryInterface
{
    public function getAllActive(): array
    {
        return VenueCategory::active()
                           ->with('venueType')
                           ->ordered()
                           ->get()
                           ->toArray();
    }

    public function getByVenueType(int $venueTypeId): array
    {
        return VenueCategory::active()
                           ->forVenueType($venueTypeId)
                           ->ordered()
                           ->get()
                           ->toArray();
    }

    public function findById(int $id): ?VenueCategory
    {
        return VenueCategory::active()->find($id);
    }

    public function findBySlug(string $slug): ?VenueCategory
    {
        return VenueCategory::active()->where('slug', $slug)->first();
    }
}
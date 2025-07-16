<?php
namespace App\Repositories;

use App\Models\VenueType;
use App\Repositories\Contracts\VenueTypeRepositoryInterface;

class VenueTypeRepository implements VenueTypeRepositoryInterface
{
   public function getAllActive(): array
    {
        return VenueType::where('is_active', true)
                       ->orderBy('sort_order', 'asc')
                       ->orderBy('name', 'asc')
                       ->get()
                       ->toArray();
    }

    public function findById(int $id): ?VenueType
    {
        return VenueType::where('is_active', true)->find($id);
    }

    public function findBySlug(string $slug): ?VenueType
    {
        return VenueType::where('is_active', true)->where('slug', $slug)->first();
    }
}
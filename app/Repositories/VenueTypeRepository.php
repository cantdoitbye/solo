<?php
namespace App\Repositories;

use App\Models\VenueType;
use App\Repositories\Contracts\VenueTypeRepositoryInterface;

class VenueTypeRepository implements VenueTypeRepositoryInterface
{
    public function getAllActive(): array
    {
        return VenueType::active()
                       ->ordered()
                       ->get()
                       ->toArray();
    }

    public function findById(int $id): ?VenueType
    {
        return VenueType::active()->find($id);
    }

    public function findBySlug(string $slug): ?VenueType
    {
        return VenueType::active()->where('slug', $slug)->first();
    }
}
<?php 

namespace App\Repositories\Contracts;

interface VenueCategoryRepositoryInterface
{
    public function getAllActive(): array;
    public function getByVenueType(int $venueTypeId): array;
    public function findById(int $id): ?object;
    public function findBySlug(string $slug): ?object;
}
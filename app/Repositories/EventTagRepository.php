<?php
namespace App\Repositories;

use App\Models\EventTag;
use App\Repositories\Contracts\EventTagRepositoryInterface;

class EventTagRepository implements EventTagRepositoryInterface
{
    public function getAllActive(): array
    {
        return EventTag::active()
                      ->ordered()
                      ->get()
                      ->groupBy('category')
                      ->map(function ($tags) {
                          return $tags->toArray();
                      })
                      ->toArray();
    }

    public function getFeatured(): array
    {
        return EventTag::active()
                      ->featured()
                      ->ordered()
                      ->get()
                      ->toArray();
    }

    public function getByCategory(string $category): array
    {
        return EventTag::active()
                      ->byCategory($category)
                      ->ordered()
                      ->get()
                      ->toArray();
    }

    public function findByIds(array $ids): array
    {
        return EventTag::active()
                      ->whereIn('id', $ids)
                      ->get()
                      ->toArray();
    }

    public function findByNames(array $names): array
    {
        return EventTag::active()
                      ->whereIn('name', $names)
                      ->get()
                      ->toArray();
    }
}
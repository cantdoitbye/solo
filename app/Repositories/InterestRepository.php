<?php

namespace App\Repositories;

use App\Models\Interest;
use App\Repositories\Contracts\InterestRepositoryInterface;

class InterestRepository implements InterestRepositoryInterface
{
    public function getAllActive(): array
    {
        return Interest::active()
                      ->orderedBySort()
                      ->get()
                      ->groupBy('category')
                      ->map(function ($interests) {
                          return $interests->toArray();
                      })
                      ->toArray();
    }

    public function getFeaturedInterests(): array
    {
        return Interest::active()
                      ->featured()
                      ->orderedBySort()
                      ->get()
                      ->toArray();
    }

    public function getByCategory(string $category): array
    {
        return Interest::where('category', $category)
                      ->active()
                      ->orderedBySort()
                      ->get()
                      ->toArray();
    }

    public function findByIds(array $ids): array
    {
        return Interest::whereIn('id', $ids)
                      ->active()
                      ->get()
                      ->toArray();
    }

    public function getInterestsWithSuggestions(): array
    {
        $featured = $this->getFeaturedInterests();
        $allByCategory = $this->getAllActive();

        return [
            'featured_suggestions' => $featured,
            'categories' => $allByCategory,
            'load_more_text' => 'Load more suggestions'
        ];
    }

    public function getInterestStats(): array
    {
        return [
            'total_interests' => Interest::active()->count(),
            'categories' => Interest::active()->distinct('category')->pluck('category')->toArray(),
            'featured_count' => Interest::active()->featured()->count()
        ];
    }
}
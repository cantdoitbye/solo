<?php

namespace App\Repositories\Contracts;

interface InterestRepositoryInterface
{
    public function getAllActive(): array;
    public function getFeaturedInterests(): array;
    public function getByCategory(string $category): array;
    public function findByIds(array $ids): array;
    public function getInterestsWithSuggestions(): array;
}
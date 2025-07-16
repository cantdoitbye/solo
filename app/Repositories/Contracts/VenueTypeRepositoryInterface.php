<?php

namespace App\Repositories\Contracts;

interface VenueTypeRepositoryInterface
{
    public function getAllActive(): array;
    public function findById(int $id): ?object;
    public function findBySlug(string $slug): ?object;
}
<?php 
namespace App\Repositories\Contracts;

interface EventTagRepositoryInterface
{
    public function getAllActive(): array;
    public function getFeatured(): array;
    public function getByCategory(string $category): array;
    public function findByIds(array $ids): array;
    public function findByNames(array $names): array;
}

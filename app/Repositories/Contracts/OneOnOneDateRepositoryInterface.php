<?php

// Interface
namespace App\Repositories\Contracts;

use App\Models\OneOnOneDate;

interface OneOnOneDateRepositoryInterface
{
    public function create(array $data): OneOnOneDate;
    
    public function findById(int $id): ?OneOnOneDate;
    
    public function getByHost(int $hostId, ?string $status = null): array;
    
    public function getOneOnOneDates(array $filters = [], int $limit = 10, int $offset = 0): array;
}
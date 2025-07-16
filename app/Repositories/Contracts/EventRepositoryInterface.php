<?php

namespace App\Repositories\Contracts;

interface EventRepositoryInterface
{
     public function create(array $data): object;
    public function update(int $id, array $data): object;
    public function findById(int $id): ?object;
    public function findByIdAndHost(int $id, int $hostId): ?object;
    public function getByHost(int $hostId, string $status = null): array;
    public function publishEvent(int $id): object;
    public function cancelEvent(int $id, string $reason = null): object;
    public function getUpcomingEvents(int $limit = 10): array;
    public function searchEvents(array $filters): array;
}
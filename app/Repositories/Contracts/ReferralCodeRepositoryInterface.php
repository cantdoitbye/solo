<?php

namespace App\Repositories\Contracts;

interface ReferralCodeRepositoryInterface
{
    public function findByCode(string $code): ?object;
    public function createForUser(int $userId): object;
    public function incrementUsage(int $id): object;
}
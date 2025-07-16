<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface
{
    public function findByPhoneNumber(string $phoneNumber, string $countryCode): ?object;
    public function create(array $data): object;
    public function update(int $id, array $data): object;
    public function findById(int $id): ?object;
    public function verifyOtp(int $userId, string $otp): bool;
    public function generateOtp(int $userId): string;
    public function completeOnboarding(int $userId): object;
}
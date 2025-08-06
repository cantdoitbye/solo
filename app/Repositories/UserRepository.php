<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Carbon\Carbon;

class UserRepository implements UserRepositoryInterface
{
    public function findByPhoneNumber(string $phoneNumber, string $countryCode): ?User
    {
        return User::where('phone_number', $phoneNumber)
                   ->where('country_code', $countryCode)
                   ->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user->fresh();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function verifyOtp(int $userId, string $otp): bool
    {
        $user = User::findOrFail($userId);
        
        if ($user->isOtpValid($otp)) {
            $user->update([
                'phone_verified_at' => now(),
                'otp_code' => null,
                'otp_expires_at' => null,
            ]);
            return true;
        }
        
        return false;
    }

    public function generateOtp(int $userId): string
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        User::findOrFail($userId)->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);
        
        return $otp;
    }

    public function completeOnboarding(int $userId, string $fcmToken): User
    {
        $user = User::findOrFail($userId);
        $user->update(['onboarding_completed' => true, 'fcm_token' => $fcmToken]);
        return $user;
    }
}
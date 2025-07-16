<?php

namespace App\Repositories;

use App\Models\ReferralCode;
use App\Models\User;
use App\Repositories\Contracts\ReferralCodeRepositoryInterface;

class ReferralCodeRepository implements ReferralCodeRepositoryInterface
{
    public function findByCode(string $code): ?ReferralCode
    {
        return ReferralCode::where('code', $code)
                          ->where('is_active', true)
                          ->first();
    }

    public function createForUser(int $userId): ReferralCode
    {
        $user = User::findOrFail($userId);
        $code = $user->generateReferralCode();
        
        return ReferralCode::create([
            'code' => $code,
            'user_id' => $userId,
        ]);
    }

    public function incrementUsage(int $id): ReferralCode
    {
        $referralCode = ReferralCode::findOrFail($id);
        $referralCode->increment('uses_count');
        return $referralCode;
    }
}
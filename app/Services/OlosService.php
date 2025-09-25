<?php
// app/Services/OlosService.php

namespace App\Services;

use App\Models\User;
use App\Models\UserOlos;
use App\Models\OlosTransaction;
use Illuminate\Support\Facades\DB;

class OlosService
{
    const REGISTRATION_BONUS = 100.00;

    /**
     * Initialize user's Olos account with registration bonus
     */
    public function initializeUserOlos(int $userId): UserOlos
    {
        return DB::transaction(function () use ($userId) {
            // Create or get user olos record
            $userOlos = UserOlos::firstOrCreate(
                ['user_id' => $userId],
                [
                    'balance' => 0,
                    'total_earned' => 0,
                    'total_spent' => 0,
                ]
            );

            // Check if registration bonus already given
            $hasRegistrationBonus = OlosTransaction::where('user_id', $userId)
                ->where('transaction_type', OlosTransaction::TRANSACTION_TYPE_REGISTRATION_BONUS)
                ->where('status', OlosTransaction::STATUS_COMPLETED)
                ->exists();

            if (!$hasRegistrationBonus) {
                $this->addOlos(
                    $userId,
                    self::REGISTRATION_BONUS,
                    OlosTransaction::TRANSACTION_TYPE_REGISTRATION_BONUS,
                    'Welcome bonus - Free Olos for registering!'
                );
                $userOlos->refresh();
            }

            return $userOlos;
        });
    }

    /**
     * Get user's current Olos balance
     */
    public function getUserBalance(?int $userId): float
    {
        $userOlos = UserOlos::where('user_id', $userId)->first();
        return $userOlos ? $userOlos->balance : 0;
    }

    /**
     * Check if user has enough Olos for a transaction
     */
    public function hasEnoughBalance(int $userId, float $amount): bool
    {
        return $this->getUserBalance($userId) >= $amount;
    }

    /**
     * Add Olos to user's account
     */
    public function addOlos(
        int $userId,
        float $amount,
        string $transactionType,
        string $description,
        ?string $referenceId = null,
        ?array $metadata = null
    ): OlosTransaction {
        return DB::transaction(function () use ($userId, $amount, $transactionType, $description, $referenceId, $metadata) {
            $userOlos = UserOlos::firstOrCreate(
                ['user_id' => $userId],
                [
                    'balance' => 0,
                    'total_earned' => 0,
                    'total_spent' => 0,
                ]
            );

            $balanceBefore = $userOlos->balance;
            $userOlos->addBalance($amount);
            $balanceAfter = $userOlos->balance;

            return OlosTransaction::create([
                'user_id' => $userId,
                'type' => OlosTransaction::TYPE_CREDIT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_type' => $transactionType,
                'description' => $description,
                'reference_id' => $referenceId,
                'metadata' => $metadata,
                'status' => OlosTransaction::STATUS_COMPLETED,
            ]);
        });
    }

    /**
     * Deduct Olos from user's account
     */
    public function deductOlos(
        int $userId,
        float $amount,
        string $transactionType,
        string $description,
        ?string $referenceId = null,
        ?array $metadata = null
    ): OlosTransaction {
        return DB::transaction(function () use ($userId, $amount, $transactionType, $description, $referenceId, $metadata) {
            $userOlos = UserOlos::where('user_id', $userId)->first();
            
            if (!$userOlos || !$userOlos->hasEnoughBalance($amount)) {
                throw new \Exception('Insufficient Olos balance. Current balance: ' . ($userOlos ? $userOlos->balance : 0) . ' Olos');
            }

            $balanceBefore = $userOlos->balance;
            $userOlos->deductBalance($amount);
            $balanceAfter = $userOlos->balance;

            return OlosTransaction::create([
                'user_id' => $userId,
                'type' => OlosTransaction::TYPE_DEBIT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_type' => $transactionType,
                'description' => $description,
                'reference_id' => $referenceId,
                'metadata' => $metadata,
                'status' => OlosTransaction::STATUS_COMPLETED,
            ]);
        });
    }

    /**
     * Refund Olos to user's account
     */
    public function refundOlos(
        int $userId,
        float $amount,
        string $description,
        ?string $referenceId = null,
        ?array $metadata = null
    ): OlosTransaction {
        return $this->addOlos(
            $userId,
            $amount,
            OlosTransaction::TRANSACTION_TYPE_EVENT_REFUND,
            $description,
            $referenceId,
            $metadata
        );
    }

    /**
     * Get user's transaction history
     */
    public function getUserTransactions(int $userId, int $limit = 50): array
    {
        $transactions = OlosTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'transaction_type' => $transaction->transaction_type,
                'description' => $transaction->description,
                'balance_after' => $transaction->balance_after,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at->toISOString(),
                'metadata' => $transaction->metadata,
            ];
        })->toArray();
    }

    /**
     * Get user's Olos summary
     */
    public function getUserOlosSummary(int $userId): array
    {
        $userOlos = UserOlos::where('user_id', $userId)->first();
        
        if (!$userOlos) {
            return [
                'current_balance' => 0,
                'total_earned' => 0,
                'total_spent' => 0,
                'recent_transactions' => [],
            ];
        }

        $recentTransactions = $this->getUserTransactions($userId, 10);

        return [
            'current_balance' => $userOlos->balance,
            'total_earned' => $userOlos->total_earned,
            'total_spent' => $userOlos->total_spent,
            'recent_transactions' => $recentTransactions,
        ];
    }
}
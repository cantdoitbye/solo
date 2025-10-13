<?php
// app/Http/Controllers/Api/FluidpayWebhookController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserPlan;
use App\Notifications\PlanActivatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FluidpayWebhookController extends Controller
{
    /**
     * Handle Fluidpay webhook
     */
    public function handle(Request $request): JsonResponse
    {
        // Log webhook payload
        $this->logWebhook($request);

        try {
            $payload = $request->all();

            // Extract payment details
            $transactionId = $payload['id'] ?? $payload['transaction_id'] ?? null;
            $status = $payload['status'] ?? 'pending';
            $amount = $payload['amount'] ?? 0;
            $currency = $payload['currency'] ?? 'USD';
            
            // Get customer details
            $customerEmail = $payload['billing']['email'] ?? $payload['customer_email'] ?? null;
            $customerName = ($payload['billing']['first_name'] ?? '') . ' ' . ($payload['billing']['last_name'] ?? '');
            
            if (!$transactionId || !$customerEmail) {
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            // Determine plan based on amount
            $planId = $this->determinePlan($amount);

            // Process payment
            if (in_array(strtolower($status), ['success', 'approved', 'completed'])) {
                return $this->handleSuccessfulPayment(
                    $transactionId,
                    $customerEmail,
                    $customerName,
                    $planId,
                    $amount,
                    $currency,
                    $payload
                );
            } else {
                return $this->handleFailedPayment(
                    $transactionId,
                    $customerEmail,
                    $customerName,
                    $planId,
                    $amount,
                    $currency,
                    $status,
                    $payload
                );
            }

        } catch (\Exception $e) {
            Log::error('Fluidpay webhook error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Handle successful payment
     */
   private function handleSuccessfulPayment(
    string $transactionId,
    string $email,
    string $name,
    string $planId,
    float $amount,
    string $currency,
    array $payload
): JsonResponse {
    DB::beginTransaction();

    try {
        // Check if already processed
        $existingPayment = Payment::where('transaction_id', $transactionId)->first();
        if ($existingPayment) {
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Already processed'
            ]);
        }

        // Find user by email (no need for hash here)
        $user = User::where('email', $email)->first();
        
        // Create payment record
        $payment = Payment::create([
            'user_id' => $user?->id,
            'user_email' => $email,
            'user_name' => $name,
            'plan_id' => $planId,
            'amount' => $amount,
            'currency' => $currency,
            'transaction_id' => $transactionId,
            'status' => 'completed',
            'payment_data' => $payload,
            'payment_date' => now(),
        ]);

        // Activate plan for user if user exists
        if ($user) {
            $this->activateUserPlan($user->id, $planId, $transactionId);
            // $user->notify(new PlanActivatedNotification($planId, $payment));
        }

        DB::commit();

        Log::info('Payment processed successfully', [
            'transaction_id' => $transactionId,
            'email' => $email,
            'plan_id' => $planId
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'payment_id' => $payment->id
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

    /**
     * Handle failed payment
     */
    private function handleFailedPayment(
        string $transactionId,
        string $email,
        string $name,
        string $planId,
        float $amount,
        string $currency,
        string $status,
        array $payload
    ): JsonResponse {
        try {
            $user = User::where('email', $email)->first();

            Payment::create([
                'user_id' => $user?->id,
                'user_email' => $email,
                'user_name' => $name,
                'plan_id' => $planId,
                'amount' => $amount,
                'currency' => $currency,
                'transaction_id' => $transactionId,
                'status' => $status === 'failed' ? 'failed' : 'pending',
                'payment_data' => $payload,
                'payment_date' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment ' . $status
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log failed payment: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Activate plan for user
     */
    private function activateUserPlan(int $userId, string $planId, string $transactionId): void
    {
        UserPlan::updateOrCreate(
            ['user_id' => $userId],
            [
                'plan_id' => $planId,
                'transaction_id' => $transactionId,
                'activated_at' => now(),
                'status' => 'active'
            ]
        );
    }

    /**
     * Determine plan based on amount
     */
    private function determinePlan(float $amount): string
    {
        $plans = config('fluidpay.plans');

        foreach ($plans as $planId => $plan) {
            if (abs($amount - $plan['amount']) < 0.01) {
                return $planId;
            }
        }

        return 'basic';
    }

    /**
     * Log webhook
     */
    private function logWebhook(Request $request): void
    {
        DB::table('webhook_logs')->insert([
            'source' => 'fluidpay',
            'payload' => json_encode($request->all()),
            'event_type' => $request->input('event_type', 'payment'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Verify webhook signature
     */
    private function verifySignature(Request $request): bool
    {
        $secret = config('fluidpay.webhook_secret');
        
        if (!$secret) {
            return true;
        }

        // Implement based on Fluidpay documentation
        return true;
    }
}
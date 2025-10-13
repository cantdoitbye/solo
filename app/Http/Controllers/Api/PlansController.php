<?php
// app/Http/Controllers/Api/V1/PlansController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserPlan;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlansController extends Controller
{
    /**
     * Get all available plans
     */
    public function index(): JsonResponse
    {
        $plans = $this->getPlansWithUrls();

        return response()->json([
            'success' => true,
            'plans' => $plans
        ]);
    }

    /**
     * Get user's active plan
     */
    public function getUserPlan(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
                'has_plan' => false
            ], 401);
        }

        $userPlan = UserPlan::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('payment')
            ->first();

        if ($userPlan) {
            $planDetails = $userPlan->getPlanDetails();

            return response()->json([
                'success' => true,
                'has_plan' => true,
                'plan' => [
                    'id' => $userPlan->plan_id,
                    'name' => $planDetails['name'] ?? 'Unknown',
                    'amount' => $planDetails['amount'] ?? 0,
                    'features' => $planDetails['features'] ?? [],
                    'activated_at' => $userPlan->activated_at,
                    'status' => $userPlan->status,
                    'transaction_id' => $userPlan->transaction_id
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'has_plan' => false,
            'message' => 'No active plan found'
        ]);
    }

    /**
     * Get payment history for user
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $payments = Payment::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                $planDetails = $payment->getPlanDetails();
                return [
                    'id' => $payment->id,
                    'plan_name' => $planDetails['name'] ?? 'Unknown',
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'transaction_id' => $payment->transaction_id,
                    'payment_date' => $payment->payment_date,
                    'created_at' => $payment->created_at
                ];
            });

        return response()->json([
            'success' => true,
            'payments' => $payments
        ]);
    }

    /**
     * Get plans with payment URLs
     */
    private function getPlansWithUrls(): array
    {
        $plans = config('fluidpay.plans');
        $paymentUrls = config('fluidpay.payment_urls');
        $plansWithUrls = [];

        foreach ($plans as $planId => $plan) {
            $plansWithUrls[] = array_merge($plan, [
                'payment_url' => ($paymentUrls[$planId] ?? '#') . '?add_custom_amount=' . $plan['amount']
            ]);
        }

        return $plansWithUrls;
    }
}
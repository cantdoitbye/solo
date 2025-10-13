<?php
// app/Http/Controllers/PlansController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPlan;
use App\Services\UserHashService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlansController extends Controller
{

    private UserHashService $hashService;

    public function __construct(UserHashService $hashService)
    {
        $this->hashService = $hashService;
    }
    /**
     * Display the plans page
     * Accepts user_id or email from query params for mobile app
     */
    public function index(Request $request): View
    {
        $plans = $this->getPlansWithUrls();
        
        // Get user info from query params (sent by mobile app)
       $hash = $request->query('hash');
        $activePlan = null;
        $user = null;
        
        // Get user's active plan
        $activePlan = null;
        $user = null;
        
         if ($hash) {
            $userId = $this->hashService->verifyHash($hash);
            
            if ($userId) {
                $user = User::find($userId);
                
                if ($user) {
                    $activePlan = UserPlan::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->first();
                }
            }
        }
              return view('plans.index', compact('plans', 'activePlan', 'user', 'hash'));
 }

    /**
     * Get all plans with payment URLs including user info
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

    /**
     * Payment success page
     */
     public function success(Request $request)
    {
        return view('plans.success', [
            'message' => 'Payment successful! Your plan will be activated shortly.',
            'hash' => $request->query('hash')
        ]);
    }

    public function cancel(Request $request)
    {
        return view('plans.cancel', [
            'message' => 'Payment cancelled. You can try again anytime.',
            'hash' => $request->query('hash')
        ]);
    }
}
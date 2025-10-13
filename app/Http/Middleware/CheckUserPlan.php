<?php
// app/Http/Middleware/CheckUserPlan.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserPlan
{
    /**
     * Handle an incoming request.
     *
     * @param  string  ...$plans  Required plan IDs (e.g., 'pro', 'premium')
     */
    public function handle(Request $request, Closure $next, string ...$plans): Response
    {
        $user = $request->user();

        // If no user is authenticated
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        // If no specific plans required, just check if user has any active plan
        if (empty($plans)) {
            if (!$user->hasActivePlan()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active plan required',
                    'required_action' => 'purchase_plan',
                    'plans_url' => url('/plans')
                ], 403);
            }
            return $next($request);
        }

        // Check if user has any of the required plans
        if (!$user->hasAnyPlan($plans)) {
            $planNames = $this->getPlanNames($plans);
            
            return response()->json([
                'success' => false,
                'message' => 'This feature requires ' . $planNames . ' plan',
                'required_plans' => $plans,
                'current_plan' => $user->activePlan?->plan_id,
                'required_action' => 'upgrade_plan',
                'plans_url' => url('/plans')
            ], 403);
        }

        return $next($request);
    }

    /**
     * Get formatted plan names
     */
    private function getPlanNames(array $plans): string
    {
        $names = array_map(function ($planId) {
            $plan = config('fluidpay.plans.' . $planId);
            return $plan['name'] ?? ucfirst($planId);
        }, $plans);

        if (count($names) === 1) {
            return $names[0];
        }

        $last = array_pop($names);
        return implode(', ', $names) . ' or ' . $last;
    }
}
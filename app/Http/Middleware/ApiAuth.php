<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please provide a valid API token.',
                'error_code' => 'TOKEN_REQUIRED'
            ], 401);
        }

        // Check if user completed onboarding for protected routes
        $user = $request->user();
        if (!$user->onboarding_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete onboarding first.',
                'error_code' => 'ONBOARDING_INCOMPLETE'
            ], 403);
        }

        return $next($request);
    }
}

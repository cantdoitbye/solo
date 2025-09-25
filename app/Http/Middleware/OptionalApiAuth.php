<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptionalApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         // Try to authenticate the user if token is provided
        if ($request->bearerToken()) {
            try {
                // This will set the user if token is valid, or leave it null if invalid/expired
                $request->user();
            } catch (\Exception $e) {
                // Token is invalid/expired, but we continue without authentication
                // The request->user() will return null
            }
        }

        return $next($request);
    }
}

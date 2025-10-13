<?php
// app/Http/Controllers/Api/V1/UserHashController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UserHashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserHashController extends Controller
{
    private UserHashService $hashService;

    public function __construct(UserHashService $hashService)
    {
        $this->hashService = $hashService;
    }

    /**
     * Generate a secure hash for authenticated user
     * GET /api/v1/user/hash
     */
    public function generateHash(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $hash = $this->hashService->generateHash($user->id);

        return response()->json([
            'success' => true,
            'user_hash' => $hash,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ],
            'plans_url' => url('/plans?hash=' . $hash),
            'expires_in' => '24 hours'
        ]);
    }

    /**
     * Verify a hash and get user info
     * POST /api/v1/user/verify-hash
     */
    public function verifyHash(Request $request): JsonResponse
    {
        $request->validate([
            'hash' => 'required|string'
        ]);

        $userId = $this->hashService->verifyHash($request->hash);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired hash'
            ], 400);
        }

        $user = \App\Models\User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]
        ]);
    }
}
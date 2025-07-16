<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    public function getProfile(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $profile = $this->profileService->getUserProfile($userId);

            return response()->json([
                'success' => true,
                'data' => $profile,
                'api_version' => 'v1'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'api_version' => 'v1'
            ], 400);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $result = $this->profileService->logoutUser($userId);

            return response()->json([
                'success' => true,
                'data' => $result,
                'api_version' => 'v1'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'api_version' => 'v1'
            ], 400);
        }
    }

    public function logoutCurrentDevice(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $tokenId = $request->user()->currentAccessToken()->id;
            
            $result = $this->profileService->logoutFromCurrentDevice($userId, $tokenId);

            return response()->json([
                'success' => true,
                'data' => $result,
                'api_version' => 'v1'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'api_version' => 'v1'
            ], 400);
        }
    }

    public function getProfileStats(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $profile = $this->profileService->getUserProfile($userId);

            // Return only stats for dashboard/quick view
            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'profile_completion' => $profile['stats']['profile_completion'],
                    'interests_count' => $profile['interests']['interests_count'],
                    'referral_points' => $profile['referral']['referral_points'],
                    'member_since' => $profile['stats']['member_since'],
                    'connection_type' => $profile['preferences']['connection_type_label']
                ],
                'api_version' => 'v1'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'api_version' => 'v1'
            ], 400);
        }
    }
}

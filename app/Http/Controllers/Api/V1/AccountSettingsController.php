<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AccountSettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountSettingsController extends Controller
{
    public function __construct(
        private AccountSettingsService $accountSettingsService
    ) {
    }

    /**
     * Get account settings
     */
    public function getAccountSettings(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $settings = $this->accountSettingsService->getAccountSettings($userId);

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update account settings (single API for all settings)
     */
    public function updateAccountSettings(Request $request): JsonResponse
    {
    
        $request->validate([
            'two_factor_enabled' => 'sometimes|boolean',
            'push_notifications_enabled' => 'sometimes|boolean',
            'sound_alerts_enabled' => 'sometimes|boolean',
            'selected_theme' => ['sometimes', 'string'],
            'default_language' => ['sometimes', 'string'],
        ]);

        try {
            $userId = Auth::id();
            $result = $this->accountSettingsService->updateAccountSettings(
                $userId, 
                $request->only([
                    'two_factor_enabled',
                    'push_notifications_enabled',
                    'sound_alerts_enabled',
                    'selected_theme',
                    'default_language'
                ])
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get login activity history with pagination
     */
    public function getLoginActivityHistory(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        try {
            $userId = Auth::id();
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            
            $activities = $this->accountSettingsService->getLoginActivityHistory($userId, $page, $perPage);

            return response()->json([
                'success' => true,
                'data' => $activities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'confirmation' => 'required|string|in:DELETE_MY_ACCOUNT',
        ]);

        try {
            $userId = Auth::id();
            $result = $this->accountSettingsService->deleteAccount($userId);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get security information
     */
    public function getSecurityInfo(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $securityInfo = $this->accountSettingsService->getSecurityInfo($userId);

            return response()->json([
                'success' => true,
                'data' => $securityInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Toggle two-factor authentication
     */
    public function toggleTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        try {
            $userId = Auth::id();
            $result = $this->accountSettingsService->toggleTwoFactor($userId, $request->enabled);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
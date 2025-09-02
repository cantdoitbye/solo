<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountSettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
    // public function updateAccountSettings(Request $request): JsonResponse
    // {
    
    //     $request->validate([
    //         'two_factor_enabled' => 'sometimes|boolean',
    //         'push_notifications_enabled' => 'sometimes|boolean',
    //         'sound_alerts_enabled' => 'sometimes|boolean',
    //         'selected_theme' => ['sometimes', 'string'],
    //         'default_language' => ['sometimes', 'string'],
    //     ]);

    //     try {
    //         $userId = Auth::id();
    //         $result = $this->accountSettingsService->updateAccountSettings(
    //             $userId, 
    //             $request->only([
    //                 'two_factor_enabled',
    //                 'push_notifications_enabled',
    //                 'sound_alerts_enabled',
    //                 'selected_theme',
    //                 'default_language'
    //             ])
    //         );

    //         return response()->json([
    //             'success' => true,
    //             'data' => $result
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage()
    //         ], 400);
    //     }
    // }


    /**
     * Update account settings with fallback to raw DB queries
     */
    public function updateAccountSettings(int $userId, array $settings): array
    {
        // Log incoming request for debugging
        Log::info('Account Settings Update Request', [
            'user_id' => $userId,
            'settings' => $settings
        ]);

        $user = User::findOrFail($userId);
        
        // Check if columns exist before trying to update
        $this->ensureColumnsExist();
        
        $allowedSettings = [
            'two_factor_enabled',
            'push_notifications_enabled',
            'sound_alerts_enabled',
            'selected_theme',
            'default_language',
        ];

        $updateData = [];
        foreach ($settings as $key => $value) {
            if (in_array($key, $allowedSettings)) {
                // Ensure boolean values are properly cast
                if (in_array($key, ['two_factor_enabled', 'push_notifications_enabled', 'sound_alerts_enabled'])) {
                    $updateData[$key] = (bool) $value;
                } else {
                    $updateData[$key] = $value;
                }
            }
        }

        Log::info('Update Data to be Applied', [
            'user_id' => $userId,
            'update_data' => $updateData
        ]);

        if (!empty($updateData)) {
            $updateData['account_settings_updated_at'] = now();
            
            try {
                // Method 1: Try Eloquent update first
                $updated = $user->update($updateData);
                
                if (!$updated) {
                    Log::warning('Eloquent update failed, trying raw DB update');
                    
                    // Method 2: Fallback to raw DB update
                    $updated = DB::table('users')
                        ->where('id', $userId)
                        ->update($updateData);
                }
                
                Log::info('Update Result', [
                    'user_id' => $userId,
                    'update_success' => $updated,
                    'updated_data' => $updateData
                ]);

                // Refresh user model to get latest data
                $user = User::find($userId);

                Log::info('Final User State After Update', [
                    'user_id' => $userId,
                    'final_two_factor' => $user->two_factor_enabled ?? 'null',
                    'final_push_notifications' => $user->push_notifications_enabled ?? 'null',
                    'final_sound_alerts' => $user->sound_alerts_enabled ?? 'null',
                    'final_theme' => $user->selected_theme ?? 'null',
                    'final_language' => $user->default_language ?? 'null',
                ]);

            } catch (\Exception $e) {
                Log::error('Account Settings Update Failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                    'update_data' => $updateData,
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        }

        // Get fresh data from database
        $freshUser = User::find($userId);

        return [
            'success' => true,
            'message' => 'Account settings updated successfully',
            'updated_settings' => $updateData,
            'current_settings' => [
                'two_factor_enabled' => (bool) ($freshUser->two_factor_enabled ?? false),
                'push_notifications_enabled' => (bool) ($freshUser->push_notifications_enabled ?? true),
                'sound_alerts_enabled' => (bool) ($freshUser->sound_alerts_enabled ?? true),
                'selected_theme' => $freshUser->selected_theme ?? 'system_default',
                'default_language' => $freshUser->default_language ?? 'english_us',
            ]
        ];
    }


    /**
     * Ensure all required columns exist in the database
     */
    private function ensureColumnsExist(): void
    {
        $columnsToCheck = [
            'two_factor_enabled' => 'TINYINT(1) DEFAULT 0',
            'push_notifications_enabled' => 'TINYINT(1) DEFAULT 1',
            'sound_alerts_enabled' => 'TINYINT(1) DEFAULT 1',
            'selected_theme' => "VARCHAR(255) DEFAULT 'system_default'",
            'default_language' => "VARCHAR(255) DEFAULT 'english_us'",
            'account_settings_updated_at' => 'TIMESTAMP NULL'
        ];

        foreach ($columnsToCheck as $column => $definition) {
            if (!Schema::hasColumn('users', $column)) {
                Log::warning("Column {$column} missing, attempting to add it");
                try {
                    DB::statement("ALTER TABLE users ADD COLUMN {$column} {$definition}");
                    Log::info("Successfully added column: {$column}");
                } catch (\Exception $e) {
                    Log::error("Failed to add column {$column}: " . $e->getMessage());
                }
            }
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
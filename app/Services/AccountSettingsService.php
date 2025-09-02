<?php

namespace App\Services;

use App\Models\User;
use App\Models\LoginActivityHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountSettingsService
{
    /**
     * Get user account settings
     */
    public function getAccountSettings(int $userId): array
    {
        $user = User::findOrFail($userId);

        return [
            'user_id' => $userId,
            'security' => [
                'two_factor_enabled' => $user->two_factor_enabled,
                'phone_number' => $user->country_code . ' ' . $user->phone_number,
                'phone_verified' => !is_null($user->phone_verified_at),
            ],
            'notifications' => [
                'push_notifications_enabled' => $user->push_notifications_enabled,
                'sound_alerts_enabled' => $user->sound_alerts_enabled,
            ],
            'preferences' => [
                'selected_theme' => $user->selected_theme,
                'default_language' => $user->default_language,
            ],
            'available_options' => [
                'themes' => User::getAvailableThemes(),
                'languages' => User::getAvailableLanguages(),
            ]
        ];
    }

    /**
     * Update account settings
     */
    public function updateAccountSettings(int $userId, array $settings): array
    {
        $user = User::findOrFail($userId);

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
              

                $updateData[$key] = $value;
            }
        }

        if (!empty($updateData)) {
            $updateData['account_settings_updated_at'] = now();
            $user->update($updateData);
        }

        return [
            'success' => true,
            'message' => 'Account settings updated successfully',
            'updated_settings' => $updateData
        ];
    }

    /**
     * Get login activity history with pagination
     */
    public function getLoginActivityHistory(int $userId, int $page = 1, int $perPage = 20): array
    {
        $query = LoginActivityHistory::where('user_id', $userId)
            ->orderBy('login_at', 'desc');

        // Get total count for pagination info
        $totalCount = $query->count();
        
        // Calculate pagination
        $offset = ($page - 1) * $perPage;
        $totalPages = (int) ceil($totalCount / $perPage);
        
        // Get paginated results
        $activities = $query->offset($offset)
            ->limit($perPage)
            ->get();

        $formattedActivities = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'device_name' => $activity->device_name,
                'device_type' => $activity->device_type,
                'formatted_device' => $activity->formatted_device,
                'location' => $activity->formatted_location,
                'login_at' => $activity->login_at,
                'login_at_formatted' => $activity->login_at->format('M j, Y - g:i A'),
                'os_info' => $activity->os_name . ($activity->os_version ? ' ' . $activity->os_version : ''),
                'app_version' => $activity->app_version,
            ];
        });

        return [
            'user_id' => $userId,
            'activities' => $formattedActivities,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_count' => $totalCount,
                'total_pages' => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_previous_page' => $page > 1,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'previous_page' => $page > 1 ? $page - 1 : null,
                'from' => $totalCount > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $totalCount)
            ]
        ];
    }

    /**
     * Record login activity
     */
    public function recordLoginActivity(int $userId, array $deviceInfo): LoginActivityHistory
    {
        return LoginActivityHistory::create([
            'user_id' => $userId,
            'device_name' => $deviceInfo['device_name'] ?? null,
            'device_type' => $deviceInfo['device_type'] ?? null,
            'os_name' => $deviceInfo['os_name'] ?? null,
            'ip_address' => $deviceInfo['ip_address'] ?? request()->ip(),
            'location' => $deviceInfo['location'] ?? null,
            'city' => $deviceInfo['city'] ?? null,
            'country' => $deviceInfo['country'] ?? null,
            'latitude' => $deviceInfo['latitude'] ?? null,
            'longitude' => $deviceInfo['longitude'] ?? null,
            'user_agent' => request()->userAgent(),
            'login_at' => now(),
        ]);
    }

    /**
     * Delete user account
     */
    public function deleteAccount(int $userId): array
    {
        $user = User::findOrFail($userId);

        // Revoke all tokens
        $user->tokens()->delete();

        $user->delete();

        // $user->update([
        //     'phone_verified_at' => null,
        //     'onboarding_completed' => false,
        //     'fcm_token' => null,
        //     'account_settings_updated_at' => now(),
        // ]);

        return [
            'success' => true,
            'message' => 'Account deleted successfully',
            'user_id' => $userId
        ];
    }

    /**
     * Get security info
     */
    public function getSecurityInfo(int $userId): array
    {
        $user = User::findOrFail($userId);
        
        $recentLoginCount = LoginActivityHistory::where('user_id', $userId)
            ->where('login_at', '>=', now()->subDays(30))
            ->count();

        return [
            'user_id' => $userId,
            'two_factor_enabled' => $user->two_factor_enabled,
            'phone_verified' => !is_null($user->phone_verified_at),
            'recent_login_count' => $recentLoginCount,
            'last_login' => LoginActivityHistory::where('user_id', $userId)
                ->orderBy('login_at', 'desc')
                ->first()?->login_at,
        ];
    }

    /**
     * Toggle two-factor authentication
     */
    public function toggleTwoFactor(int $userId, bool $enabled): array
    {
        $user = User::findOrFail($userId);

        $user->update([
            'two_factor_enabled' => $enabled,
            'account_settings_updated_at' => now(),
        ]);

        return [
            'success' => true,
            'two_factor_enabled' => $enabled,
            'message' => $enabled ? 'Two-factor authentication enabled' : 'Two-factor authentication disabled'
        ];
    }
}
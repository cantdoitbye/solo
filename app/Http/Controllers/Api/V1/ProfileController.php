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
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'api_version' => 'v1'
            ], 400);
        }
    }

      public function getProfile2(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $profile = $this->profileService->getUserProfile2($userId);

            return response()->json([
                'success' => true,
                'data' => $profile,
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


public function updateProfile(Request $request): JsonResponse
{
    // Add logging to see what we're receiving
    \Log::info('Update Profile Request:', [
        'has_file' => $request->file('profile_photo'),
        'files' => $request->allFiles(),
        'all_data' => $request->all()
    ]);

    $request->validate([
        'name' => 'sometimes|string|max:255',
        'age' => 'sometimes|integer|min:18|max:100',
        'gender' => 'sometimes|in:male,female,other',
        'bio' => 'sometimes|string|max:500',
        'profile_photo' => 'sometimes|file|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max
    ]);

    try {
        $userId = $request->user()->id;
        $updateData = [];

        // Handle basic profile fields
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }

        if ($request->has('age')) {
            $updateData['age'] = $request->age;
        }

        if ($request->has('gender')) {
            $updateData['gender'] = $request->gender;
        }

        if ($request->has('bio')) {
            $updateData['bio'] = $request->bio;
        }

        // Handle profile photo upload with detailed logging
        if ($request->hasFile('profile_photo')) {
            \Log::info('File detected, processing upload...');
            
            $file = $request->file('profile_photo');
            \Log::info('File details:', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'is_valid' => $file->isValid()
            ]);
            
            if ($file->isValid()) {
                $profilePhotoUrl = $this->handleProfilePhotoUpload($file, $userId);
                $updateData['profile_photo'] = $profilePhotoUrl;
                \Log::info('Photo upload successful:', ['url' => $profilePhotoUrl]);
            } else {
                \Log::error('Invalid file uploaded');
                throw new \Exception('Invalid file uploaded');
            }
        } else {
            \Log::info('No profile photo file detected in request');
        }

        \Log::info('Update data:', $updateData);

        // Update user profile
        $result = $this->profileService->updateUserProfile($userId, $updateData);

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Profile updated successfully',
        ]);

    } catch (\Exception $e) {
        \Log::error('Profile update failed:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 400);
    }
}

/**
 * Handle profile photo upload to public folder
 */
private function handleProfilePhotoUpload($file, int $userId): string
{
    try {
        \Log::info('Starting profile photo upload for user: ' . $userId);
        
        // Get file properties
        $extension = $file->getClientOriginalExtension();
        
        // Generate unique filename
        $filename = 'profile_' . $userId . '_' . time() . '_' . uniqid() . '.' . $extension;
        \Log::info('Generated filename: ' . $filename);
        
        // Define the path in public folder
        $publicPath = 'uploads/profiles';
        $fullPath = public_path($publicPath);
        \Log::info('Upload path: ' . $fullPath);
        
        // Create directory if it doesn't exist
        if (!file_exists($fullPath)) {
            \Log::info('Creating directory: ' . $fullPath);
            if (!mkdir($fullPath, 0755, true)) {
                throw new \Exception('Failed to create upload directory');
            }
        }
        
        // Check if directory is writable
        if (!is_writable($fullPath)) {
            throw new \Exception('Upload directory is not writable: ' . $fullPath);
        }
        
        // Get current user to delete old photo
        $user = \App\Models\User::find($userId);
        if ($user && $user->profile_photo) {
            $oldPhotoPath = public_path($user->profile_photo);
            \Log::info('Deleting old photo: ' . $oldPhotoPath);
            if (file_exists($oldPhotoPath)) {
                unlink($oldPhotoPath);
                \Log::info('Old photo deleted successfully');
            }
        }
        
        // Move file to public folder
        \Log::info('Moving file to: ' . $fullPath . '/' . $filename);
        if (!$file->move($fullPath, $filename)) {
            throw new \Exception('Failed to move uploaded file');
        }
        
        // Verify file was moved
        $finalPath = $fullPath . '/' . $filename;
        if (!file_exists($finalPath)) {
            throw new \Exception('File was not saved successfully');
        }
        
        $relativePath = $publicPath . '/' . $filename;
        \Log::info('File upload completed. Relative path: ' . $relativePath);
        
        // Return relative path for storage in database
        return $relativePath;
        
    } catch (\Exception $e) {
        \Log::error('Profile photo upload failed:', [
            'error' => $e->getMessage(),
            'user_id' => $userId
        ]);
        throw new \Exception('Failed to upload profile photo: ' . $e->getMessage());
    }
}


/**
 * Update user location (latitude and longitude)
 */
public function updateLocation(Request $request): JsonResponse
{
    $request->validate([
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
    ]);

    try {
        $userId = $request->user()->id;
        
        $updateData = [
            'latitude' => $request->latitude,
            'longitude' => $request->longitude
        ];

        // Update user location in database
        $this->profileService->updateUserProfile($userId, $updateData);

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $userId,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'updated_at' => now()->toISOString()
            ],
            'message' => 'Location updated successfully',
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 400);
    }
}

}

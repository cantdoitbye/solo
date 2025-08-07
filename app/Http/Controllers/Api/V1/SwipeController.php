<?php
// app/Http/Controllers/Api/V1/SwipeController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OlosTransaction;
use App\Models\User;
use App\Services\OlosService;
use App\Services\SwipeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class SwipeController extends Controller
{
    public function __construct(
        private SwipeService $swipeService,
        private OlosService $olosService

    ) {}

    /**
     * Get profiles for swiping
     */
    public function getProfiles(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'integer|min:1|max:20'
        ]);

        try {
            $userId = $request->user()->id;
            $limit = $request->get('limit', 10);
            
            $result = $this->swipeService->getDiscoverProfiles($userId, $limit);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Handle swipe action
     */
    public function swipe(Request $request): JsonResponse
    {
        $request->validate([
            'target_user_id' => 'required|integer|exists:users,id',
            'action' => ['required', Rule::in(['like', 'pass', 'super_like'])]
        ]);

        try {
            $userId = $request->user()->id;
            $targetUserId = $request->target_user_id;
            $action = $request->action;

            // Prevent self-swipe
            if ($userId === $targetUserId) {
                throw new \Exception('Cannot swipe on yourself');
            }

            $result = $this->swipeService->handleSwipe($userId, $targetUserId, $action);

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

    /**
     * Get single profile details (for the overlay screen)
     */
    public function getProfileDetails(Request $request, int $profileId): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            
            // Check if user has already swiped on this profile
            $hasSwipedOn = \App\Models\Swipe::where('user_id', $userId)
                ->where('target_user_id', $profileId)
                ->exists();

            if ($hasSwipedOn) {
                throw new \Exception('Profile no longer available');
            }

            $profile = \App\Models\User::where('id', $profileId)
                ->where('onboarding_completed', true)
                // ->whereNotNull('name')
                ->first();


                $selfProfile = User::find($userId);

                   if (!$profile) {
                throw new \Exception('Profile not found');
            }
                    $amount = 2.00;

//  return response()->json([
//                 'success' => true,
//                 'data' => $profile,
//             ]);
            $checkOlos = $selfProfile->hasEnoughOlos($amount);  
                 
            if (!$checkOlos) {
                throw new \Exception('Not enough Olos to check profile');
            }
                   $this->olosService->deductOlos(
                    $userId,
                    $amount,
                    OlosTransaction::TRANSACTION_TYPE_CHECK_PROFILE,
                    'User Profile Check'
                );
                $profile->refresh();
         

            // Get full profile details
            $interests = [];
            if (!empty($profile->interests)) {
                $interestRepository = app(\App\Repositories\Contracts\InterestRepositoryInterface::class);
                $interests = $interestRepository->findByIds($profile->interests);
            }

            $profileData = [
                'id' => $profile->id,
                'name' => $profile->name,
                'profile_photo' => $profile->profile_photo,
                'bio' => $profile->bio,
                'location' => [
                    'latitude' => $profile->latitude,
                    'longitude' => $profile->longitude,
                    'city' => $profile->city,
                    'state' => $profile->state,
                    'country' => $profile->country
                ],
                'interests' => $interests,
                'connection_type' => $profile->connection_type,
                'member_since' => $profile->created_at->diffForHumans(),
                'last_active' => $profile->updated_at->diffForHumans()
            ];

            return response()->json([
                'success' => true,
                'data' => $profileData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
<?php
// app/Services/SwipeService.php

namespace App\Services;

use App\Models\User;
use App\Models\Swipe;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\InterestRepositoryInterface;

class SwipeService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private InterestRepositoryInterface $interestRepository
    ) {}

    /**
     * Get profiles for swiping
     */
    public function getDiscoverProfiles(int $userId, int $limit = 10): array
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Get users that haven't been swiped on yet
        $swipedUserIds = Swipe::where('user_id', $userId)
            ->pluck('target_user_id')
            ->toArray();

        // Add current user to excluded list
        $excludedIds = array_merge($swipedUserIds, [$userId]);

        // Get potential matches
        $profiles = User::whereNotIn('id', $excludedIds)
            ->where('onboarding_completed', true)
            ->whereNotNull('name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        $formattedProfiles = [];
        foreach ($profiles as $profile) {
            $formattedProfiles[] = $this->formatProfileForSwipe($profile);
        }

        return [
            'profiles' => $formattedProfiles,
            'has_more' => count($formattedProfiles) === $limit,
            'total_available' => $this->getTotalAvailableProfiles($userId)
        ];
    }

    /**
     * Handle swipe action
     */
    public function handleSwipe(int $userId, int $targetUserId, string $action): array
    {
        // Validate action
        if (!in_array($action, ['like', 'pass', 'super_like'])) {
            throw new \Exception('Invalid swipe action');
        }

        // Check if already swiped
        $existingSwipe = Swipe::where('user_id', $userId)
            ->where('target_user_id', $targetUserId)
            ->first();

        if ($existingSwipe) {
            throw new \Exception('Already swiped on this profile');
        }

        // Create swipe record
        $swipe = Swipe::create([
            'user_id' => $userId,
            'target_user_id' => $targetUserId,
            'action' => $action
        ]);

        $isMatch = false;
        $matchData = null;

        // Check for match only if it's a like or super_like
        if (in_array($action, ['like', 'super_like'])) {
            $isMatch = $this->checkForMatch($userId, $targetUserId);
            
            if ($isMatch) {
                // Update both swipes to mark as match
                $swipe->update([
                    'is_match' => true,
                    'matched_at' => now()
                ]);

                // Update the other user's swipe too
                Swipe::where('user_id', $targetUserId)
                    ->where('target_user_id', $userId)
                    ->update([
                        'is_match' => true,
                        'matched_at' => now()
                    ]);

                $matchData = $this->getMatchData($targetUserId);
            }
        }

        return [
            'swipe_id' => $swipe->id,
            'action' => $action,
            'is_match' => $isMatch,
            'match_data' => $matchData,
            'swiped_at' => $swipe->created_at->toISOString()
        ];
    }

    /**
     * Check if swipe results in a match
     */
    private function checkForMatch(int $userId, int $targetUserId): bool
    {
        return Swipe::where('user_id', $targetUserId)
            ->where('target_user_id', $userId)
            ->whereIn('action', ['like', 'super_like'])
            ->exists();
    }

    /**
     * Format profile for swipe display
     */
    private function formatProfileForSwipe(User $user): array
    {
        // Get user interests
        $interests = [];
        if (!empty($user->interests)) {
            $interests = $this->interestRepository->findByIds($user->interests);
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'profile_photo' => $user->profile_photo,
            'bio' => $user->bio,
            'location' => [
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
                'city' => $user->city,
                'state' => $user->state,
                'country' => $user->country
            ],
            'interests' => array_slice($interests, 0, 3), // Show only first 3 interests
            'connection_type' => $user->connection_type,
            'member_since' => $user->created_at->diffForHumans()
        ];
    }

    /**
     * Get match data for successful match
     */
    private function getMatchData(int $targetUserId): array
    {
        $targetUser = $this->userRepository->findById($targetUserId);
        
        return [
            'matched_user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'profile_photo' => $targetUser->profile_photo,
                'bio' => $targetUser->bio
            ],
            'matched_at' => now()->toISOString(),
            'message' => "It's a Match! 🎉"
        ];
    }

    /**
     * Get total available profiles count
     */
    private function getTotalAvailableProfiles(int $userId): int
    {
        $swipedUserIds = Swipe::where('user_id', $userId)
            ->pluck('target_user_id')
            ->toArray();

        $excludedIds = array_merge($swipedUserIds, [$userId]);

        return User::whereNotIn('id', $excludedIds)
            ->where('onboarding_completed', true)
            ->whereNotNull('name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->count();
    }
}
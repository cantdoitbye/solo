<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\InterestRepositoryInterface;
use App\Repositories\Contracts\OnboardingQuestionRepositoryInterface;

class ProfileService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private InterestRepositoryInterface $interestRepository,
        private OnboardingQuestionRepositoryInterface $questionRepository
    ) {}

    public function getUserProfile(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Get user's interests with details
        $userInterests = [];
        if (!empty($user->interests)) {
            $userInterests = $this->interestRepository->findByIds($user->interests);
        }

        // Get questions with user's answers
        $questionsWithAnswers = [];
        if (!empty($user->introduction_answers)) {
            $questions = $this->questionRepository->getAllActiveQuestions();
            
            foreach ($questions as $question) {
                $questionKey = $question['question_key'];
                $questionsWithAnswers[] = [
                    'question_id' => $question['id'],
                    'question_key' => $questionKey,
                    'question_text' => $question['question_text'],
                    'answer' => $user->introduction_answers[$questionKey] ?? null,
                    'input_type' => $question['input_type'],
                    'max_length' => $question['max_length']
                ];
            }
        }

        // Discovery sources with labels
        $discoverySourcesLabels = [
            'instagram' => 'Instagram',
            'friend_family' => 'Friend/Family',
            'meetup' => 'Meetup',
            'google_search' => 'Google Search',
            'blog_article' => 'Blog/Article',
            'solo_member' => 'Solo Member',
            'local_event' => 'Local Event',
            'other' => 'Other'
        ];

        $discoverySourcesWithLabels = [];
        if (!empty($user->discovery_sources)) {
            foreach ($user->discovery_sources as $source) {
                $discoverySourcesWithLabels[] = [
                    'key' => $source,
                    'label' => $discoverySourcesLabels[$source] ?? ucfirst($source)
                ];
            }
        }

        return [
            'user' => [
                'id' => $user->id,
                'phone_number' => $user->phone_number,
                'country_code' => $user->country_code,
                'phone_verified' => !is_null($user->phone_verified_at),
                'phone_verified_at' => $user->phone_verified_at?->toISOString(),
                'onboarding_completed' => $user->onboarding_completed,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ],
            'preferences' => [
                'connection_type' => $user->connection_type,
                'connection_type_label' => $this->getConnectionTypeLabel($user->connection_type),
                'search_radius' => $user->search_radius,
                'search_radius_unit' => 'km'
            ],
            'location' => [
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
                'city' => $user->city,
                'state' => $user->state,
                'country' => $user->country
            ],
            'interests' => [
                'selected_interests' => $userInterests,
                'interests_count' => count($userInterests),
                'categories' => $this->groupInterestsByCategory($userInterests)
            ],
            'profile' => [
                'bio' => $user->bio,
                'bio_length' => strlen($user->bio ?? ''),
                'introduction_answers' => $questionsWithAnswers,
                'completed_questions' => count(array_filter($questionsWithAnswers, fn($q) => !empty($q['answer'])))
            ],
            'referral' => [
                'referral_code' => $user->referral_code,
                'used_referral_code' => $user->used_referral_code,
                'referral_points' => $user->referral_points,
                'referral_url' => config('app.url') . '/invite/' . $user->referral_code
            ],
            'discovery' => [
                'sources' => $discoverySourcesWithLabels,
                'sources_count' => count($discoverySourcesWithLabels)
            ],
            'stats' => [
                'profile_completion' => $this->calculateProfileCompletion($user),
                'member_since' => $user->created_at->diffForHumans(),
                'last_updated' => $user->updated_at->diffForHumans()
            ]
        ];
    }


      public function getUserProfile2(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

     

      

      

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'gender' => $user->gender,
                'age' => $user->age ?? null,
                'bio' => $user->bio,
                'olos_balance' => $user->getCurrentOlosBalance(),
                'profile_photo' => $user->profile_photo,

             
            ]
          
         
        
         
        ];
    }

    public function logoutUser(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Delete all tokens for the user
        $user->tokens()->delete();

        return [
            'user_id' => $userId,
            'message' => 'Logged out successfully from all devices',
            'logged_out_at' => now()->toISOString()
        ];
    }

    public function logoutFromCurrentDevice(int $userId, string $tokenId): array
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Delete only the current token
        $user->tokens()->where('id', $tokenId)->delete();

        return [
            'user_id' => $userId,
            'message' => 'Logged out successfully from current device',
            'logged_out_at' => now()->toISOString()
        ];
    }

    private function getConnectionTypeLabel(?string $connectionType): ?string
    {
        return match($connectionType) {
            'social' => 'Social Connections',
            'dating' => 'Dating',
            'both' => 'Social + Dating',
            default => null
        };
    }

    private function groupInterestsByCategory(array $interests): array
    {
        $categories = [];
        foreach ($interests as $interest) {
            $category = $interest['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }
            $categories[$category][] = $interest;
        }
        return $categories;
    }

    private function calculateProfileCompletion($user): array
    {
        $fields = [
            'phone_verified' => !is_null($user->phone_verified_at),
            'connection_type' => !is_null($user->connection_type),
            'interests' => !empty($user->interests),
            'bio' => !empty($user->bio),
            'introduction_answers' => !empty($user->introduction_answers),
            'discovery_sources' => !empty($user->discovery_sources)
        ];

        $completed = array_sum($fields);
        $total = count($fields);
        $percentage = round(($completed / $total) * 100);

        return [
            'percentage' => $percentage,
            'completed_fields' => $completed,
            'total_fields' => $total,
            'missing_fields' => array_keys(array_filter($fields, fn($v) => !$v))
        ];
    }

    public function updateUserProfile(int $userId, array $updateData): array
{
    $user = $this->userRepository->findById($userId);
    
    if (!$user) {
        throw new \Exception('User not found');
    }

    // Validate age if provided
    if (isset($updateData['age']) && ($updateData['age'] < 18 || $updateData['age'] > 100)) {
        throw new \Exception('Age must be between 18 and 100');
    }

    // Validate bio length if provided
    if (isset($updateData['bio']) && (strlen($updateData['bio']) > 500)) {
        throw new \Exception('Bio cannot exceed 500 characters');
    }

    // Validate name if provided
    if (isset($updateData['name']) && (empty(trim($updateData['name'])) || strlen($updateData['name']) > 255)) {
        throw new \Exception('Name is required and cannot exceed 255 characters');
    }

    // Update user data
    $updatedUser = $this->userRepository->update($userId, $updateData);

    // Return updated profile data
    return [
        'user_id' => $userId,
        'name' => $updatedUser->name,
        'age' => $updatedUser->age ?? null,
        'gender' => $updatedUser->gender ?? null,
        'bio' => $updatedUser->bio,
        'profile_photo' => $updatedUser->profile_photo,
        'profile_photo_url' => $updatedUser->profile_photo ? asset($updatedUser->profile_photo) : null,
        'updated_at' => $updatedUser->updated_at->toISOString(),
        'message' => 'Profile updated successfully'
    ];
}
}
<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\InterestRepositoryInterface;
use App\Repositories\Contracts\ReferralCodeRepositoryInterface;
use Illuminate\Support\Facades\DB;

class OnboardingService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private InterestRepositoryInterface $interestRepository,
        private ReferralCodeRepositoryInterface $referralCodeRepository
    ) {}

    public function initiatePhoneVerification(string $phoneNumber, string $countryCode): array
    {
        $user = $this->userRepository->findByPhoneNumber($phoneNumber, $countryCode);
        
        if (!$user) {
            $user = $this->userRepository->create([
                'phone_number' => $phoneNumber,
                'country_code' => $countryCode,
            ]);
        }
        
        $otp = $this->userRepository->generateOtp($user->id);
     
        
        return [
            'user_id' => $user->id,
            'otp' => config('app.debug') ? $otp : null, // Only return OTP in debug mode
            'message' => 'OTP sent successfully'
        ];
    }

  public function verifyOtp(int $userId, string $otp, string $fcmToken = null): array
{
    $isValid = $this->userRepository->verifyOtp($userId, $otp);
    
    if (!$isValid) {
        throw new \Exception('Invalid or expired OTP');
    }
    
    $user = $this->userRepository->findById($userId);
    
    // LOGIN FLOW - Check if user has completed onboarding
    if ($user->onboarding_completed) {
        // Generate API token for existing user
        $token = $user->createToken('solo-app-token')->plainTextToken;
        
          $this->userRepository->update($userId, [
            'fcm_token' => $fcmToken ? $fcmToken : $user->fcm_token
        ]);
        return [
            'user_id' => $userId,
            'verified' => true,
            'onboarding_completed' => true,
            'api_token' => $token,
            'token_type' => 'Bearer',
            'user_type' => 'existing',
            'message' => 'Welcome back! Logged in successfully.'
        ];
    }
    
    // REGISTRATION FLOW - Continue with onboarding
    if (!$user->referral_code) {
        $referralCode = $this->referralCodeRepository->createForUser($userId);
        $this->userRepository->update($userId, [
            'referral_code' => $referralCode->code
        ]);
    }
    
    return [
        'user_id' => $userId,
        'verified' => true,
        'onboarding_completed' => false,
        'user_type' => 'new',
        'message' => 'Phone number verified successfully. Continue with onboarding.'
    ];
}

    public function setConnectionType(int $userId, string $connectionType): array
    {
        $validTypes = ['social', 'dating', 'both'];
        
        if (!in_array($connectionType, $validTypes)) {
            throw new \Exception('Invalid connection type');
        }
        
        $user = $this->userRepository->update($userId, [
            'connection_type' => $connectionType
        ]);
        
        return [
            'user_id' => $userId,
            'connection_type' => $connectionType,
            'message' => 'Connection type updated successfully'
        ];
    }

    public function setSearchRadius(int $userId, int $radius): array
    {
        if ($radius < 1 || $radius > 500) {
            throw new \Exception('Search radius must be between 1 and 500 km');
        }
        
        $user = $this->userRepository->update($userId, [
            'search_radius' => $radius
        ]);
        
        return [
            'user_id' => $userId,
            'search_radius' => $radius,
            'message' => 'Search radius updated successfully'
        ];
    }

    public function applyReferralCode(int $userId, string $referralCode): array
    {
        $referral = $this->referralCodeRepository->findByCode($referralCode);
        
        if (!$referral) {
            throw new \Exception('Invalid referral code');
        }
        
        if (!$referral->canBeUsed()) {
            throw new \Exception('Referral code has expired or reached maximum uses');
        }
        
        if ($referral->user_id === $userId) {
            throw new \Exception('You cannot use your own referral code');
        }
        
        DB::transaction(function () use ($userId, $referral) {
            // Update user with referral code
            $this->userRepository->update($userId, [
                'used_referral_code' => $referral->code,
                'referral_points' => 50 // Bonus points for using referral
            ]);
            
            // Update referrer points
            $this->userRepository->update($referral->user_id, [
                'referral_points' => DB::raw('referral_points + 50')
            ]);
            
            // Increment referral usage
            $this->referralCodeRepository->incrementUsage($referral->id);
        });
        
        return [
            'user_id' => $userId,
            'referral_code' => $referralCode,
            'points_earned' => 50,
            'message' => 'Referral code applied successfully'
        ];
    }

    public function setDiscoverySources(int $userId, array $sources): array
    {
        $validSources = [
            'instagram', 'friend_family', 'meetup', 'google_search',
            'blog_article', 'solo_member', 'local_event', 'other'
        ];
        
        $invalidSources = array_diff($sources, $validSources);
        if (!empty($invalidSources)) {
            throw new \Exception('Invalid discovery sources: ' . implode(', ', $invalidSources));
        }
        
        $user = $this->userRepository->update($userId, [
            'discovery_sources' => $sources
        ]);
        
        return [
            'user_id' => $userId,
            'discovery_sources' => $sources,
            'message' => 'Discovery sources updated successfully'
        ];
    }

  public function setInterests(int $userId, array $interestIds): array
{
    // Allow empty interests array
    if (!empty($interestIds)) {
        $interests = $this->interestRepository->findByIds($interestIds);
        
        if (count($interests) !== count($interestIds)) {
            throw new \Exception('Some interest IDs are invalid');
        }
    } else {
        $interests = [];
    }
    
    $user = $this->userRepository->update($userId, [
        'interests' => $interestIds
    ]);
    
    return [
        'user_id' => $userId,
        'interests' => $interests,
        'message' => empty($interestIds) ? 'Interests cleared successfully' : 'Interests updated successfully'
    ];
}

    public function setIntroduction(int $userId, string $bio): array
    {
        if (strlen($bio) < 10 || strlen($bio) > 500) {
            throw new \Exception('Bio must be between 10 and 500 characters');
        }
        
        $user = $this->userRepository->update($userId, [
            'bio' => $bio
        ]);
        
        return [
            'user_id' => $userId,
            'bio' => $bio,
            'message' => 'Introduction updated successfully'
        ];
    }

  public function setIntroductionAnswers(int $userId, array $answers): array
{
    // Make answers optional - remove required field validation
    $validFields = [
        'what_i_care_about',
        'three_words_describe_me',
        'favorite_saturday',
        'travel_with_others',
        'class_to_take'
    ];
    
    // Filter answers to only include valid fields and remove empty values
    $filteredAnswers = [];
    foreach ($answers as $key => $value) {
        if (in_array($key, $validFields) && !empty(trim($value))) {
            $filteredAnswers[$key] = trim($value);
        }
    }
    
    $user = $this->userRepository->update($userId, [
        'introduction_answers' => $filteredAnswers
    ]);
    
    $answeredCount = count($filteredAnswers);
    $totalQuestions = count($validFields);
    
    return [
        'user_id' => $userId,
        'answers' => $filteredAnswers,
        'answered_questions' => $answeredCount,
        'total_questions' => $totalQuestions,
        'message' => $answeredCount > 0 
            ? "Introduction answers updated successfully ($answeredCount/$totalQuestions completed)" 
            : 'Introduction answers cleared'
    ];
}

    public function completeOnboarding(int $userId, string $fcmToken): array
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }
        
        // Validate required fields
        $requiredFields = [
            'phone_verified_at',
            // 'connection_type',
            'search_radius',
            // 'interests',
            'bio',
            // 'introduction_answers'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                throw new \Exception("Please complete all onboarding steps. Missing: $field");
            }
        }

        $token = $user->createToken('solo-app-token')->plainTextToken;

        
        $user = $this->userRepository->completeOnboarding($userId, $fcmToken);
        
        return [
            'user_id' => $userId,
            'onboarding_completed' => true,
            'referral_code' => $user->referral_code,
            'api_token' => $token,
            'token_type' => 'Bearer',
            'message' => 'Onboarding completed successfully! Welcome to Solo!'
        ];
    }
}
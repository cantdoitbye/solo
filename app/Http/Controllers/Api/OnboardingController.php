<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OnboardingService;
use App\Repositories\Contracts\InterestRepositoryInterface;
use App\Repositories\Contracts\OnboardingQuestionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    public function __construct(
        private OnboardingService $onboardingService,
        private InterestRepositoryInterface $interestRepository,
        private OnboardingQuestionRepositoryInterface $questionRepository
    ) {}

    public function initiatePhoneVerification(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'country_code' => 'required|string|max:5',
        ]);

        try {
            $result = $this->onboardingService->initiatePhoneVerification(
                $request->phone_number,
                $request->country_code
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

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'otp' => 'required|string|size:6',
        ]);

        try {
            $result = $this->onboardingService->verifyOtp(
                $request->user_id,
                $request->otp
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

    public function setConnectionType(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'connection_type' => ['required', Rule::in(['social', 'dating', 'both'])],
        ]);

        try {
            $result = $this->onboardingService->setConnectionType(
                $request->user_id,
                $request->connection_type
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

    public function setSearchRadius(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'radius' => 'required|integer|min:1|max:500',
        ]);

        try {
            $result = $this->onboardingService->setSearchRadius(
                $request->user_id,
                $request->radius
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

    public function applyReferralCode(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'referral_code' => 'required|string|max:10',
        ]);

        try {
            $result = $this->onboardingService->applyReferralCode(
                $request->user_id,
                $request->referral_code
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

    public function setDiscoverySources(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'sources' => 'required|array|min:1',
            'sources.*' => 'string|in:instagram,friend_family,meetup,google_search,blog_article,solo_member,local_event,other',
        ]);

        try {
            $result = $this->onboardingService->setDiscoverySources(
                $request->user_id,
                $request->sources
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

    public function getInterests(): JsonResponse
    {
        try {
            $interests = $this->interestRepository->getInterestsWithSuggestions();

            return response()->json([
                'success' => true,
                'data' => [
                    'title' => 'Interest',
                    'subtitle' => 'Add your interests so others can discover what matters to you',
                    'search_placeholder' => 'Search or browse your favorite interests...',
                    'dropdown_placeholder' => 'Select',
                    'suggestions_title' => 'Not sure what to pick?',
                    'suggestions_subtitle' => 'Here are some ideas to get you started:',
                    'interests' => $interests
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function setInterests(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'interest_ids' => 'nullable|array|max:10',
            'interest_ids.*' => 'integer|exists:interests,id',
        ]);

        try {
            $result = $this->onboardingService->setInterests(
                $request->user_id,
                $request->interest_ids
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

    public function setIntroduction(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'bio' => 'required|string|min:10|max:500',
        ]);

        try {
            $result = $this->onboardingService->setIntroduction(
                $request->user_id,
                $request->bio
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

    public function getIntroductionQuestions(): JsonResponse
    {
        try {
            $questions = $this->questionRepository->getAllActiveQuestions();

            return response()->json([
                'success' => true,
                'data' => [
                    'title' => "Let's get to know you better",
                    'subtitle' => 'These quick questions help others get to know you. Totally optional — but the more you share, the more your vibe shows.',
                    'questions' => $questions
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

   public function setIntroductionAnswers(Request $request): JsonResponse
{
    $questions = $this->questionRepository->getAllActiveQuestions();
    $validationRules = [
        'user_id' => 'required|integer|exists:users,id',
        'answers' => 'nullable|array', // Changed: required to nullable
    ];

    foreach ($questions as $question) {
        $rule = 'nullable|string|max:' . $question['max_length'];
        $validationRules['answers.' . $question['question_key']] = $rule;
    }

    $request->validate($validationRules);

    try {
        // Handle empty or null answers
        $answers = $request->answers ?? [];
        
        $result = $this->onboardingService->setIntroductionAnswers(
            $request->user_id,
            $answers
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

    public function completeOnboarding(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $result = $this->onboardingService->completeOnboarding($request->user_id);

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
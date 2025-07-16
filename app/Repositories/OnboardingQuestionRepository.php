<?php

namespace App\Repositories;

use App\Models\OnboardingQuestion;
use App\Repositories\Contracts\OnboardingQuestionRepositoryInterface;

class OnboardingQuestionRepository implements OnboardingQuestionRepositoryInterface
{
    public function getAllActiveQuestions(): array
    {
        return OnboardingQuestion::active()
                                 ->ordered()
                                 ->get()
                                 ->toArray();
    }

    public function getQuestionByKey(string $key): ?OnboardingQuestion
    {
        return OnboardingQuestion::where('question_key', $key)
                                 ->active()
                                 ->first();
    }
}
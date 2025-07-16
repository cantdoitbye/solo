<?php

namespace App\Repositories\Contracts;

interface OnboardingQuestionRepositoryInterface
{
    public function getAllActiveQuestions(): array;
    public function getQuestionByKey(string $key): ?object;
}
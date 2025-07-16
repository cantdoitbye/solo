<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\Contracts\InterestRepositoryInterface;
use App\Repositories\Contracts\OnboardingQuestionRepositoryInterface;
use App\Repositories\InterestRepository;
use App\Repositories\Contracts\ReferralCodeRepositoryInterface;
use App\Repositories\OnboardingQuestionRepository;
use App\Repositories\ReferralCodeRepository;


class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
         $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(InterestRepositoryInterface::class, InterestRepository::class);
        $this->app->bind(ReferralCodeRepositoryInterface::class, ReferralCodeRepository::class);
        $this->app->bind(OnboardingQuestionRepositoryInterface::class, OnboardingQuestionRepository::class);
      $this->app->singleton(\App\Services\OnboardingService::class);
        $this->app->singleton(\App\Services\ProfileService::class);
    }

    public function boot()
    {
        //
    }
}
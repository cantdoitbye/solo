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


use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\EventRepository;
use App\Repositories\Contracts\VenueTypeRepositoryInterface;
use App\Repositories\VenueTypeRepository;
use App\Repositories\Contracts\VenueCategoryRepositoryInterface;
use App\Repositories\VenueCategoryRepository;
use App\Repositories\Contracts\EventTagRepositoryInterface;
use App\Repositories\EventTagRepository;


class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
         $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(InterestRepositoryInterface::class, InterestRepository::class);
        $this->app->bind(ReferralCodeRepositoryInterface::class, ReferralCodeRepository::class);
        $this->app->bind(OnboardingQuestionRepositoryInterface::class, OnboardingQuestionRepository::class);

          $this->app->bind(EventRepositoryInterface::class, EventRepository::class);
        $this->app->bind(VenueTypeRepositoryInterface::class, VenueTypeRepository::class);
        $this->app->bind(VenueCategoryRepositoryInterface::class, VenueCategoryRepository::class);
        $this->app->bind(EventTagRepositoryInterface::class, EventTagRepository::class);
        

      $this->app->singleton(\App\Services\OnboardingService::class);
        $this->app->singleton(\App\Services\ProfileService::class);
                $this->app->singleton(\App\Services\EventService::class);
                        $this->app->singleton(\App\Services\EventMediaService::class);


    }

    public function boot()
    {
        //
    }
}
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\V1\ProfileController;

Route::prefix('onboarding')->group(function () {
    Route::post('phone/initiate', [OnboardingController::class, 'initiatePhoneVerification']);
    Route::post('phone/verify', [OnboardingController::class, 'verifyOtp']);
    Route::post('connection-type', [OnboardingController::class, 'setConnectionType']);
    Route::post('search-radius', [OnboardingController::class, 'setSearchRadius']);
    Route::post('referral-code', [OnboardingController::class, 'applyReferralCode']);
    Route::post('discovery-sources', [OnboardingController::class, 'setDiscoverySources']);
    Route::get('interests', [OnboardingController::class, 'getInterests']);
    Route::post('interests', [OnboardingController::class, 'setInterests']);
    Route::post('introduction', [OnboardingController::class, 'setIntroduction']);
        Route::get('introduction-questions', [OnboardingController::class, 'getIntroductionQuestions']);
    Route::post('introduction-answers', [OnboardingController::class, 'setIntroductionAnswers']);
    Route::post('complete', [OnboardingController::class, 'completeOnboarding']);
});


Route::middleware(['auth:sanctum', 'api.auth'])->group(function () {
    
     Route::get('profile', [ProfileController::class, 'getProfile']);
    Route::get('profile/stats', [ProfileController::class, 'getProfileStats']);
    
    // Authentication
    Route::post('logout', [ProfileController::class, 'logout']);
    Route::post('logout/current', [ProfileController::class, 'logoutCurrentDevice']);
    
    
});
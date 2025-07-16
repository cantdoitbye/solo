<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventDataController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\V1\EventDataController as V1EventDataController;
use App\Http\Controllers\Api\V1\EventMediaController;
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
Route::prefix('event-data')->group(function () {
    Route::get('venue-types', [V1EventDataController::class, 'getVenueTypes']);
    Route::get('venue-categories', [V1EventDataController::class, 'getVenueCategories']);
    Route::get('tags', [V1EventDataController::class, 'getEventTags']);
    Route::get('options', [V1EventDataController::class, 'getGenderOptions']);
});

    // Event media management (BEFORE event creation)
    Route::prefix('event-media')->group(function () {
        Route::post('upload-media', [EventMediaController::class, 'uploadMedia']);
        Route::post('upload-itinerary', [EventMediaController::class, 'uploadItinerary']);
        Route::get('session/{session_id}', [EventMediaController::class, 'getSessionMedia']);
        Route::delete('session/{session_id}', [EventMediaController::class, 'deleteSessionMedia']);
    });
    
    // Event management routes
    Route::prefix('events')->group(function () {
        Route::post('create', [EventController::class, 'create']);
        Route::get('my-events', [EventController::class, 'myEvents']);
        Route::get('{eventId}', [EventController::class, 'show']);
        Route::put('{eventId}', [EventController::class, 'update']);
        Route::post('{eventId}/publish', [EventController::class, 'publish']);
        Route::post('{eventId}/cancel', [EventController::class, 'cancel']);
    });
    
    
});
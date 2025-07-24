<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventDataController;
use App\Http\Controllers\Api\EventMediaController as ApiEventMediaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\V1\EventController as V1EventController;
use App\Http\Controllers\Api\V1\EventCreationController;
use App\Http\Controllers\Api\V1\EventDataController as V1EventDataController;
use App\Http\Controllers\Api\V1\EventJoinController;
use App\Http\Controllers\Api\V1\EventMediaController;
use App\Http\Controllers\Api\V1\HomeScreenController;
use App\Http\Controllers\Api\V1\OneOnOneDateController;
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

   Route::prefix('events')->name('events.')->group(function () {

    // Combined API for simple events (no media)
Route::post('create-bulk', [EventCreationController::class, 'createEventBulk'])
    ->name('create-bulk');

    Route::put('{eventId}/edit-bulk', [EventCreationController::class, 'createEventBulk'])
    ->name('edit-bulk');
        
        // Step 1: Basic Info (Create new or Edit existing)
        Route::post('basic-info', [EventCreationController::class, 'handleBasicInfo'])
            ->name('basic-info.create');
        Route::put('{eventId}/basic-info', [EventCreationController::class, 'handleBasicInfo'])
            ->name('basic-info.edit');
        
        // Step 2: Venue & Location
        Route::put('{eventId}/venue-location', [EventCreationController::class, 'handleVenueLocation'])
            ->name('venue-location');
        
        // Step 3: Date & Time
        Route::put('{eventId}/date-time', [EventCreationController::class, 'handleDateTime'])
            ->name('date-time');
        
        // Step 4: Attendees Setup
        Route::put('{eventId}/attendees-setup', [EventCreationController::class, 'handleAttendeesSetup'])
            ->name('attendees-setup');
        
        // Step 5: Token & Payment
        Route::put('{eventId}/token-payment', [EventCreationController::class, 'handleTokenPayment'])
            ->name('token-payment');
        
        // Step 6: Event History & Media
        Route::put('{eventId}/event-history', [EventCreationController::class, 'handleEventHistory'])
            ->name('event-history');
        
        // Step 7: Host Responsibilities
        Route::put('{eventId}/host-responsibilities', [EventCreationController::class, 'handleHostResponsibilities'])
            ->name('host-responsibilities');
        
        // Step 8: Preview
        Route::post('{eventId}/preview', [EventCreationController::class, 'generatePreview'])
            ->name('preview');
        
        // Final: Publish
        Route::put('{eventId}/publish', [EventCreationController::class, 'publishEvent'])
            ->name('publish');
        
        // Get Progress & Data
        Route::get('{eventId}/progress', [EventCreationController::class, 'getEventProgress'])
            ->name('progress');
        
        // Delete Draft
        Route::delete('{eventId}/draft', [EventCreationController::class, 'deleteDraftEvent'])
            ->name('delete-draft');
    });

    // ========================================
    // MEDIA UPLOAD ROUTES
    // ========================================
    
    Route::prefix('event-media')->name('event-media.')->group(function () {
        Route::post('upload-media', [ApiEventMediaController::class, 'uploadMedia'])
            ->name('upload-media');
        Route::post('upload-itinerary', [ApiEventMediaController::class, 'uploadItinerary'])
            ->name('upload-itinerary');
        Route::get('session/{sessionId}', [ApiEventMediaController::class, 'getSessionMedia'])
            ->name('session-media');
        Route::delete('session/{sessionId}', [ApiEventMediaController::class, 'deleteSessionMedia'])
            ->name('delete-session');
    });


     Route::prefix('home')->name('home.')->group(function () {
        Route::get('/', [HomeScreenController::class, 'getHomeScreen'])
            ->name('screen');
        Route::get('category/{categoryId}', [HomeScreenController::class, 'getEventsByCategoryId'])
            ->name('category-id')
            ->where('categoryId', '[0-9]+');
        Route::post('search', [HomeScreenController::class, 'searchEvents'])
            ->name('search');
        Route::post('filter', [HomeScreenController::class, 'applyFilters'])
            ->name('filter');
    });

        Route::get('/events/{eventId}/details', [EventJoinController::class, 'getEventDetails']);

      Route::post('/events/{eventId}/join', [EventJoinController::class, 'joinEvent']);
    Route::post('/events/{eventId}/cancel', [EventJoinController::class, 'cancelAttendance']);
    Route::get('/events/joined', [EventJoinController::class, 'getJoinedEvents']);
    
    // Olos Management Routes
    Route::prefix('olos')->group(function () {
        Route::get('/balance', [EventJoinController::class, 'getOlosBalance']);
        Route::get('/transactions', [EventJoinController::class, 'getOlosTransactions']);
        Route::post('/initialize', [EventJoinController::class, 'initializeOlos']);
    });


    Route::prefix('one-on-one-dates')->name('one-on-one-dates.')->group(function () {
    Route::post('/', [OneOnOneDateController::class, 'createOneOnOneDate'])
        ->name('create');

            Route::get('/{dateId}', [OneOnOneDateController::class, 'getOneOnOneDateById'])
        ->name('details');

          Route::post('/{dateId}/book', [OneOnOneDateController::class, 'bookOneOnOneDate'])
        ->name('book');
});
    
    
});
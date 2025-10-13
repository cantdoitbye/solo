<?php

use App\Http\Controllers\Api\V1\AccountSettingsController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventDataController;
use App\Http\Controllers\Api\EventMediaController as ApiEventMediaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\DmRequestController;
use App\Http\Controllers\Api\V1\EventController as V1EventController;
use App\Http\Controllers\Api\V1\EventCreationController;
use App\Http\Controllers\Api\V1\EventDataController as V1EventDataController;
use App\Http\Controllers\Api\V1\EventHistoryController;
use App\Http\Controllers\Api\V1\EventJoinController;
use App\Http\Controllers\Api\V1\EventMediaController;
use App\Http\Controllers\Api\V1\EventReviewController;
use App\Http\Controllers\Api\V1\HomeScreenController;
use App\Http\Controllers\Api\V1\MessageBoardController;
use App\Http\Controllers\Api\V1\MyEventsController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OneOnOneDateController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SuggestedLocationsController;
use App\Http\Controllers\Api\V1\SwipeController;
use App\Http\Controllers\Api\V1\UserHashController;

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

Route::middleware(['api.optional.auth'])->group(function () {
    
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
    
});


Route::middleware(['auth:sanctum', 'api.auth'])->group(function () {
    
     Route::get('profile', [ProfileController::class, 'getProfile']);
       Route::post('profile', [ProfileController::class, 'updateProfile']);
    Route::put('profile/location', [ProfileController::class, 'updateLocation']); // ADD THIS LINE
     Route::get('profile2', [ProfileController::class, 'getProfile2']);

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

        Route::post('create', [EventCreationController::class, 'createEvent'])->name('create');

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


    //  Route::prefix('home')->name('home.')->group(function () {
    //     Route::get('/', [HomeScreenController::class, 'getHomeScreen'])
    //         ->name('screen');
    //     Route::get('category/{categoryId}', [HomeScreenController::class, 'getEventsByCategoryId'])
    //         ->name('category-id')
    //         ->where('categoryId', '[0-9]+');
    //     Route::post('search', [HomeScreenController::class, 'searchEvents'])
    //         ->name('search');
    //     Route::post('filter', [HomeScreenController::class, 'applyFilters'])
    //         ->name('filter');
    // });

    //     Route::get('/events/{eventId}/details', [EventJoinController::class, 'getEventDetails']);

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

  Route::prefix('suggested-locations')->name('suggested-locations.')->group(function () {
        Route::get('/', [SuggestedLocationsController::class, 'index'])->name('index');
          Route::post('/', [SuggestedLocationsController::class, 'store'])->name('store');
        Route::get('/category/{category}', [SuggestedLocationsController::class, 'getByCategory'])->name('by-category');
    });


     Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('rooms', [ChatController::class, 'getChatRooms'])->name('rooms');
        Route::get('rooms/{chatRoomId}/messages', [ChatController::class, 'getChatMessages'])->name('messages');
        Route::post('rooms/{chatRoomId}/send', [ChatController::class, 'sendMessage'])->name('send');
        Route::post('personal/create', [ChatController::class, 'createPersonalChat'])->name('personal.create');
    });


      Route::prefix('swipe')->group(function () {
        Route::get('discover', [SwipeController::class, 'getProfiles']);
        Route::post('action', [SwipeController::class, 'swipe']);
        Route::get('profile/{profileId}', [SwipeController::class, 'getProfileDetails']);
    });

    Route::get('/events/history', [EventHistoryController::class, 'getEventHistory']);
    Route::get('/my-events', [MyEventsController::class, 'getMyEvents']);


Route::prefix('message-board')->name('message-board.')->group(function () {
    
    Route::get('posts', [MessageBoardController::class, 'getPosts'])->name('posts.index');
    Route::post('posts', [MessageBoardController::class, 'createPost'])->name('posts.create');
    
    // Tags - General routes
    Route::get('tags', [MessageBoardController::class, 'getTags'])->name('tags.index');
    
    // Posts - Specific routes with parameters (after general routes)
    Route::get('posts/{postId}', [MessageBoardController::class, 'getPost'])->name('posts.show');
    Route::put('posts/{postId}', [MessageBoardController::class, 'updatePost'])->name('posts.update');
    Route::delete('posts/{postId}', [MessageBoardController::class, 'deletePost'])->name('posts.delete');
    
    // Replies - Routes with parameters
    Route::post('posts/{postId}/replies', [MessageBoardController::class, 'createReply'])->name('replies.create');
    
    // Likes - General routes
    Route::post('likes', [MessageBoardController::class, 'toggleLike'])->name('likes.toggle');
    
});

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Submit a new review for an event
    Route::post('/events/{eventId}/reviews', [EventReviewController::class, 'submitReview']);
    
    // Get all reviews for a specific event (public - can be accessed by anyone)
    Route::get('/events/{eventId}/reviews', [EventReviewController::class, 'getEventReviews']);
    
    // Get user's own review for a specific event
    Route::get('/events/{eventId}/my-review', [EventReviewController::class, 'getMyReview']);
    
    // Update user's review for an event
    Route::put('/events/{eventId}/reviews', [EventReviewController::class, 'updateReview']);
    
    // Delete user's review for an event
    Route::delete('/events/{eventId}/reviews', [EventReviewController::class, 'deleteReview']);
});


Route::prefix('account-settings')->name('account-settings.')->middleware('auth:sanctum')->group(function () {
    
    // Get all account settings
    Route::get('/', [AccountSettingsController::class, 'getAccountSettings'])
        ->name('index');
    
    // Update account settings (single API for all settings)
    Route::put('/update', [AccountSettingsController::class, 'updateAccountSettings'])
        ->name('update');
    
    // Security related routes
    Route::get('security', [AccountSettingsController::class, 'getSecurityInfo'])
        ->name('security');
    
    Route::post('security/two-factor', [AccountSettingsController::class, 'toggleTwoFactor'])
        ->name('toggle-two-factor');
    
    // Login activity history
    Route::get('login-activity', [AccountSettingsController::class, 'getLoginActivityHistory'])
        ->name('login-activity');
    
    // Delete account
    Route::delete('/', [AccountSettingsController::class, 'deleteAccount'])
        ->name('delete');
});

    Route::post('feedback', [App\Http\Controllers\Api\V1\FeedbackController::class, 'store'])->name('feedback.store');



Route::get('test-message-board', function () {
    return response()->json(['message' => 'Message board route working']);
});

Route::prefix('dm-requests')->name('dm-requests.')->group(function () {
    Route::post('/', [DmRequestController::class, 'sendDmRequest'])->name('send');
    Route::put('{dmRequestId}/accept', [DmRequestController::class, 'acceptDmRequest'])->name('accept');
    Route::put('{dmRequestId}/reject', [DmRequestController::class, 'rejectDmRequest'])->name('reject');
    Route::get('pending', [DmRequestController::class, 'getPendingDmRequests'])->name('pending');
    Route::get('sent', [DmRequestController::class, 'getSentDmRequests'])->name('sent');
});

Route::get('users/{userId}/profile', [DmRequestController::class, 'getUserProfile'])->name('users.profile');

Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'getUserNotifications'])->name('index');
    // Route::get('summary', [NotificationController::class, 'getNotificationSummary'])->name('summary');
    // Route::get('dm-requests', [NotificationController::class, 'getDmRequestNotifications'])->name('dm-requests');
    Route::put('{notificationId}/mark-read', [NotificationController::class, 'markNotificationAsRead'])->name('mark-read');
    Route::put('mark-all-read', [NotificationController::class, 'markAllNotificationsAsRead'])->name('mark-all-read');
    // Route::delete('{notificationId}', [NotificationController::class, 'deleteNotification'])->name('delete');
});

Route::get('chat/{chatId}/attendees', [ChatController::class, 'getChatAttendees']);
    

   Route::get('/user/hash', [UserHashController::class, 'generateHash'])
            ->name('api.user.hash');
        Route::post('/user/verify-hash', [UserHashController::class, 'verifyHash'])
            ->name('api.user.verify-hash');
});
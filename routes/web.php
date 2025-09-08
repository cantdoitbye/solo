<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SuggestedLocationController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\EventController;
use Illuminate\Support\Facades\Route;

// Admin authentication routes (no middleware)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});

// Protected admin routes
Route::prefix('admin')->name('admin.')->middleware(['admin.auth'])->group(function () {
    
    // Dashboard
    Route::get('/', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::get('dashboard', [AuthController::class, 'dashboard']);
    
    // Users Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('{id}', [UserController::class, 'show'])->name('show');
        Route::post('{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('{id}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('{id}/restore', [UserController::class, 'restore'])->name('restore');
    });
    
    // Suggested Locations Management
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/', [SuggestedLocationController::class, 'index'])->name('index');
        Route::get('create', [SuggestedLocationController::class, 'create'])->name('create');
        Route::post('/', [SuggestedLocationController::class, 'store'])->name('store');
        Route::get('{id}', [SuggestedLocationController::class, 'show'])->name('show');
        Route::get('{id}/edit', [SuggestedLocationController::class, 'edit'])->name('edit');
        Route::put('{id}', [SuggestedLocationController::class, 'update'])->name('update');
        Route::delete('{id}', [SuggestedLocationController::class, 'destroy'])->name('destroy');
        Route::delete('{locationId}/images/{imageId}', [SuggestedLocationController::class, 'deleteImage'])->name('delete-image');
        Route::post('{locationId}/images/{imageId}/primary', [SuggestedLocationController::class, 'setPrimaryImage'])->name('set-primary-image');
    });
    
    // Banners Management
    Route::prefix('banners')->name('banners.')->group(function () {
        Route::get('/', [BannerController::class, 'index'])->name('index');
        Route::get('create', [BannerController::class, 'create'])->name('create');
        Route::post('/', [BannerController::class, 'store'])->name('store');
        Route::get('{id}', [BannerController::class, 'show'])->name('show');
        Route::get('{id}/edit', [BannerController::class, 'edit'])->name('edit');
        Route::put('{id}', [BannerController::class, 'update'])->name('update');
        Route::delete('{id}', [BannerController::class, 'destroy'])->name('destroy');
        Route::post('{id}/toggle-status', [BannerController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // Events Management
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::get('{id}', [EventController::class, 'show'])->name('show');
        Route::post('{id}/update-status', [EventController::class, 'updateStatus'])->name('update-status');
        Route::delete('{id}', [EventController::class, 'destroy'])->name('destroy');
        Route::post('bulk-action', [EventController::class, 'bulkAction'])->name('bulk-action');
        Route::get('{id}/attendees', [EventController::class, 'attendees'])->name('attendees');
    });


    Route::get('/test-google-maps', function() {
    $apiKey = config('services.google_maps.api_key');
    
    return response()->json([
        'api_key_configured' => !empty($apiKey),
        'api_key_length' => strlen($apiKey ?? ''),
        'api_key_starts_with' => substr($apiKey ?? '', 0, 10) . '...',
        'env_google_maps_key' => !empty(env('GOOGLE_MAPS_API_KEY')),
        'config_cache_cleared' => 'Run: php artisan config:clear',
    ]);
});

// routes/web.php
Route::get('/google-maps-test', function() {
    return view('admin.test.google-maps');
})->middleware('admin.auth');
    
});
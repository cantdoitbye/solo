<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    AuthController,
    DashboardController,
    SellerController,
    BuyerController,
    CourierController
};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    
    // Authentication routes (if using custom admin authentication)
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    
    // Protected Admin Routes
    Route::middleware(['auth:admin'])->group(function () {
        
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/', [DashboardController::class, 'index']);
        
        // Sellers Management
        Route::prefix('sellers')->name('sellers.')->group(function () {
            Route::get('/', [SellerController::class, 'index'])->name('index');
            Route::get('create', [SellerController::class, 'create'])->name('create');
            Route::post('/', [SellerController::class, 'store'])->name('store');
            Route::get('{id}', [SellerController::class, 'show'])->name('show');
            Route::get('{id}/edit', [SellerController::class, 'edit'])->name('edit');
            Route::put('{id}', [SellerController::class, 'update'])->name('update');
            Route::delete('{id}', [SellerController::class, 'destroy'])->name('destroy');
            
            // Additional seller routes
            Route::patch('{id}/status', [SellerController::class, 'updateStatus'])->name('update-status');
            Route::post('bulk-action', [SellerController::class, 'bulkAction'])->name('bulk-action');
            Route::get('export', [SellerController::class, 'export'])->name('export');
            Route::get('by-tea-grade', [SellerController::class, 'getByTeaGrade'])->name('by-tea-grade');
        });
        
        // Buyers Management
        Route::prefix('buyers')->name('buyers.')->group(function () {
            Route::get('/', [BuyerController::class, 'index'])->name('index');
            Route::get('create', [BuyerController::class, 'create'])->name('create');
            Route::post('/', [BuyerController::class, 'store'])->name('store');
            Route::get('{id}', [BuyerController::class, 'show'])->name('show');
            Route::get('{id}/edit', [BuyerController::class, 'edit'])->name('edit');
            Route::put('{id}', [BuyerController::class, 'update'])->name('update');
            Route::delete('{id}', [BuyerController::class, 'destroy'])->name('destroy');
            
            // Additional buyer routes
            Route::patch('{id}/status', [BuyerController::class, 'updateStatus'])->name('update-status');
            Route::post('bulk-action', [BuyerController::class, 'bulkAction'])->name('bulk-action');
            Route::get('export', [BuyerController::class, 'export'])->name('export');
            Route::get('by-type', [BuyerController::class, 'getByType'])->name('by-type');
            Route::get('by-tea-grade', [BuyerController::class, 'getByTeaGrade'])->name('by-tea-grade');
        });
        
        // Courier Services Management
        Route::prefix('couriers')->name('couriers.')->group(function () {
            Route::get('/', [CourierController::class, 'index'])->name('index');
            Route::get('create', [CourierController::class, 'create'])->name('create');
            Route::post('/', [CourierController::class, 'store'])->name('store');
            Route::get('{id}', [CourierController::class, 'show'])->name('show');
            Route::get('{id}/edit', [CourierController::class, 'edit'])->name('edit');
            Route::put('{id}', [CourierController::class, 'update'])->name('update');
            Route::delete('{id}', [CourierController::class, 'destroy'])->name('destroy');
            
            // Additional courier routes
            Route::patch('{id}/status', [CourierController::class, 'updateStatus'])->name('update-status');
            Route::post('{id}/test-api', [CourierController::class, 'testApi'])->name('test-api');
            Route::post('bulk-action', [CourierController::class, 'bulkAction'])->name('bulk-action');
            Route::get('export', [CourierController::class, 'export'])->name('export');
            Route::get('by-service-area', [CourierController::class, 'getByServiceArea'])->name('by-service-area');
            Route::post('{id}/tracking-url', [CourierController::class, 'generateTrackingUrl'])->name('tracking-url');
        });
        
        // Logistic Companies (Future implementation)
        Route::prefix('logistics')->name('logistics.')->group(function () {
            Route::get('/', function () {
                return view('admin.coming-soon', ['title' => 'Logistic Companies']);
            })->name('index');
        });
        
        // Contract Management (Future implementation)
        Route::prefix('contracts')->name('contracts.')->group(function () {
            Route::get('/', function () {
                return view('admin.coming-soon', ['title' => 'Contract Management']);
            })->name('index');
        });
        
        // Sample Management (Future implementation)
        Route::prefix('samples')->name('samples.')->group(function () {
            Route::get('/', function () {
                return view('admin.coming-soon', ['title' => 'Sample Management']);
            })->name('index');
            
            Route::get('receiving', function () {
                return view('admin.coming-soon', ['title' => 'Sample Receiving']);
            })->name('receiving');
            
            Route::get('evaluation', function () {
                return view('admin.coming-soon', ['title' => 'Sample Evaluation']);
            })->name('evaluation');
            
            Route::get('assignment', function () {
                return view('admin.coming-soon', ['title' => 'Buyer Assignment']);
            })->name('assignment');
        });
        
        // Dispatch Management (Future implementation)
        Route::prefix('dispatch')->name('dispatch.')->group(function () {
            Route::get('/', function () {
                return view('admin.coming-soon', ['title' => 'Dispatch Management']);
            })->name('index');
            
            Route::get('samples', function () {
                return view('admin.coming-soon', ['title' => 'Sample Dispatch']);
            })->name('samples');
            
            Route::get('feedback', function () {
                return view('admin.coming-soon', ['title' => 'Buyer Feedback']);
            })->name('feedback');
            
            Route::get('advice', function () {
                return view('admin.coming-soon', ['title' => 'Dispatch Advice']);
            })->name('advice');
        });
        
        // Reports (Future implementation)
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', function () {
                return view('admin.coming-soon', ['title' => 'Reports']);
            })->name('index');
            
            Route::get('sales', function () {
                return view('admin.coming-soon', ['title' => 'Sales Reports']);
            })->name('sales');
            
            Route::get('commission', function () {
                return view('admin.coming-soon', ['title' => 'Commission Reports']);
            })->name('commission');
        });
        
        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', function () {
                return view('admin.coming-soon', ['title' => 'Settings']);
            })->name('index');
        });
        
    });
});

/*
|--------------------------------------------------------------------------
| API Routes for AJAX calls
|--------------------------------------------------------------------------
*/

Route::prefix('api/admin')->name('api.admin.')->middleware(['auth:admin'])->group(function () {
    
    // Sellers API
    Route::prefix('sellers')->name('sellers.')->group(function () {
        Route::get('search', [SellerController::class, 'search'])->name('search');
        Route::get('select-options', [SellerController::class, 'getForSelect'])->name('select-options');
    });
    
    // Buyers API
    Route::prefix('buyers')->name('buyers.')->group(function () {
        Route::get('search', [BuyerController::class, 'search'])->name('search');
        Route::get('select-options', [BuyerController::class, 'getForSelect'])->name('select-options');
    });
    
    // Couriers API
    Route::prefix('couriers')->name('couriers.')->group(function () {
        Route::get('search', [CourierController::class, 'search'])->name('search');
        Route::get('select-options', [CourierController::class, 'getForSelect'])->name('select-options');
    });
    
});

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    return view('errors.404');
});
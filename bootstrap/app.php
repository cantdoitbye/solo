<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
         then: function () {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
          $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'api.auth' => \App\Http\Middleware\ApiAuth::class,
            'api.optional.auth' => \App\Http\Middleware\OptionalApiAuth::class,
                        'check.plan' => \App\Http\Middleware\CheckUserPlan::class,


        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

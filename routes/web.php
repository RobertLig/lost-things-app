<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Laravel\Fortify\RoutePath;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => [
            'localeSessionRedirect',
            'localizationRedirect',
            'localeViewPath',
            /* 'localize',
            'localeCookieRedirect', */
        ]
    ],
    function () {
        // Fortify auth routes
        //require base_path('vendor/laravel/fortify/routes/routes.php');

        Route::get('/', function () {
            return view('welcome');
        })->name('home');

        Route::view(LaravelLocalization::transRoute('routes.dashboard'), 'dashboard') //'dashboard', 'dashboard',
            ->middleware(['auth', 'verified'])
            ->name('dashboard');

        require __DIR__.'/settings.php';

        // Authentication...

        $enableViews = config('fortify.views', true);

        if ($enableViews) {

            Route::get(
                LaravelLocalization::transRoute('routes.login'),
                [AuthenticatedSessionController::class, 'create']
            )
            ->middleware(['guest:'.config('fortify.guard')])
            ->name('login');

        }

        $limiter = config('fortify.limiters.login');

        Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware(array_filter([
                'guest:'.config('fortify.guard'),
                $limiter ? 'throttle:'.$limiter : null,
            ]))->name('login.store');

    }
);


/* Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php'; */

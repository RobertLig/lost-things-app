<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => [
            'localeSessionRedirect',
            'localizationRedirect',
            'localeViewPath'
        ]
    ],
    function () {
        // Fortify auth routes
        require base_path('vendor/laravel/fortify/routes/routes.php');

        Route::get('/', function () {
            return view('welcome');
        })->name('home');

        Route::view('dashboard', 'dashboard')
            ->middleware(['auth', 'verified'])
            ->name('dashboard');

        require __DIR__.'/settings.php';

    }
);


/* Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php'; */

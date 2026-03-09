<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire(LaravelLocalization::transRoute('routes.settings.profile'), Profile::class)->name('profile.edit'); //settings/profile
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire(LaravelLocalization::transRoute('routes.settings.password'), Password::class)->name('user-password.edit'); //settings/password
    Route::livewire(LaravelLocalization::transRoute('routes.settings.appearance'), Appearance::class)->name('appearance.edit'); //settings/appearance

    Route::livewire('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class Language extends Component
{
    /* public function switch($locale)
    {
        return redirect(
            LaravelLocalization::getLocalizedURL(
                $locale,
                null,
                [],
                true
                //url()->current()
            )
        );
    } */

    public function render()
    {
        return view('livewire.settings.language', [
            'locales' => LaravelLocalization::getSupportedLocales(),
            'current' => app()->getLocale(),
        ]);
    }
}
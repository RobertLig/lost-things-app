<?php

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

function lroute($name, $parameters = [], $absolute = true)
{
    return LaravelLocalization::localizeURL(
        route($name, $parameters, $absolute)
    );
}

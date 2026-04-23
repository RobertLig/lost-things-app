<?php

return [
    'login' => 'login',
    'register' => 'register',
    'dashboard' => 'dashboard',
    'password.request' => 'forgot-password',
    'password.reset' => 'reset-password/{token}',
    //'verification.notice' => 'email/verify',
    //'verification.verify' => 'email/verify/{id}/{hash}',
    'verification' => [
        'notice' => 'email/verify',
        'verify' => 'email/verify/{id}/{hash}',
    ],
    'settings' => [
        'profile' => 'settings/profile',
        'password' => 'settings/password',
        'appearance' => 'settings/appearance',
        'language' => 'settings/language',
    ],
    'password' => [
        'confirm' => 'password/confirm',
    ],

    'items' => [
        'create' => 'items/create',
        'edit' => 'items/{item}/edit'
    ]
];
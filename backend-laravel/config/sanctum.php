<?php

use Laravel\Sanctum\Sanctum;

$statefulDomains = env('SANCTUM_STATEFUL_DOMAINS');

return [
    'stateful' => !empty($statefulDomains) 
        ? array_filter(array_map('trim', explode(',', $statefulDomains)))
        : [],

    'guard' => ['web'],

    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];

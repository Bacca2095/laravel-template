<?php

use Laravel\Sanctum\Sanctum;

return [
    'stateful' => array_filter(explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000'))),
    'guard' => ['sanctum'],
    'expiration' => env('SANCTUM_EXPIRATION'),
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
    'personal_access_token_model' => Sanctum::personalAccessTokenModel(),
];

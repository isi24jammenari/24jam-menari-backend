<?php

use Laravel\Sanctum\Sanctum;

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s,%s', // Perhatikan tambahan koma di tengah sini
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1,tenant.localhost:3000,tenant.24jammenariisisurakarta.com,24jammenariisisurakarta.com,admin.24jammenariisisurakarta.com,komunitas.24jammenariisisurakarta.com',
        Sanctum::currentApplicationUrlWithPort(),
    ))),

    'guard' => ['web'],
    'expiration' => null,
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        'https://admin.24jammenariisisurakarta.com',
        'https://24jammenariisisurakarta.com',
        'http://admin.24jammenariisisurakarta.com',
    ],
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // INI WAJIB TRUE AGAR SANCTUM & NEXT.JS BISA SALING LEMPAR COOKIE/TOKEN
    'supports_credentials' => true,
];
<?php
return [


    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', [])
        ? explode(',', env('CORS_ALLOWED_ORIGINS'))
        : [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:8000',
            'https://*.vercel.app',
            'https://*.onrender.com',
        ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
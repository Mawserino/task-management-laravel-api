<?php

return [
    'paths' => ['api/*', 'login', 'register', 'logout', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    
    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:8000',
        'https://task-management-frontend-seven-plum.vercel.app/',
        'https://*.vercel.app',
        'https://*.onrender.com',
    ],
    
    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.vercel\.app$/',
        '/^https:\/\/.*\.onrender\.com$/',
    ],
    
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],
    
    'exposed_headers' => [
        'Authorization',
        'X-Token',
    ],
    
    'max_age' => 86400, 
    
    'supports_credentials' => true, 
];
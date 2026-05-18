<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'register', 'logout'],
    
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    
    'allowed_origins' => [
        'https://task-management-react-e9ni.onrender.com',
        'https://task-management-frontend-9gehs6zug-mawserinos-projects.vercel.app',
        'https://*.vercel.app',
        'http://localhost:3000',
    ],
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-CSRF-TOKEN',
    ],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => false,
];
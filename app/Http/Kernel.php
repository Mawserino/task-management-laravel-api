
protected $routeMiddleware = [
    // ... existing middleware
    'auth.api' => \App\Http\Middleware\JWTMiddleware::class,
    'role' => \App\Http\Middleware\RoleMiddleware::class,
];
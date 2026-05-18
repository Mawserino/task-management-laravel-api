<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = [
            'https://task-management-frontend-seven-plum.vercel.app/',
            'https://*.vercel.app',
            'http://localhost:3000',
        ];
        
        $origin = $request->headers->get('ORIGIN');
        
        if (in_array($origin, $allowedOrigins) || str_ends_with($origin, '.vercel.app')) {
            return $next($request)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }
        
        return $next($request);
    }
}
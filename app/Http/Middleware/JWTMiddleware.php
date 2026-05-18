<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JWTMiddleware extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
            if (!$user->is_active) {
                return response()->json(['message' => 'Account is inactive'], 403);
            }
        } catch (\Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json(['message' => 'Token is invalid'], 401);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json(['message' => 'Token is expired'], 401);
            } else {
                return response()->json(['message' => 'Authorization token not found'], 401);
            }
        }

        return $next($request);
    }
}
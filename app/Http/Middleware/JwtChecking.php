<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtChecking
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Authorization Token not found'
                ], 401);
            }
        } catch (Exception $e) {
            if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Token is Invalid'
                ], 401);
            } elseif ($e instanceof TokenExpiredException) {
                try {
                    $newToken = JWTAuth::refresh();
                    return response()->json([
                        'status'    => false,
                        'message'   => 'Token is Expired',
                        'new_token' => $newToken
                    ], 403);
                } catch (Exception $refreshException) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Token Expired and cannot be refreshed, please login again',
                        'relogin' => true,
                    ], 403);
                }
            } else {
                return response()->json([
                    'status'  => false,
                    'message' => 'Authorization Token not found'
                ], 401);
            }
        }

        return $next($request);
    }
}

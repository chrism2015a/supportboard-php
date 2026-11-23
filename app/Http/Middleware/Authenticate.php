<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        // Check for token in session
        $token = session('auth_token');
        
        if (!$token) {
            return redirect()->route('login');
        }

        // Validate JWT token directly
        try {
            $user = JWTAuth::setToken($token)->authenticate();
            if (!$user) {
                session()->forget('auth_token');
                return redirect()->route('login');
            }
            $request->merge(['user' => $user]);
        } catch (JWTException $e) {
            // Token is invalid/expired
            session()->forget('auth_token');
            return redirect()->route('login')->with('error', 'Session expired. Please log in again.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Session::has('auth_token')) {
            return redirect()->route('dashboard');
        }
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            $response = Http::timeout(10)->post(env('APP_URL') . '/api/auth/login', [
                'email' => $request->email,
                'password' => $request->password,
            ]);

            if ($response->successful() && $response->json('data.token')) {
                Session::put('auth_token', $response->json('data.token'));
                Session::put('user', $response->json('data.user', []));
                return redirect()->route('dashboard');
            }

            return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Connection error: ' . $e->getMessage()])->withInput();
        }
    }

    public function logout()
    {
        if (Session::has('auth_token')) {
            try {
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . Session::get('auth_token'),
                ])->post(env('APP_URL') . '/api/auth/logout');
            } catch (\Exception $e) {
                // Ignore errors on logout
            }
        }

        Session::flush();
        return redirect()->route('login');
    }

    public function getToken()
    {
        return response()->json(['token' => Session::get('auth_token')]);
    }
}

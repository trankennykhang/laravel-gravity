<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show the gorgeous dark login gate.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        return view('gravity.login');
    }

    /**
     * Handle authentication attempts.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended('/')
                ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        return back()
            ->withErrors([
                'email' => 'The provided credentials do not match our records.'
            ])
            ->onlyInput('email');
    }

    /**
     * Handle session logouts.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Logged out successfully. Secure spatial link terminated.');
    }
}

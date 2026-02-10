<?php

/**
 * Poznámka: Tento controller bol vytvorený/upravený s pomocou AI nástrojov (GitHub Copilot).
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showForm()
    {
        // Render a login view; you can create resources/views/auth/login.blade.php
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $redirect = (string) $request->query('redirect', '');
            if ($redirect !== '') {
                return redirect()->to($redirect);
            }

            return redirect()->intended(route('home'));
        }

        return back()->withErrors([
            'email' => 'Login failed. Please check your credentials.',
        ])->onlyInput('email');
    }
}

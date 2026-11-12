<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create()
    {
        return response()->view('auth.login')->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['username' => 'required|string|max:255', 'password' => 'required|string']);
        $key = 'login:'.hash('sha256', mb_strtolower($data['username']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['username' => 'Demasiados intentos. Intenta de nuevo en '.RateLimiter::availableIn($key).' segundos.']);
        }
        if (! Auth::attempt($data + ['active' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['username' => 'Usuario o contraseña incorrectos.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->route(Auth::user()->isSuperAdmin() ? 'dashboard' : 'inscripciones.index');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

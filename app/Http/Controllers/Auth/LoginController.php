<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    protected int $maxAttempts = 5;
    protected int $decayMinutes = 15;

    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        if (session()->has('url.intended')) {
            session()->flash('error', 'You must be logged in to access that page.');
        }

        return view('index');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'notRobot' => 'accepted',
        ]);

        $this->ensureIsNotRateLimited($request);

        $fieldType = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $fieldType => $request->username,
            'password' => $request->password,
            'status'   => 'active',
        ];

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            RateLimiter::clear($this->throttleKey($request));

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($this->throttleKey($request), $this->decayMinutes * 60);

        $attempts  = RateLimiter::attempts($this->throttleKey($request));
        $remaining = $this->maxAttempts - $attempts;

        throw ValidationException::withMessages([
            'username' => [
                $remaining > 0
                    ? "The provided credentials are incorrect. {$remaining} attempt(s) remaining before lockout."
                    : 'Too many failed attempts. Please try again later.',
            ],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('index')->with('success', 'You have been logged out successfully.');
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));
        $minutes = ceil($seconds / 60);

        throw ValidationException::withMessages([
            'username' => [
                "Too many login attempts. Please try again in {$minutes} minute(s).",
            ],
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return 'login.' . strtolower($request->input('username')) . '.' . $request->ip();
    }
}
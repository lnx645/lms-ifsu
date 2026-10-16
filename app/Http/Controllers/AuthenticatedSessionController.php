<?php

namespace App\Http\Controllers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * The maximum number of failed login attempts per minute.
     */
    protected const MAX_ATTEMPTS = 5;

    /**
     * Show the login page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Login');
    }

    /**
     * Attempt to authenticate the user.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            app(RateLimiter::class)->hit($this->throttleKey($request));
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        app(RateLimiter::class)->clear($this->throttleKey($request));

        $request->session()->regenerate();

        return redirect()->intended(route('app.dashboard'));
    }

    /**
     * Destroy the authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }

    /**
     * Abort the request when the user has exhausted too many login attempts.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        $rateLimiter = app(RateLimiter::class);

        if (! $rateLimiter->tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = $rateLimiter->availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('email'))).'|'.$request->ip();
    }
}

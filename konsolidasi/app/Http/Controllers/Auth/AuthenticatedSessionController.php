<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * This function processes the user login attempt by first checking the rate limit for login attempts.
     * If the rate limit has been exceeded, a response indicating the wait time is returned.
     * If the login attempt passes rate-limiting, it proceeds to authenticate the user with the provided credentials.
     *
     * In the event of successful authentication, the session is regenerated and the user is redirected to
     * the intended URL (or the default dashboard if no intended URL is set).
     *
     * If the authentication fails due to invalid credentials, an error message is returned, indicating
     * that the username and password combination is incorrect.
     *
     * Additionally, this function logs events for both successful and failed login attempts,
     * and throttles excessive login attempts to prevent brute-force attacks.
     *
     * Responses:
     * - For a successful login, the user is redirected to the intended page.
     * - If rate limiting is triggered, the user is informed to try again after a specific period.
     * - If login fails due to invalid credentials, an error message is returned to the user.
     *
     * @param \App\Http\Requests\LoginRequest $request The incoming request object containing the login data.
     * @return \Illuminate\Http\Response|RedirectResponse A response or redirect based on the login outcome.
     */

    public function store(LoginRequest $request)
    {
        $key = 'login_attempts:' . $request->input('username') . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('Login throttled', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
                'available_in' => $seconds,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."
                ], 429);
            }

            throw new ThrottleRequestsException(
                "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."
            );
        }

        try {
            // Successful authentication
            $request->authenticate();
            RateLimiter::clear($key); // Clear rate limiter for the user/IP
            Log::info('Login successful', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
            ]);

            try {
                $request->session()->regenerate(); // Regenerate session to prevent session fixation
            } catch (\Exception $e) {
                Log::error('Session regeneration failed', [
                    'username' => $request->input('username'),
                    'ip' => $request->ip(),
                    'message' => $e->getMessage(),
                ]);
            }

            $intendedUrl = $request->session()->pull('url.intended', route('dashboard'));

            if ($request->expectsJson()) {
                return response()->json(['redirect' => $intendedUrl], 200);
            }

            return redirect()->to($intendedUrl);
        } catch (\Exception $e) {
            // Failed authentication or unexpected server error
            RateLimiter::hit($key, 300); // Increment the failed attempt count
            Log::warning('Login failed', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
                'message' => $e->getMessage(),
            ]);

            // Invalid credentials
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Kombinasi antara username dan password salah.'
                ], 401);
            }

            // Throw validation exception with error message
            throw ValidationException::withMessages([
                'username' => ['Kombinasi antara username dan password salah.'],
            ]);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

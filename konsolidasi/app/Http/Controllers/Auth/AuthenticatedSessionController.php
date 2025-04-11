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
     */

    public function store(LoginRequest $request): RedirectResponse
    {
        $key = 'login_attempts:' . $request->input('username') . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('Login throttled', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
                'available_in' => $seconds,
            ]);
            throw new ThrottleRequestsException(
                "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."
            );
        }

        try {
            $request->authenticate();
            Log::info('Login successful', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            RateLimiter::hit($key, 60);
            Log::warning('Login failed', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
                'message' => $e->getMessage(),
            ]);
            throw ValidationException::withMessages([
                'username' => ['Kombinasi antara username dan password salah.'],
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $intendedUrl = $request->session()->pull('url.intended', route('dashboard'));
        Log::info('Redirecting after login', [
            'intended_url' => $intendedUrl,
            'dashboard_route' => route('dashboard'),
            'session_id' => $request->session()->getId(),
            'user_id' => Auth::id(),
        ]);

        return redirect()->to($intendedUrl);
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

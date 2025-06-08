<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
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

    public function store(LoginRequest $request)
    {
        try {
            // Authenticate user (rate limiting is handled by LoginRequest)
            $request->authenticate();
            Log::info('Login successful', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
            ]);

            $request->session()->regenerate(); // Regenerate session to prevent session fixation
            $intendedUrl = $request->session()->pull('url.intended', route('dashboard'));

            if ($request->expectsJson()) {
                return response()->json(['redirect' => $intendedUrl], 200);
            }

            return redirect()->to($intendedUrl);
        } catch (ValidationException $e) {
            // Failed authentication (invalid credentials or rate limit exceeded)
            Log::warning('Login failed', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
                'message' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Kombinasi antara username dan password salah.'
                ], 401);
            }

            throw $e; // Re-throw the ValidationException for non-JSON requests
        } catch (\Exception $e) {
            // Unexpected server error
            Log::error('Login error', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
                'message' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Terjadi kesalahan server.'
                ], 500);
            }

            throw ValidationException::withMessages([
                'username' => ['Terjadi kesalahan saat login. Silakan coba lagi.'],
            ]);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        Log::info('Attempting to log out user', [
            'user_id' => $userId,
            'timestamp' => now(),
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($userId) {
            $cacheKey = 'user_' . $userId;
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
                Log::info('Removed user cache on logout', [
                    'user_id' => $userId,
                    'cache_key' => $cacheKey,
                    'timestamp' => now(),
                ]);
            }
        }

        return redirect('/');
    }
}

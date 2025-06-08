<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;
use JKD\SSO\Client\Provider\Keycloak;
use Exception;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;


class SSOController extends Controller
{
    protected $authController;

    public function __construct(AuthenticatedSessionController $authController)
    {
        $this->authController = $authController;
    }

    protected function getProvider()
    {
        return new Keycloak(config('sso'));
    }

    public function redirectToSSO()
    {
        $provider = $this->getProvider();
        $authUrl = $provider->getAuthorizationUrl();
        Session::put('oauth2state', $provider->getState());
        return redirect($authUrl);
    }

    public function handleSSOCallback(Request $request)
    {
        $provider = $this->getProvider();

        // Check for 'code' and validate state
        if (!$request->has('code') || $request->get('state') !== Session::get('oauth2state')) {
            Session::forget('oauth2state');
            return abort(403, 'Invalid state');
        }

        try {
            // Exchange code for access token
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $request->get('code')
            ]);

            // Get user details
            $ssoUser = $provider->getResourceOwner($token);

            // Dump the user data to inspect it
            dd($ssoUser);

            // Get the username from SSO
            $username = $ssoUser->getUsername();

            // Check if the username exists in the database
            $user = User::where('username', $username)->first();

            // Save user data to session or database
            // Session::put('sso_user', [
            //     'name' => $ssoUser->getName(),
            //     'username' => $ssoUser->getUsername(),
            // ]);

            if (!$user) {
                // Username not found in the database, reject the login
                Session::forget('oauth2state');
                return redirect()->route('login')->withErrors([
                    'sso' => 'Akun SSO tidak terdaftar di aplikasi ini. Silakan hubungi administrator.'
                ]);
            }

            // Log the user in
            Auth::login($user);

            // Regenerate session to prevent session fixation <- what
            $request->session()->regenerate();

            // Clear the OAuth state
            Session::forget('oauth2state');

            // Redirect to a protected page
            return redirect()->intended(route('dashboard'));
        } catch (Exception $e) {
            // Dump the exception if something goes wrong
            dd($e);
            return abort(500, 'SSO Error: ' . $e->getMessage());
        }
    }

    public function logoutSSO(Request $request)
    {
        $provider = $this->getProvider();

        $userId = Auth::id();
        Log::info('Attempting SSO logout', [
            'user_id' => $userId,
            'timestamp' => now(),
        ]);

        // Call destroy for local logout and cache clearing
        $this->authController->destroy($request);
        // Session::forget('sso_user'); // Clear user session

        return redirect($provider->getLogoutUrl());
    }
}

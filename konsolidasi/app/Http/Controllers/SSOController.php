<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JKD\SSO\Client\Provider\Keycloak;
use Exception;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SSOController extends Controller
{
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

            // Save user data to session or database
            Session::put('sso_user', [
                'name' => $ssoUser->getName(),
                'email' => $ssoUser->getEmail(),
                'username' => $ssoUser->getUsername(),
                'nip' => $ssoUser->getNip(),
            ]);

            // Redirect to a protected page
            return redirect('/dashboard');
        } catch (Exception $e) {
            // Dump the exception if something goes wrong
            dd($e);
            return abort(500, 'SSO Error: ' . $e->getMessage());
        }
    }

    public function logoutSSO()
    {
        $provider = $this->getProvider();
        Session::forget('sso_user'); // Clear user session
        return redirect($provider->getLogoutUrl());
    }
}

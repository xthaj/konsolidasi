<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;
use JKD\SSO\Client\Provider\Keycloak;
use Exception;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
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

    public function lookupPegawai(Request $request): JsonResponse
    {
        // Get username from 'username' or 'query' parameter
        $username = $request->input('username', $request->input('query'));
        if (!$username) {
            return response()->json([
                'message' => 'Username wajib diisi'
            ], 400);
        }

        $base_url = rtrim(config('sso.authServerUrl'), '/') . '/auth/';
        $realm = config('sso.realm');
        $url_token = $base_url . "realms/{$realm}/protocol/openid-connect/token";
        $url_api = $base_url . "realms/{$realm}/api-pegawai";
        $client_id = config('sso.clientId');
        $client_secret = config('sso.clientSecret');
        $query_search = "/search/username/{$username}";

        try {
            // Mendapatkan access token
            $response_token = Http::asForm()->withBasicAuth($client_id, $client_secret)
                ->post($url_token, ['grant_type' => 'client_credentials'])
                ->throw(function ($response) {
                    throw new Exception("Gagal mendapatkan token: " . $response->body());
                });
            $access_token = $response_token->json('access_token');

            // Mengambil data pengguna
            $response = Http::withToken($access_token)
                ->get($url_api . $query_search)
                ->throw(function ($response) {
                    throw new Exception("Gagal mengambil data pengguna: " . $response->body());
                });
            $json = $response->json();

            if (!empty($json) && count($json) == 1) {
                return response()->json([
                    'username' => $json[0]['username'],
                    'nama_lengkap' => $json[0]['attributes']['attribute-nama'][0],
                ], 200);
            }

            return response()->json([
                'message' => 'Pencarian user tidak ditemukan atau lebih dari 1 user ditemukan'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }
}

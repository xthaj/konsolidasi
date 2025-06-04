<?php

return [
    'authServerUrl' => env('SSO_AUTH_SERVER', 'https://sso.bps.go.id'),
    'realm' => env('SSO_REALM', 'pegawai-bps'),
    'clientId' => env('SSO_CLIENT_ID'),
    'clientSecret' => env('SSO_CLIENT_SECRET'),
    'redirectUri' => env('SSO_REDIRECT_URI'),
];

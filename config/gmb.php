<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OAuth 2.0 Client Secrets
    | storage/app/google/gmb_client_secrets.json
    |--------------------------------------------------------------------------
    */
    'client_secrets_path' => storage_path('app/google/gmb_client_secrets.json'),

    /*
    |--------------------------------------------------------------------------
    | Service Account
    | storage/app/google/gmb_service_account.json
    |--------------------------------------------------------------------------
    */
    'service_account_path' => storage_path('app/google/gmb_service_account.json'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Scopes
    |--------------------------------------------------------------------------
    */
    'scopes' => [
        'https://www.googleapis.com/auth/business.manage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirect URI
    |--------------------------------------------------------------------------
    */
    'redirect_uri' => 'http://localhost:8000/gmb/gauth',

    /*
    |--------------------------------------------------------------------------
    | GMB Team ID
    |--------------------------------------------------------------------------
    */
    'team_id' => 2,

    'gemini_api_key' => env('GEMINI_API_KEY'),

    'tl_email' => env('GMB_TL_EMAIL', 'shivam@ichelonconsulting.com'),
    
    'google_places_api_key' => env('GMB_PLACES_API_KEY'),

    'serpapi_key' => env('SERPAPI_KEY'),


];
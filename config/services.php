<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

         'firebase' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'fcm_endpoint' => env('FCM_ENDPOINT', 'https://fcm.googleapis.com/v1/projects/' . env('FCM_PROJECT_ID') . '/messages:send'),
        'service_account_path' => storage_path('app/private/service_account.json'),
        'batch_size' => env('FCM_BATCH_SIZE', 100),
        'retry_attempts' => env('FCM_RETRY_ATTEMPTS', 3),
        'timeout' => env('FCM_TIMEOUT', 30),
         ],
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

];

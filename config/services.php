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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    /*
    |--------------------------------------------------------------------------
    | FundiConnect Integration Services
    |--------------------------------------------------------------------------
    */

    'mpesa' => [
        'environment'       => env('MPESA_ENVIRONMENT', 'sandbox'),
        'consumer_key'      => env('MPESA_CONSUMER_KEY'),
        'consumer_secret'   => env('MPESA_CONSUMER_SECRET'),
        'shortcode'         => env('MPESA_SHORTCODE'),
        'passkey'           => env('MPESA_PASSKEY'),
        'initiator_name'    => env('MPESA_INITIATOR_NAME'),
        'initiator_password' => env('MPESA_INITIATOR_PASSWORD'),
        'callback_url'      => env('MPESA_CALLBACK_URL', env('APP_URL') . '/api/webhooks/mpesa/callback'),
        'b2c_result_url'    => env('MPESA_B2C_RESULT_URL', env('APP_URL') . '/api/webhooks/mpesa/b2c/result'),
        'b2c_timeout_url'   => env('MPESA_B2C_TIMEOUT_URL', env('APP_URL') . '/api/webhooks/mpesa/b2c/timeout'),
        'sandbox_url'       => 'https://sandbox.safaricom.co.ke',
        'production_url'    => 'https://api.safaricom.co.ke',
    ],

    'africastalking' => [
        'username' => env('AT_USERNAME', 'sandbox'),
        'api_key'  => env('AT_API_KEY'),
        'sender'   => env('AT_SENDER_ID', 'FundiConnect'),
    ],

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-credentials.json')),
        'project_id'  => env('FIREBASE_PROJECT_ID'),
    ],

    'airtel_money' => [
        'client_id'     => env('AIRTEL_CLIENT_ID'),
        'client_secret' => env('AIRTEL_CLIENT_SECRET'),
        'environment'   => env('AIRTEL_ENVIRONMENT', 'sandbox'),
    ],

];

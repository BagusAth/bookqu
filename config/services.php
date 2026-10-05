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

    'singapay' => [
        'base_url' => env('SINGAPAY_BASE_URL', 'https://payment-b2b.singapay.id'),
        'client_id' => env('SINGAPAY_CLIENT_ID'),
        'client_secret' => env('SINGAPAY_CLIENT_SECRET'),
        'api_key' => env('SINGAPAY_API_KEY'),
        'account_id' => env('SINGAPAY_ACCOUNT_ID'),
        'expiry_minutes' => (int) env('SINGAPAY_PAYMENT_EXPIRY_MINUTES', 15),
        'webhook_tolerance_seconds' => (int) env('SINGAPAY_WEBHOOK_TOLERANCE_SECONDS', 300),
        'customer_pays_fee' => (bool) env('SINGAPAY_CUSTOMER_PAYS_FEE', false),
    ],

];

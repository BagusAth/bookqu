<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway Driver
    |--------------------------------------------------------------------------
    |
    | Menentukan provider payment gateway aktif untuk seluruh sistem BookQu
    | (Customer Booking & Owner Subscription Checkout).
    |
    | Didukung: "singapay", "midtrans"
    |
    */

    'default' => env('PAYMENT_GATEWAY_DRIVER', 'singapay'),

    /*
    |--------------------------------------------------------------------------
    | Payment Drivers Configuration
    |--------------------------------------------------------------------------
    */

    'drivers' => [

        'singapay' => [
            'base_url'                  => env('SINGAPAY_BASE_URL', 'https://payment-b2b.singapay.id'),
            'client_id'                 => env('SINGAPAY_CLIENT_ID'),
            'client_secret'             => env('SINGAPAY_CLIENT_SECRET'),
            'api_key'                   => env('SINGAPAY_API_KEY'),
            'hmac_validation_key'       => env('SINGAPAY_HMAC_VALIDATION_KEY'),
            'account_id'                => env('SINGAPAY_ACCOUNT_ID'),
            'expiry_minutes'            => (int) env('SINGAPAY_PAYMENT_EXPIRY_MINUTES', 15),
            'subscription_expiry_hours' => (int) env('SINGAPAY_SUBSCRIPTION_EXPIRY_HOURS', 1),
            'webhook_tolerance_seconds' => (int) env('SINGAPAY_WEBHOOK_TOLERANCE_SECONDS', 300),
            'customer_pays_fee'         => (bool) env('SINGAPAY_CUSTOMER_PAYS_FEE', false),
        ],

        'midtrans' => [
            'merchant_id'   => env('MIDTRANS_MERCHANT_ID'),
            'client_key'    => env('MIDTRANS_CLIENT_KEY'),
            'server_key'    => env('MIDTRANS_SERVER_KEY'),
            'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
            'is_sanitized'  => (bool) env('MIDTRANS_IS_SANITIZED', true),
            'is_3ds'        => (bool) env('MIDTRANS_IS_3DS', true),
            'snap_url'      => env('MIDTRANS_IS_PRODUCTION', false)
                ? 'https://app.midtrans.com/snap/snap.js'
                : 'https://app.sandbox.midtrans.com/snap/snap.js',
            'api_url'       => env('MIDTRANS_IS_PRODUCTION', false)
                ? 'https://api.midtrans.com'
                : 'https://api.sandbox.midtrans.com',
        ],

    ],

];

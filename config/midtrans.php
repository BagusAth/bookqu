<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi Midtrans payment gateway.
    | Digunakan untuk pembayaran subscription owner ke platform BookQu.
    |
    */

    'merchant_id'   => env('MIDTRANS_MERCHANT_ID'),
    'client_key'    => env('MIDTRANS_CLIENT_KEY'),
    'server_key'    => env('MIDTRANS_SERVER_KEY'),
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized'  => (bool) env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds'        => (bool) env('MIDTRANS_IS_3DS', true),

    // Snap URL berdasarkan environment
    'snap_url' => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js',

    // API URL berdasarkan environment
    'api_url' => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://api.midtrans.com'
        : 'https://api.sandbox.midtrans.com',

];

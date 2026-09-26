<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scalev Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi Scalev payment gateway.
    | Konfigurasi ini tidak menghapus midtrans, sehingga keduanya bisa
    | berjalan berdampingan (misal untuk transisi atau multi-gateway).
    |
    */

    'api_key'       => env('SCALEV_API_KEY'),
    'secret_key'    => env('SCALEV_SECRET_KEY'), // (Bila ada)
    'is_production' => (bool) env('SCALEV_IS_PRODUCTION', false),

    // URL API Scalev
    'api_url' => env('SCALEV_API_BASE_URL', 'https://api.scalev.id/v1'),

];

<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    \App\Models\Booking::create([
        'idtenant' => 2,
        'idlayanan' => 2,
        'idschedule' => 843,
        'namapelanggan' => 'test',
        'nomorhp' => '081234567890',
        'email' => 'test@test.com',
        'tanggalbooking' => '2026-09-23',
        'jam' => '15:00:00',
        'status' => 'pending',
        'idpayment' => 554,
        'booking_code' => 'TEST1234'
    ]);
    echo "SUCCESS\n";
} catch (\Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p1612 = App\Models\Payment::find(1612);
$p1610 = App\Models\Payment::find(1610);
$b1612 = App\Models\Booking::where('idpayment', 1612)->first();
$b1610 = App\Models\Booking::where('idpayment', 1610)->first();

echo "Payment 1612: " . ($p1612 ? $p1612->status : 'NOT FOUND') . "\n";
echo "Booking 1612: " . ($b1612 ? $b1612->status : 'NOT FOUND') . "\n";
echo "Payment 1610: " . ($p1610 ? $p1610->status : 'NOT FOUND') . "\n";
echo "Booking 1610: " . ($b1610 ? $b1610->status : 'NOT FOUND') . "\n";

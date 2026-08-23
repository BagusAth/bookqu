<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$paymentService = app(\App\Services\MidtransPaymentService::class);
$p1612 = App\Models\Payment::find(1612);
$p1610 = App\Models\Payment::find(1610);

if ($p1612) {
    echo "1612 Before Verify: {$p1612->status}\n";
    $res12 = $paymentService->verifyAndSync($p1612);
    echo "1612 After Verify: " . json_encode($res12) . "\n";
}

if ($p1610) {
    echo "1610 Before Verify: {$p1610->status}\n";
    $res10 = $paymentService->verifyAndSync($p1610);
    echo "1610 After Verify: " . json_encode($res10) . "\n";
}

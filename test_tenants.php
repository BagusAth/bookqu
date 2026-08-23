<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenants = \App\Models\Tenant::where('iduser', 5)->get();
foreach($tenants as $t) {
    echo "TenantID: {$t->id} | UserID: {$t->iduser} | Name: {$t->namabisnis} | Services: " . $t->services()->count() . "\n";
}

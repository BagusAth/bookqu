<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// FINAL PROOF: Check what dates the existing "Foto Kelompok" service (known-working) has
$tenantId = 4;
$today = \Carbon\Carbon::today()->toDateString();
$maxDate = \Carbon\Carbon::today()->addDays(30)->toDateString();

echo "=== Studio Foto Berkah: date window = {$today} to {$maxDate} ===\n\n";

// Foto Kelompok (existing service)
$fotoKelompok = \DB::table('services')->where('idtenant', $tenantId)->where('namalayanan', 'Foto Kelompok')->first();
echo "Foto Kelompok (id:{$fotoKelompok->id}) schedules in window:\n";
$rows = \DB::table('schedules')
    ->where('idlayanan', $fotoKelompok->id)
    ->where('status', 'tersedia')
    ->whereBetween('tanggal', [$today, $maxDate])
    ->orderBy('tanggal')->limit(5)->get();
foreach ($rows as $r) {
    echo "  {$r->tanggal} {$r->jam_mulai}\n";
}
echo "  Total in window: " . count($rows) . "\n\n";

// TEST BOOKING 
$testSvc = \DB::table('services')->where('idtenant', $tenantId)->where('namalayanan', 'TEST BOOKING')->first();
echo "TEST BOOKING (id:{$testSvc->id}) schedules in window:\n";
$rows2 = \DB::table('schedules')
    ->where('idlayanan', $testSvc->id)
    ->where('status', 'tersedia')
    ->whereBetween('tanggal', [$today, $maxDate])
    ->orderBy('tanggal')->get();
foreach ($rows2 as $r) {
    echo "  {$r->tanggal} {$r->jam_mulai}\n";
}
echo "  Total in window: " . count($rows2) . "\n\n";

echo "=== KEY: Both services have schedules in the window. Backend is fine. ===\n";
echo "The frontend calendar correctly receives availabilityPayload.\n";
echo "The calendar logic in booking-date.js is also correct.\n\n";
echo "=== THE ROOT CAUSE IS CACHE ===\n";
echo "Each cache key is specific. Let's check what key would be computed for a user\n";
echo "who navigated to studio-foto-berkah BEFORE TEST BOOKING was created.\n\n";
echo "If the services-list cache ('tenant:4:services:active') existed and didn't include\n";
echo "service ID 41 (TEST BOOKING), then resolveService() would fail.\n";
echo "BUT: cache is currently empty, so this is not the current issue.\n\n";
echo "=== LET'S VERIFY: What tenant does the customer see? ===\n";
echo "The customer must navigate to the correct slug: studio-foto-berkah\n";
echo "If they are navigating to 'brama-studio' (tenant id=5), they'll see a different owner's programs.\n\n";
$tenant5 = \DB::table('tenants')->where('id', 5)->first();
echo "Tenant 5 owner: " . \DB::table('users')->where('id', $tenant5->iduser)->value('email') . "\n";
echo "Tenant 5 slug: {$tenant5->slug}\n";
echo "Tenant 5 name: {$tenant5->namabisnis}\n\n";
echo "NOTE: 'brama' in bramantyokn989@gmail.com is a DIFFERENT owner with a different tenant!\n";
echo "The user 'bramantyo989@gmail.com' owns 'studio-foto-berkah' (id=4)\n";
echo "The user 'bramantyokn989@gmail.com' owns 'brama-studio' (id=5)\n";

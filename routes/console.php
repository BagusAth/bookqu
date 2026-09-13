<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:check-expired-subscriptions')->daily();

// Run every 15 minutes to cancel pending bookings whose payment window has expired.
// This ensures freed slots are available to other customers within one expiry cycle.
Schedule::command('bookings:expire-payments')->everyFifteenMinutes();

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => Cache::put('ops:scheduler-heartbeat', now()->timestamp, now()->addDay()))
    ->name('ops:scheduler-heartbeat')
    ->everyMinute();

Schedule::call(fn () => Artisan::call('app:check-expired-subscriptions'))
    ->name('app:check-expired-subscriptions')
    ->daily()
    ->withoutOverlapping();

Schedule::call(fn () => Artisan::call('bookings:expire-payments'))
    ->name('bookings:expire-payments')
    ->everyMinute()
    ->withoutOverlapping();

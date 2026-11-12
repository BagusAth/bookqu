<?php

use App\Actions\Payment\RemindOpenRefundRequests;
use App\Actions\Tenant\CheckCustomDomain;
use App\Models\Tenant;
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

Artisan::command('refunds:remind-open', function (): void {
    app(RemindOpenRefundRequests::class)->execute();
});

Schedule::call(fn () => Artisan::call('refunds:remind-open'))
    ->name('refunds:remind-open')
    ->daily()
    ->withoutOverlapping();

// BQ-154: every own domain is re-checked hourly; one that no longer points here stops resolving.
Artisan::command('domains:check', function (CheckCustomDomain $checkCustomDomain): void {
    Tenant::query()->whereNotNull('custom_domain')->select(['id', 'custom_domain', 'custom_domain_verified_at'])
        ->chunkById(100, function ($tenants) use ($checkCustomDomain): void {
            foreach ($tenants as $tenant) {
                $checkCustomDomain->execute($tenant);
            }
        });
})->purpose('Check where every custom domain points');

Schedule::call(fn () => Artisan::call('domains:check'))
    ->name('domains:check')
    ->hourly()
    ->withoutOverlapping();

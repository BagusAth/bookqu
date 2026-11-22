<?php

use App\Actions\Booking\CompleteBookings;
use App\Actions\Owner\SendWeeklySummary;
use App\Actions\Payment\RemindOpenRefundRequests;
use App\Actions\Schedule\OpenAutomaticSlots;
use App\Actions\Tenant\CheckCustomDomain;
use App\Domain\Subscription\EntitlementRules;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
    Tenant::query()->whereNotNull('custom_domain')->select(['id', 'custom_domain', 'custom_domain_verified_at', 'custom_domain_token'])
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

Artisan::command('webhooks:prune', function (): void {
    DB::table('webhook_deliveries')->where('created_at', '<', now()->subDays(30))->delete();
})->purpose('Prune webhook delivery logs older than 30 days');

Schedule::call(fn () => Artisan::call('webhooks:prune'))
    ->name('webhooks:prune')
    ->daily()
    ->withoutOverlapping();

// BQ-162 (FR-BOOKING-024, D-026): paid sessions that ended 6 hours ago are completed for
// tenants that keep automatic completion on. Each booking goes through CompleteBookings.
Artisan::command('bookings:complete-ended', function (CompleteBookings $completeBookings): void {
    $cutoff = now('Asia/Jakarta')->subHours(CompleteBookings::AUTO_COMPLETE_AFTER_HOURS);
    Tenant::query()->where('auto_complete_bookings', true)->whereNotNull('auto_completion_started_at')
        ->select(['id', 'auto_completion_started_at'])
        ->chunkById(100, function ($tenants) use ($completeBookings, $cutoff): void {
            $context = app(TenantContext::class);
            foreach ($tenants as $tenant) {
                // Same pattern as bookings:expire-payments: tenant-scoped relations resolve inside this tenant only.
                $context->setTenantId((int) $tenant->id);
                try {
                    $completeBookings->completeEnded($tenant, 'system', $cutoff, $tenant->auto_completion_started_at);
                } catch (Throwable $exception) {
                    report($exception);
                } finally {
                    $context->clear();
                }
            }
        });
})->purpose('Complete paid bookings whose session ended at least 6 hours ago');

Schedule::call(fn () => Artisan::call('bookings:complete-ended'))
    ->name('bookings:complete-ended')
    ->hourly()
    ->withoutOverlapping();

// BQ-162 (FR-SCHEDULE-015, D-026): every night, open the missing slots of services that switched on
// automatic opening, from opening hours up to 30 days ahead.
Artisan::command('slots:open-automatic', function (OpenAutomaticSlots $openAutomaticSlots): void {
    Tenant::query()->whereIn('id', DB::table('services')->where('auto_open_slots', true)->select('idtenant'))
        ->chunkById(100, function ($tenants) use ($openAutomaticSlots): void {
            $context = app(TenantContext::class);
            foreach ($tenants as $tenant) {
                $context->setTenantId((int) $tenant->id);
                try {
                    $openAutomaticSlots->execute($tenant);
                } catch (Throwable $exception) {
                    report($exception);
                } finally {
                    $context->clear();
                }
            }
        });
})->purpose('Open slots automatically from opening hours for services that switched it on');

Schedule::call(fn () => Artisan::call('slots:open-automatic'))
    ->name('slots:open-automatic')
    ->dailyAt('01:00')
    ->withoutOverlapping();

// BQ-162 (FR-NOTIFICATION-008, D-026): Monday-morning summary of last week for every Pemilik.
Artisan::command('owners:weekly-summary', function (SendWeeklySummary $sendWeeklySummary): void {
    Tenant::query()->with('user')->chunkById(100, function ($tenants) use ($sendWeeklySummary): void {
        $context = app(TenantContext::class);
        foreach ($tenants as $tenant) {
            $previous = $context->getTenantId();
            $context->setTenantId((int) $tenant->id);
            try {
                $tenant->load('subscription.plan');
                if (EntitlementRules::hasEffectivePlan($tenant->subscription)) {
                    $sendWeeklySummary->execute($tenant);
                }
            } catch (Throwable $exception) {
                report($exception);
            } finally {
                $context->setTenantId($previous);
            }
        }
    });
})->purpose('Email the summary of last week to every business owner');

Schedule::call(fn () => Artisan::call('owners:weekly-summary'))
    ->name('owners:weekly-summary')
    ->weeklyOn(1, '07:00')
    ->withoutOverlapping();

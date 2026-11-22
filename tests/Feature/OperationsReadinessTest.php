<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OperationsReadinessTest extends TestCase
{
    public function test_scheduler_runs_commands_and_records_heartbeat_in_process(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        Artisan::call('schedule:list');
        $events = app(Schedule::class)->events();
        Artisan::shouldReceive('call')->once()->with('app:check-expired-subscriptions')->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('bookings:expire-payments')->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('refunds:remind-open')->andReturn(0);
        // BQ-154: every custom domain is re-checked hourly.
        Artisan::shouldReceive('call')->once()->with('domains:check')->andReturn(0);
        // BQ-162 (D-026): automatic completion, automatic slots and the weekly summary.
        Artisan::shouldReceive('call')->once()->with('bookings:complete-ended')->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('slots:open-automatic')->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('owners:weekly-summary')->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('webhooks:prune')->andReturn(0);

        // HostData disables proc_open, so no event may spawn a child `php artisan` process.
        foreach ($events as $event) {
            $this->assertInstanceOf(CallbackEvent::class, $event, "Scheduled event [{$event->getSummaryForDisplay()}] must run in-process.");
        }

        foreach ($events as $event) {
            $event->run($this->app);
        }

        $this->assertSame(now()->timestamp, Cache::get('ops:scheduler-heartbeat'));
    }

    /**
     * A run killed by the host leaves its overlap lock behind. The lock must expire before the next
     * run is due, or the job stops for a day (Laravel's default is 1440 minutes) (BQ-173).
     */
    public function test_overlap_locks_expire_before_the_next_run_is_due(): void
    {
        $maxMinutes = ['* * * * *' => 10, '0 * * * *' => 50, '0 0 * * *' => 120, '0 1 * * *' => 120, '0 7 * * 1' => 120];

        foreach (app(Schedule::class)->events() as $event) {
            if (! $event->withoutOverlapping) {
                continue;
            }
            $this->assertArrayHasKey($event->expression, $maxMinutes, "No lock budget for [{$event->getSummaryForDisplay()}].");
            $this->assertLessThanOrEqual($maxMinutes[$event->expression], $event->expiresAt, "[{$event->getSummaryForDisplay()}] keeps its lock too long.");
        }
    }

    public function test_health_only_fails_when_an_existing_scheduler_heartbeat_is_older_than_five_minutes(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        config(['app.debug' => false]);

        Cache::forget('ops:scheduler-heartbeat');
        $this->getJson('/up')->assertOk()->assertJson(['status' => 'up']);

        Cache::put('ops:scheduler-heartbeat', now()->timestamp, now()->addDay());
        $this->getJson('/up')->assertOk();

        $this->travelTo(now()->addMinutes(5));
        $this->getJson('/up')->assertOk();

        $this->travelTo(now()->addSecond());
        $this->getJson('/up')->assertStatus(500)->assertJson(['status' => 'down']);
    }

    #[DataProvider('proxyConfigurations')]
    public function test_customer_access_accepts_forwarded_https_only_from_configured_proxies(string $proxies, int $status): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'https://localhost', 'app.trusted_proxies' => $proxies]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeader('X-Forwarded-Proto', 'https')
            ->get('http://localhost/access')
            ->assertStatus($status);
    }

    /** @return array<string, array{string, int}> */
    public static function proxyConfigurations(): array
    {
        return [
            'empty default' => ['', 404],
            'other proxy' => ['10.0.0.2', 404],
            'trusted proxy list' => ['10.0.0.2, 10.0.0.1', 200],
        ];
    }
}

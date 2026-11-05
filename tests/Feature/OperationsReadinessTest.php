<?php

namespace Tests\Feature;

use Carbon\Carbon;
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

        foreach ($events as $event) {
            $event->run($this->app);
        }

        $this->assertSame(now()->timestamp, Cache::get('ops:scheduler-heartbeat'));
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

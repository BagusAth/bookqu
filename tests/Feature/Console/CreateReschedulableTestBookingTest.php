<?php

namespace Tests\Feature\Console;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Domain\Booking\BookingRules;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CreateReschedulableTestBookingTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['testing'])]
    #[TestWith(['production'])]
    public function test_command_refuses_to_run_outside_local(string $environment): void
    {
        $this->app->instance('env', $environment);

        $this->artisan('booking:test-reschedule', ['schedule' => 1])
            ->expectsOutput('Perintah ini hanya tersedia di local.')
            ->assertFailed();

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_command_creates_one_reschedulable_paid_booking_and_prints_only_its_id(): void
    {
        $this->freezeTime();
        $this->app->instance('env', 'local');
        $schedule = $this->createTestSchedule();

        $this->assertSame(0, Artisan::call('booking:test-reschedule', ['schedule' => $schedule->id]));

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('customer_access_grants', 0);
        $this->assertDatabaseCount('customer_access_deliveries', 0);
        $this->assertNull(app(TenantContext::class)->getTenantId());
        app(TenantContext::class)->setTenantId((int) $schedule->idtenant);
        $booking = Booking::query()->sole();
        $this->assertSame($schedule->id, $booking->idschedule);
        $this->assertSame('paid', $booking->status);
        $this->assertSame('sukses', $booking->payment->status);
        $this->assertTrue(BookingRules::canReschedule($booking));
        $this->assertSame((string) $booking->id, trim(Artisan::output()));
    }

    public function test_command_rolls_back_when_the_reschedule_deadline_has_passed(): void
    {
        $this->freezeTime();
        $this->app->instance('env', 'local');
        $schedule = $this->createTestSchedule(rescheduleBeforeHours: 168);

        $this->artisan('booking:test-reschedule', ['schedule' => $schedule->id])->assertFailed();

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('booking_seats', 0);
        $this->assertNull(app(TenantContext::class)->getTenantId());
    }

    public function test_command_rejects_a_schedule_from_another_tenant(): void
    {
        $this->freezeTime();
        $this->app->instance('env', 'local');
        Tenant::factory()->create(['slug' => 'brama-digital']);
        $schedule = $this->createTestSchedule('tenant-lain');

        $this->artisan('booking:test-reschedule', ['schedule' => $schedule->id])->assertFailed();

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    private function createTestSchedule(string $slug = 'brama-digital', int $rescheduleBeforeHours = 24): Schedule
    {
        $tenant = Tenant::factory()->create(['slug' => $slug, 'reschedule_before_hours' => $rescheduleBeforeHours]);
        app(CreateTrialSubscription::class)->execute($tenant)->forceFill(['created_at' => now()->subMinute()])->save();
        $service = Service::factory()->create(['idtenant' => $tenant->id, 'is_active' => true]);

        return Schedule::factory()->create([
            'idtenant' => $tenant->id,
            'idlayanan' => $service->id,
            'tanggal' => now('Asia/Jakarta')->addDays(3)->toDateString(),
        ]);
    }
}

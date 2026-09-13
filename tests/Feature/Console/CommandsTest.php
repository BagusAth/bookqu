<?php

namespace Tests\Feature\Console;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bookings_expire_payments_command(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $tenant = Tenant::factory()->create(['iduser' => $owner->id]);
        $service = Service::factory()->create(['idtenant' => $tenant->id]);
        $schedule = Schedule::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id]);

        $expiredPayment = Payment::create([
            'idtenant' => $tenant->id,
            'tipe' => 'booking',
            'jumlah' => 50000,
            'status' => 'pending',
            'metode' => 'midtrans',
            'order_id' => 'ORD-EXP-1',
            'expired_at' => Carbon::now()->subMinutes(30),
        ]);

        $booking = Booking::create([
            'idtenant' => $tenant->id,
            'idlayanan' => $service->id,
            'idschedule' => $schedule->id,
            'idpayment' => $expiredPayment->id,
            'namapelanggan' => 'Late Customer',
            'nomorhp' => '08123456789',
            'email' => 'late@example.com',
            'tanggalbooking' => Carbon::tomorrow()->format('Y-m-d'),
            'jam' => '10:00:00',
            'status' => 'pending',
            'booking_code' => 'BKQ-LATE-1',
        ]);

        $this->artisan('bookings:expire-payments')
            ->expectsOutputToContain('Done. Cancelled 1 expired booking payment(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('payments', [
            'id' => $expiredPayment->id,
            'status' => 'gagal',
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_check_expired_subscriptions_command(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $tenant = Tenant::factory()->create(['iduser' => $owner->id]);
        $plan = Plan::firstOrCreate(
            ['namapaket' => 'pro'],
            ['hargabulanan' => 100000, 'maxlayanan' => 10, 'maxbooking' => 500, 'isunlimited' => false]
        );

        $pastTrial = Subscription::create([
            'idtenant' => $tenant->id,
            'idplan' => $plan->id,
            'status' => 'trial',
            'trial_berakhir' => Carbon::now()->subDay(),
        ]);

        $this->artisan('app:check-expired-subscriptions')
            ->assertExitCode(0);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $pastTrial->id,
            'status' => 'expired',
        ]);
    }
}

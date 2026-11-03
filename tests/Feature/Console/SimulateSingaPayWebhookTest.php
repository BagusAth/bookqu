<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The local/dev SingaPay webhook simulator (BQ-076).
 */
class SimulateSingaPayWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_simulation_settles_the_payment_through_the_real_webhook(): void
    {
        Mail::fake();
        Notification::fake();
        Config::set('services.singapay.client_secret', 'local-test-secret');
        [$payment, $booking] = $this->pendingSingaPayBooking();

        $this->artisan('singapay:simulate-webhook', ['order_id' => $payment->order_id])
            ->expectsOutputToContain('Webhook result: 200')
            ->assertSuccessful();

        $this->assertSame('sukses', $payment->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->status);
    }

    public function test_refuses_to_run_in_production(): void
    {
        Config::set('services.singapay.client_secret', 'local-test-secret');
        [$payment] = $this->pendingSingaPayBooking();
        $this->app['env'] = 'production';

        $this->artisan('singapay:simulate-webhook', ['order_id' => $payment->order_id])
            ->expectsOutputToContain('never runs in production')
            ->assertFailed();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_fails_for_an_unknown_order(): void
    {
        Config::set('services.singapay.client_secret', 'local-test-secret');

        $this->artisan('singapay:simulate-webhook', ['order_id' => 'NO-SUCH-ORDER'])
            ->expectsOutputToContain('Payment not found')
            ->assertFailed();
    }

    /**
     * @return array{0: Payment, 1: Booking}
     */
    private function pendingSingaPayBooking(): array
    {
        $tenant = Tenant::factory()->create();
        $payment = Payment::factory()->create([
            'idtenant' => $tenant->id,
            'provider' => 'singapay',
            'metode' => 'singapay',
            'jumlah' => 150000,
        ]);
        $booking = Booking::factory()->create([
            'idtenant' => $tenant->id,
            'idpayment' => $payment->id,
        ]);

        return [$payment, $booking];
    }
}

<?php

namespace Tests\Feature\Customer;

use App\Actions\CustomerAccess\CancelCheckoutPayment;
use App\Actions\CustomerAccess\ResolveCustomerAccess;
use App\Actions\CustomerAccess\RevokeCustomerAccess;
use App\Actions\Payment\PaymentLifecycle;
use App\Actions\Payment\PaymentTransitionResult;
use App\Contracts\Payments\PaymentGatewayInterface;
use App\Domain\CustomerAccess\AccessProfile;
use App\Domain\Payment\PaymentTransitionOutcome;
use App\Domain\Payment\ProviderOutcome;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesCustomerAccess;
use Tests\TestCase;

class CancelCheckoutPaymentTest extends TestCase
{
    use RefreshDatabase;
    use UsesCustomerAccess;

    private PaymentGatewayInterface&MockInterface $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        Mail::fake();
        Notification::fake();
        $this->gateway = Mockery::mock(PaymentGatewayInterface::class);
        app(PaymentGatewayManager::class)->extend('midtrans', fn () => $this->gateway);
    }

    /** @return array{Payment, array<int, Booking>} */
    private function reservation(string $status = 'pending'): array
    {
        $tenant = Tenant::factory()->create();
        $service = Service::factory()->create(['idtenant' => $tenant->id, 'durasi' => 60]);
        $payment = Payment::factory()->create([
            'idtenant' => $tenant->id, 'provider' => 'midtrans', 'status' => $status,
            'snap_token' => 'fixture-snap', 'expired_at' => now()->addMinutes(15),
        ]);
        $bookings = [];
        foreach ([10, 11] as $hour) {
            $schedule = Schedule::factory()->create([
                'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'tanggal' => '2026-11-05',
                'jam_mulai' => $hour.':00:00', 'jam_selesai' => ($hour + 1).':00:00',
            ]);
            $bookings[] = Booking::factory()->create([
                'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'idschedule' => $schedule->id,
                'idpayment' => $payment->id, 'tanggalbooking' => '2026-11-05', 'jam' => $schedule->jam_mulai,
                'status' => match ($status) {
                    'sukses' => 'paid', 'gagal' => 'cancelled', default => 'pending'
                },
            ]);
        }

        return [$payment, $bookings];
    }

    private function path(Payment $payment, string $operation = 'cancel'): string
    {
        return '/'.$payment->tenant->slug.'/booking/payment/'.$payment->order_id.'/'.$operation;
    }

    /** @return array<string, array{AccessProfile}> */
    public static function payerProfiles(): array
    {
        return ['Checkout' => [AccessProfile::Checkout], 'Management' => [AccessProfile::Management]];
    }

    #[DataProvider('payerProfiles')]
    public function test_authorized_payer_cancels_the_complete_group_using_its_original_provider(AccessProfile $profile): void
    {
        [$payment, $bookings] = $this->reservation();
        config(['payment.default' => 'singapay']);
        $this->gateway->shouldReceive('cancel')->once()->withArgs(fn (Payment $current) => (int) $current->id === (int) $payment->id && $current->status === 'gagal');

        $this->grantCustomerAccess($payment, $profile)->post($this->path($payment))
            ->assertRedirect('/'.$payment->tenant->slug)->assertSessionHas('info');

        $this->assertSame('gagal', $payment->fresh()->status);
        foreach ($bookings as $booking) {
            $this->assertSame('cancelled', $booking->fresh()->status);
        }
        $this->assertDatabaseCount('refunds', 0);
        Mail::assertNothingSent();
    }

    /** @return array<string, array{string, int|null}> */
    public static function refusedStates(): array
    {
        return [
            'paid' => ['sukses', 900], 'failed' => ['gagal', 900],
            'past deadline in settlement margin' => ['pending', -1],
            'past settlement margin' => ['pending', -180],
            'exact deadline' => ['pending', 0],
            'missing deadline' => ['pending', null],
        ];
    }

    #[DataProvider('refusedStates')]
    public function test_final_or_nonpayable_payment_is_refused_without_gateway_or_booking_changes(string $status, ?int $seconds): void
    {
        [$payment, $bookings] = $this->reservation($status);
        $payment->update(['expired_at' => $seconds === null ? null : now()->addSeconds($seconds)]);
        $this->gateway->shouldNotReceive('cancel');
        $before = array_map(fn (Booking $booking) => $booking->status, $bookings);

        $this->grantCustomerAccess($payment, AccessProfile::Management)->postJson($this->path($payment))->assertUnprocessable();

        $this->assertSame($status, $payment->fresh()->status);
        foreach ($bookings as $index => $booking) {
            $this->assertSame($before[$index], $booking->fresh()->status);
        }
        $this->assertDatabaseCount('refunds', 0);
        Mail::assertNothingSent();
    }

    public function test_one_second_before_deadline_is_still_cancellable(): void
    {
        [$payment] = $this->reservation();
        $payment->update(['expired_at' => now()->addSecond()]);
        $this->gateway->shouldReceive('cancel')->once();

        $this->grantCustomerAccess($payment, AccessProfile::Checkout)->post($this->path($payment))->assertRedirect();

        $this->assertSame('gagal', $payment->fresh()->status);
    }

    public function test_repeat_request_does_not_repeat_gateway_cancellation(): void
    {
        [$payment, $bookings] = $this->reservation();
        $this->gateway->shouldReceive('cancel')->once();
        $this->grantCustomerAccess($payment, AccessProfile::Checkout);

        $this->post($this->path($payment))->assertRedirect();
        $this->postJson($this->path($payment))->assertUnprocessable();

        $this->assertSame('gagal', $payment->fresh()->status);
        $this->assertSame('cancelled', $bookings[0]->fresh()->status);
        $this->assertSame('cancelled', $bookings[1]->fresh()->status);
        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_gateway_failure_does_not_reverse_payer_cancellation(): void
    {
        [$payment, $bookings] = $this->reservation();
        $this->gateway->shouldReceive('cancel')->once()->andThrow(new \RuntimeException('Fixture gateway unavailable'));

        $this->grantCustomerAccess($payment, AccessProfile::Checkout)->post($this->path($payment))
            ->assertRedirect()->assertSessionHas('info');

        $this->assertSame('gagal', $payment->fresh()->status);
        $this->assertSame('cancelled', $bookings[0]->fresh()->status);
        $this->assertSame('cancelled', $bookings[1]->fresh()->status);
    }

    public function test_unsupported_singapay_gateway_cancel_still_cancels_locally_without_an_external_call(): void
    {
        [$payment, $bookings] = $this->reservation();
        $payment->update(['provider' => 'singapay', 'metode' => 'singapay']);
        $this->gateway->shouldNotReceive('cancel');
        Http::fake();
        Http::preventStrayRequests();

        $this->grantCustomerAccess($payment, AccessProfile::Checkout)->post($this->path($payment))->assertRedirect();

        $this->assertSame('gagal', $payment->fresh()->status);
        $this->assertSame('cancelled', $bookings[0]->fresh()->status);
        $this->assertSame('cancelled', $bookings[1]->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_booking_selection_session_and_legacy_tokens_do_not_authorize_cancellation(): void
    {
        [$payment, $bookings] = $this->reservation();
        $this->gateway->shouldNotReceive('cancel');

        $this->withSession(['booking' => ['tenant_id' => $payment->idtenant, 'schedule_ids' => [$bookings[0]->idschedule]]])
            ->postJson($this->path($payment), ['token' => $bookings[0]->cancellation_token])->assertNotFound();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $bookings[0]->fresh()->status);
        $this->assertSame('pending', $bookings[1]->fresh()->status);
    }

    public function test_revocation_after_preflight_is_rechecked_before_any_cancellation(): void
    {
        [$payment, $bookings] = $this->reservation();
        $this->gateway->shouldNotReceive('cancel');
        $this->grantCustomerAccess($payment, AccessProfile::Checkout);
        $context = app(ResolveCustomerAccess::class)->execute((int) session('customer_access.grant_id'));
        app(RevokeCustomerAccess::class)->execute($context->grant, true);

        $this->postJson($this->path($payment))->assertNotFound();

        try {
            app(CancelCheckoutPayment::class)->execute($context, $payment);
            $this->fail('A revoked preflight must not cancel payment.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $bookings[0]->fresh()->status);
        $this->assertSame('pending', $bookings[1]->fresh()->status);
    }

    public function test_expired_grant_cannot_cancel_a_still_payable_payment(): void
    {
        [$payment, $bookings] = $this->reservation();
        $this->gateway->shouldNotReceive('cancel');
        $this->grantCustomerAccess($payment, AccessProfile::Checkout);
        $context = app(ResolveCustomerAccess::class)->execute((int) session('customer_access.grant_id'));
        $context->grant->update(['expires_at' => now()]);

        $this->postJson($this->path($payment))->assertNotFound();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $bookings[0]->fresh()->status);
        $this->assertSame('pending', $bookings[1]->fresh()->status);
    }

    public function test_standalone_booking_management_grant_cannot_cancel_a_payment_in_the_same_tenant(): void
    {
        [$payment, $bookings] = $this->reservation();
        $standalone = Booking::factory()->create(['idtenant' => $payment->idtenant, 'idpayment' => null]);
        $this->gateway->shouldNotReceive('cancel');

        $this->grantCustomerAccess($standalone, AccessProfile::Management)->postJson($this->path($payment))->assertNotFound();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $bookings[0]->fresh()->status);
        $this->assertSame('pending', $bookings[1]->fresh()->status);
    }

    public function test_lifecycle_refusal_cannot_be_reported_as_success(): void
    {
        [$payment, $bookings] = $this->reservation();
        $this->gateway->shouldNotReceive('cancel');
        $this->mock(PaymentLifecycle::class)->shouldReceive('transition')->once()->andReturn(
            new PaymentTransitionResult(PaymentTransitionOutcome::Rejected, $payment, 'fixture-refusal')
        );

        $this->grantCustomerAccess($payment, AccessProfile::Checkout)->postJson($this->path($payment))
            ->assertUnprocessable()->assertSessionMissing('info');

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $bookings[0]->fresh()->status);
        $this->assertSame('pending', $bookings[1]->fresh()->status);
    }

    public function test_late_provider_success_after_payer_cancel_creates_only_one_refund_and_never_revives_bookings(): void
    {
        [$payment, $bookings] = $this->reservation();
        $this->gateway->shouldReceive('cancel')->once();
        $this->gateway->shouldReceive('queryOutcome')->twice()->andReturn(new ProviderOutcome(
            provider: 'midtrans', references: [$payment->order_id], result: 'sukses',
            amount: (string) $payment->jumlah, currency: 'IDR',
        ));
        $this->grantCustomerAccess($payment, AccessProfile::Checkout);
        $this->post($this->path($payment))->assertRedirect();

        $this->postJson($this->path($payment, 'callback'), ['transaction_status' => 'settlement'])->assertOk()->assertJsonPath('status', 'gagal');
        $this->postJson($this->path($payment, 'callback'), ['transaction_status' => 'settlement'])->assertOk()->assertJsonPath('status', 'gagal');

        $this->assertSame('gagal', $payment->fresh()->status);
        $this->assertSame('cancelled', $bookings[0]->fresh()->status);
        $this->assertSame('cancelled', $bookings[1]->fresh()->status);
        $this->assertDatabaseCount('refunds', 1);
        $this->assertDatabaseHas('refunds', ['payment_id' => $payment->id, 'status' => 'pending']);
    }

    public function test_cancellation_requires_csrf_even_with_a_valid_grant(): void
    {
        [$payment] = $this->reservation();
        $this->gateway->shouldNotReceive('cancel');
        $this->grantCustomerAccess($payment, AccessProfile::Checkout);
        $this->app->instance('env', 'local');

        $this->postJson($this->path($payment))->assertStatus(419);

        $this->assertSame('pending', $payment->fresh()->status);
    }
}

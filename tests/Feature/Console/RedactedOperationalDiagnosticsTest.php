<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Actions\Payment\OutcomeIntakeResult;
use App\Actions\Payment\PaymentOutcomeIntake;
use App\Actions\Service\CreateService;
use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\UsageLog;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

/**
 * BQ-077 (NFR-SEC-010): operational failures log the exception type, location and record ids,
 * never the exception message, which can carry SQL, credentials or provider payloads.
 */
class RedactedOperationalDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'SQLSTATE[HY000] password=fixture-secret-123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
    }

    public function test_a_failed_payment_expiry_is_reported_without_the_exception_message(): void
    {
        $tenant = Tenant::factory()->create(['iduser' => User::factory()->create(['role' => 'owner'])->id]);
        $failing = $this->expiredPayment($tenant, 'ORD-EXP-FAIL');
        $next = $this->expiredPayment($tenant, 'ORD-EXP-NEXT');

        $intake = $this->mock(PaymentOutcomeIntake::class);
        $intake->shouldReceive('check')->once()->withArgs(fn (Payment $payment): bool => $payment->is($failing))
            ->andThrow(new RuntimeException(self::SECRET));
        $intake->shouldReceive('check')->once()->withArgs(fn (Payment $payment): bool => $payment->is($next))
            ->andReturn(new OutcomeIntakeResult(true, $next->replicate()->fill(['status' => 'gagal'])));

        Log::spy();

        // The failure is isolated: the next payment is still processed.
        $this->artisan('bookings:expire-payments')
            ->expectsOutputToContain('Failed to expire Payment #'.$failing->id.' (RuntimeException)')
            ->expectsOutputToContain('Cancelled Payment #'.$next->id)
            ->doesntExpectOutputToContain('fixture-secret')
            ->assertExitCode(0);

        Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context) use ($failing): bool {
            return ! str_contains($message, 'fixture-secret')
                && ! str_contains(json_encode($context) ?: '', 'fixture-secret')
                && $context['payment_id'] === $failing->id
                && $context['exception_type'] === RuntimeException::class
                && isset($context['exception_file'], $context['exception_line']);
        });
    }

    public function test_a_failed_usage_record_keeps_the_service_and_logs_no_exception_message(): void
    {
        $tenant = Tenant::factory()->create(['iduser' => User::factory()->create(['role' => 'owner'])->id]);
        app(CreateTrialSubscription::class)->execute($tenant);
        app(TenantContext::class)->setTenantId((int) $tenant->id);
        UsageLog::creating(function (): void {
            throw new RuntimeException(self::SECRET);
        });
        Log::spy();

        $service = app(CreateService::class)->execute($tenant, [
            'namalayanan' => 'Layanan Baru',
            'harga' => 50000,
            'durasi' => 30,
        ]);

        $this->assertInstanceOf(Service::class, $service);
        $this->assertDatabaseHas('services', ['id' => $service->id, 'idtenant' => $tenant->id]);
        Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context) use ($tenant, $service): bool {
            return ! str_contains($message, 'fixture-secret')
                && ! str_contains(json_encode($context) ?: '', 'fixture-secret')
                && $context['tenant_id'] === $tenant->id
                && $context['service_id'] === $service->id
                && $context['exception_type'] === RuntimeException::class;
        });
    }

    private function expiredPayment(Tenant $tenant, string $orderId): Payment
    {
        return Payment::create([
            'idtenant' => $tenant->id,
            'tipe' => 'booking',
            'jumlah' => 50000,
            'status' => 'pending',
            'metode' => 'midtrans',
            'order_id' => $orderId,
            'expired_at' => Carbon::now()->subMinutes(30),
        ]);
    }
}

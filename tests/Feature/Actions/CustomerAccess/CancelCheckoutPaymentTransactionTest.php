<?php

namespace Tests\Feature\Actions\CustomerAccess;

use App\Actions\CustomerAccess\CancelCheckoutPayment;
use App\Actions\CustomerAccess\IssueCustomerAccess;
use App\Actions\CustomerAccess\ResolveCustomerAccess;
use App\Contracts\Payments\PaymentGatewayInterface;
use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessProfile;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('mysql')]
class CancelCheckoutPaymentTransactionTest extends TestCase
{
    use DatabaseTruncation;

    private PaymentGatewayInterface&MockInterface $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        $this->gateway = Mockery::mock(PaymentGatewayInterface::class);
        app(PaymentGatewayManager::class)->extend('midtrans', fn () => $this->gateway);
        config(['database.connections.payer_cancel_observer' => config('database.connections.mysql')]);
        $this->beforeApplicationDestroyed(function (): void {
            DB::connection('payer_cancel_observer')->rollBack();
            DB::purge('payer_cancel_observer');
            $this->truncateTablesForAllConnections();
        });
    }

    /** @return array{Payment, AccessContext} */
    private function reservation(): array
    {
        $tenant = Tenant::factory()->create();
        $service = Service::factory()->create(['idtenant' => $tenant->id]);
        $payment = Payment::factory()->create(['idtenant' => $tenant->id, 'provider' => 'midtrans']);
        foreach ([10, 11] as $hour) {
            $schedule = Schedule::factory()->create([
                'idtenant' => $tenant->id, 'idlayanan' => $service->id,
                'tanggal' => now()->addDays(4)->toDateString(), 'jam_mulai' => $hour.':00:00',
            ]);
            Booking::factory()->create([
                'idtenant' => $tenant->id, 'idpayment' => $payment->id,
                'idlayanan' => $service->id, 'idschedule' => $schedule->id,
                'tanggalbooking' => $schedule->tanggal, 'jam' => $schedule->jam_mulai,
            ]);
        }
        $issued = app(IssueCustomerAccess::class)->execute($payment, AccessProfile::Checkout);

        return [$payment, app(ResolveCustomerAccess::class)->execute($issued->grant->id)];
    }

    public function test_gateway_cancellation_observes_committed_group_and_released_tenant_mutex(): void
    {
        [$payment, $context] = $this->reservation();
        $observed = [];
        $this->gateway->shouldReceive('cancel')->once()->andReturnUsing(function (Payment $current) use (&$observed): void {
            $other = DB::connection('payer_cancel_observer');
            $observed['level'] = DB::transactionLevel();
            $observed['payment'] = $other->table('payments')->where('id', $current->id)->value('status');
            $observed['bookings'] = $other->table('bookings')->where('idpayment', $current->id)->pluck('status')->all();
            if ($observed['level'] === 0) {
                $other->beginTransaction();
                try {
                    $other->statement('SET SESSION innodb_lock_wait_timeout = 1');
                    $observed['tenant'] = $other->table('tenants')->where('id', $current->idtenant)->lockForUpdate()->value('id');
                } finally {
                    $other->rollBack();
                }
            }
        });

        app(CancelCheckoutPayment::class)->execute($context, $payment);

        $this->assertSame(0, $observed['level']);
        $this->assertSame('gagal', $observed['payment']);
        $this->assertSame(['cancelled', 'cancelled'], $observed['bookings']);
        $this->assertSame((int) $payment->idtenant, (int) $observed['tenant']);
        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_rolling_back_the_outer_business_transaction_does_not_cancel_the_gateway(): void
    {
        [$payment, $context] = $this->reservation();
        $this->gateway->shouldNotReceive('cancel');

        try {
            DB::transaction(function () use ($context, $payment): void {
                app(CancelCheckoutPayment::class)->execute($context, $payment);
                throw new \RuntimeException('Fixture rollback');
            });
            $this->fail('The fixture must roll back.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Fixture rollback', $exception->getMessage());
        }

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('bookings', 2);
        $this->assertSame(['pending', 'pending'], $context->bookings->map(fn (Booking $booking) => $booking->fresh()->status)->all());
        $this->assertDatabaseCount('refunds', 0);
    }
}

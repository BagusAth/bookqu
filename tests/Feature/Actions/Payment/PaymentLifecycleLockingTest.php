<?php

namespace Tests\Feature\Actions\Payment;

use App\Actions\Booking\UpdateBookingStatus;
use App\Actions\Payment\PaymentLifecycle;
use App\Domain\Payment\PaymentTransition;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Proves the Payment lifecycle locking protocol on a real InnoDB engine
 * (ADR-0004 decision 2, ADR-0005 decision 4): the tenant row first, then the payment.
 */
#[Group('mysql')]
class PaymentLifecycleLockingTest extends TestCase
{
    use DatabaseTruncation;

    private const string SECOND_CONNECTION = 'mysql_concurrent';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.'.self::SECOND_CONNECTION => config('database.connections.mysql')]);

        $this->beforeApplicationDestroyed(function (): void {
            DB::connection(self::SECOND_CONNECTION)->rollBack();
            DB::purge(self::SECOND_CONNECTION);
            $this->truncateTablesForAllConnections();
        });
    }

    public function test_waits_for_the_tenant_lock_before_touching_the_payment(): void
    {
        $payment = Payment::factory()->create();
        $this->holdLock('tenants', $payment->idtenant);

        $this->assertTransitionWaitsOn('tenants', $payment);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_waits_for_the_payment_lock_held_by_another_transaction(): void
    {
        $payment = Payment::factory()->create();
        $this->holdLock('payments', $payment->id);

        $this->assertTransitionWaitsOn('payments', $payment);
    }

    public function test_owner_action_waits_for_webhook_tenant_lock_and_reloads_after_success(): void
    {
        $this->travelTo(now()->startOfDay());
        Notification::fake();
        Queue::fake();
        $payment = Payment::factory()->create(['provider' => 'singapay']);
        $booking = Booking::factory()->create(['idtenant' => $payment->idtenant, 'idpayment' => $payment->id, 'status' => 'pending']);
        $this->holdLock('tenants', $payment->idtenant);

        // The competing transaction follows the same lifecycle as a verified webhook.
        $default = DB::getDefaultConnection();
        DB::setDefaultConnection(self::SECOND_CONNECTION);
        try {
            app(PaymentLifecycle::class)->transition($payment, PaymentTransition::ProviderSucceeded, 'qris');
        } finally {
            DB::setDefaultConnection($default);
        }
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        try {
            app(UpdateBookingStatus::class)->execute($booking, 'completed');
            $this->fail('The owner action bypassed the tenant lock.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Lock wait timeout exceeded', $exception->getMessage());
            $this->assertMatchesRegularExpression('/^select .* from `tenants` .* for update$/', $exception->getSql());
        }

        DB::connection(self::SECOND_CONNECTION)->commit();
        $this->assertTrue(app(UpdateBookingStatus::class)->execute($booking, 'completed')['success']);
        $this->assertSame('completed', $booking->fresh()->status);
        $this->assertSame('qris', $payment->fresh()->metode);
    }

    private function holdLock(string $table, int $id): void
    {
        $otherTransaction = DB::connection(self::SECOND_CONNECTION);
        $otherTransaction->beginTransaction();
        $otherTransaction->table($table)->where('id', $id)->lockForUpdate()->first();
    }

    private function assertTransitionWaitsOn(string $table, Payment $payment): void
    {
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

        try {
            app(PaymentLifecycle::class)->transition($payment, PaymentTransition::ProviderSucceeded);
            $this->fail("The lifecycle changed a payment while another transaction held the {$table} row.");
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Lock wait timeout exceeded', $exception->getMessage());
            $this->assertMatchesRegularExpression("/^select .* from `{$table}` .* for update$/", $exception->getSql());
        }
    }
}

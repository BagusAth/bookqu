<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Actions\Booking\UpdateBookingStatus;
use App\Actions\Payment\PaymentLifecycle;
use App\Domain\Payment\PaymentState;
use App\Domain\Payment\PaymentTransition;
use App\Domain\Payment\PaymentTransitionOutcome;
use App\Mail\BookingGroupInvoiceMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Refund;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\NewBookingOwnerNotification;
use App\Notifications\PaymentRefundOwedOwnerNotification;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The Payment lifecycle module at its interface (ADR-0004, BQ-067).
 */
class PaymentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private PaymentLifecycle $lifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id]);
        $this->lifecycle = app(PaymentLifecycle::class);
    }

    public function test_provider_success_marks_payment_and_whole_group_paid(): void
    {
        [$payment, $bookings] = $this->pendingGroup(slots: 2);
        $bookings->each->update(['booking_code' => null]);

        $result = $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded, 'gopay');

        $this->assertSame(PaymentTransitionOutcome::Changed, $result->outcome);
        $this->assertSame(PaymentState::STATUS_SUKSES, $result->status());
        $this->assertSame('gopay', $payment->fresh()->metode);
        foreach ($bookings as $booking) {
            $this->assertSame('paid', $booking->fresh()->status);
            $this->assertNotNull($booking->fresh()->booking_code);
        }
        Notification::assertSentToTimes($this->owner, NewBookingOwnerNotification::class, 1);
        Mail::assertSent(BookingGroupInvoiceMail::class, 1);
    }

    public function test_repeated_provider_success_changes_nothing(): void
    {
        [$payment] = $this->pendingGroup();

        $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded);
        $result = $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded);

        $this->assertSame(PaymentTransitionOutcome::AlreadyFinal, $result->outcome);
        $this->assertSame(PaymentState::STATUS_SUKSES, $result->status());
        Notification::assertSentToTimes($this->owner, NewBookingOwnerNotification::class, 1);
        Mail::assertSent(BookingGroupInvoiceMail::class, 1);
    }

    public function test_provider_failure_cancels_pending_group(): void
    {
        [$payment, $bookings] = $this->pendingGroup(slots: 2);

        $result = $this->lifecycle->transition($payment, PaymentTransition::ProviderFailed, 'gopay');

        $this->assertSame(PaymentTransitionOutcome::Changed, $result->outcome);
        $this->assertSame(PaymentState::STATUS_GAGAL, $payment->fresh()->status);
        foreach ($bookings as $booking) {
            $this->assertSame('cancelled', $booking->fresh()->status);
        }
    }

    public function test_failure_after_success_is_already_final(): void
    {
        [$payment, $bookings] = $this->pendingGroup();
        $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded);

        $result = $this->lifecycle->transition($payment, PaymentTransition::Expired);

        $this->assertSame(PaymentTransitionOutcome::AlreadyFinal, $result->outcome);
        $this->assertSame(PaymentState::STATUS_SUKSES, $payment->fresh()->status);
        $this->assertSame('paid', $bookings->first()->fresh()->status);
    }

    public function test_expiry_and_payer_cancel_cancel_the_pending_group(): void
    {
        foreach ([PaymentTransition::Expired, PaymentTransition::CancelledByPayer, PaymentTransition::InitiationFailed] as $transition) {
            [$payment, $bookings] = $this->pendingGroup();

            $result = $this->lifecycle->transition($payment, $transition);

            $this->assertSame(PaymentTransitionOutcome::Changed, $result->outcome, $transition->value);
            $this->assertSame(PaymentState::STATUS_GAGAL, $payment->fresh()->status, $transition->value);
            $this->assertSame('cancelled', $bookings->first()->fresh()->status, $transition->value);
        }
    }

    public function test_late_success_records_one_refund_and_alerts_the_owner(): void
    {
        [$payment, $bookings] = $this->pendingGroup(amount: 75000);
        $this->lifecycle->transition($payment, PaymentTransition::Expired);

        $first = $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded);
        $second = $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded);

        $this->assertSame(PaymentTransitionOutcome::RefundOwed, $first->outcome);
        $this->assertSame(PaymentTransitionOutcome::RefundOwed, $second->outcome);
        $this->assertSame(PaymentState::STATUS_GAGAL, $payment->fresh()->status);
        $this->assertSame('cancelled', $bookings->first()->fresh()->status);

        $refunds = Refund::where('payment_id', $payment->id)->get();
        $this->assertCount(1, $refunds);
        $this->assertEquals(75000, (float) $refunds->first()->jumlah);
        $this->assertSame('pending', $refunds->first()->status);
        $this->assertSame($bookings->first()->id, $refunds->first()->booking_id);
        Notification::assertSentToTimes($this->owner, PaymentRefundOwedOwnerNotification::class, 1);
    }

    public function test_success_for_group_with_cancelled_booking_withdraws_group_and_owes_refund(): void
    {
        [$payment, $bookings] = $this->pendingGroup(slots: 2);
        $bookings->first()->update(['status' => 'cancelled']);

        $result = $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded);

        $this->assertSame(PaymentTransitionOutcome::RefundOwed, $result->outcome);
        $this->assertSame(PaymentState::STATUS_GAGAL, $payment->fresh()->status);
        $this->assertSame('cancelled', $bookings->last()->fresh()->status);
        $this->assertSame(1, Refund::where('payment_id', $payment->id)->count());
        Notification::assertNotSentTo($this->owner, NewBookingOwnerNotification::class);
    }

    public function test_owner_settlement_settles_the_whole_group_in_cash(): void
    {
        [$payment, $bookings] = $this->pendingGroup(slots: 2);

        $result = $this->lifecycle->transition($payment, PaymentTransition::SettledByOwner, PaymentState::METODE_CASH);

        $this->assertSame(PaymentTransitionOutcome::Changed, $result->outcome);
        $this->assertSame(PaymentState::STATUS_SUKSES, $payment->fresh()->status);
        $this->assertSame(PaymentState::METODE_CASH, $payment->fresh()->metode);
        foreach ($bookings as $booking) {
            $this->assertSame('paid', $booking->fresh()->status);
        }
        Notification::assertNotSentTo($this->owner, NewBookingOwnerNotification::class);
        Mail::assertNotSent(BookingGroupInvoiceMail::class);
    }

    public function test_owner_marking_one_slot_paid_settles_the_whole_group(): void
    {
        [$payment, $bookings] = $this->pendingGroup(slots: 2);

        $result = app(UpdateBookingStatus::class)->execute($bookings->first(), 'paid', 'owner');

        $this->assertTrue($result['success']);
        $this->assertSame(PaymentState::STATUS_SUKSES, $payment->fresh()->status);
        $this->assertSame(PaymentState::METODE_CASH, $payment->fresh()->metode);
        $this->assertSame('paid', $bookings->last()->fresh()->status);

        $lateWebhook = $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded, 'qris');

        $this->assertSame(PaymentTransitionOutcome::AlreadyFinal, $lateWebhook->outcome);
        $this->assertSame(PaymentState::METODE_CASH, $payment->fresh()->metode);
        $this->assertSame(0, Refund::count());
    }

    public function test_owner_completing_a_pending_booking_records_a_cash_payment(): void
    {
        [$payment, $bookings] = $this->pendingGroup();

        $result = app(UpdateBookingStatus::class)->execute($bookings->first(), 'completed', 'owner');

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $bookings->first()->fresh()->status);
        $this->assertSame(PaymentState::STATUS_SUKSES, $payment->fresh()->status);
        $this->assertSame(PaymentState::METODE_CASH, $payment->fresh()->metode);
    }

    public function test_owner_cannot_mark_paid_a_booking_whose_payment_failed(): void
    {
        [$payment, $bookings] = $this->pendingGroup();
        $this->lifecycle->transition($payment, PaymentTransition::Expired);

        $result = app(UpdateBookingStatus::class)->execute($bookings->first()->fresh(), 'paid', 'owner');

        $this->assertFalse($result['success']);
        $this->assertSame(PaymentState::STATUS_GAGAL, $payment->fresh()->status);
    }

    public function test_owner_settlement_of_group_with_cancelled_booking_is_rejected(): void
    {
        [$payment, $bookings] = $this->pendingGroup(slots: 2);
        $bookings->first()->update(['status' => 'cancelled']);

        $result = $this->lifecycle->transition($payment, PaymentTransition::SettledByOwner, PaymentState::METODE_CASH);

        $this->assertSame(PaymentTransitionOutcome::Rejected, $result->outcome);
        $this->assertSame('purchase-undeliverable', $result->reason);
        $this->assertSame(PaymentState::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame('pending', $bookings->last()->fresh()->status);
    }

    public function test_owner_cannot_settle_a_subscription_payment(): void
    {
        $payment = $this->subscriptionPayment();

        $result = $this->lifecycle->transition($payment, PaymentTransition::SettledByOwner, PaymentState::METODE_CASH);

        $this->assertSame(PaymentTransitionOutcome::Rejected, $result->outcome);
        $this->assertSame('transition-not-applicable', $result->reason);
        $this->assertSame(PaymentState::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_subscription_success_activates_the_plan(): void
    {
        $payment = $this->subscriptionPayment();

        $result = $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded, 'gopay');

        $this->assertSame(PaymentTransitionOutcome::Changed, $result->outcome);
        $this->assertTrue(Subscription::withoutGlobalScopes()->where('idtenant', $this->tenant->id)
            ->where('idplan', $payment->idplan)
            ->where('status', 'active')
            ->exists());
    }

    public function test_late_subscription_success_owes_a_refund_without_a_booking(): void
    {
        $payment = $this->subscriptionPayment();
        $this->lifecycle->transition($payment, PaymentTransition::Expired);

        $result = $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded);

        $this->assertSame(PaymentTransitionOutcome::RefundOwed, $result->outcome);
        $this->assertNull(Refund::where('payment_id', $payment->id)->sole()->booking_id);
        $this->assertFalse(Subscription::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->where('status', 'active')->exists());
    }

    public function test_record_settled_always_stores_a_successful_payment(): void
    {
        $payment = $this->lifecycle->recordSettled([
            'idtenant' => $this->tenant->id,
            'tipe' => PaymentState::TIPE_BOOKING,
            'jumlah' => 0,
            'status' => PaymentState::STATUS_PENDING,
            'metode' => PaymentState::METODE_GRATIS,
            'provider' => 'manual',
            'order_id' => 'FREE-LIFECYCLE-1',
        ]);

        $this->assertSame(PaymentState::STATUS_SUKSES, $payment->fresh()->status);
    }

    public function test_refund_for_a_cancelled_paid_booking_is_created_once(): void
    {
        [$payment, $bookings] = $this->pendingGroup(amount: 50000);
        $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded);

        $first = $this->lifecycle->refundCancelledBooking($bookings->first(), 'Pelanggan batal');
        $second = $this->lifecycle->refundCancelledBooking($bookings->first());

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertEquals(50000, (float) $first->jumlah);
        $this->assertSame(1, Refund::where('payment_id', $payment->id)->count());
    }

    public function test_no_refund_is_owed_for_a_booking_whose_payment_did_not_succeed(): void
    {
        [, $bookings] = $this->pendingGroup();

        $this->assertNull($this->lifecycle->refundCancelledBooking($bookings->first()));
        $this->assertSame(0, Refund::count());
    }

    public function test_missing_payment_is_rejected(): void
    {
        $payment = new Payment(['idtenant' => $this->tenant->id]);
        $payment->id = 999999;

        $result = $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded);

        $this->assertSame(PaymentTransitionOutcome::Rejected, $result->outcome);
        $this->assertSame('payment-not-found', $result->reason);
    }

    public function test_caller_tenant_context_is_restored(): void
    {
        [$payment] = $this->pendingGroup();
        $otherTenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenantId($otherTenant->id);

        $this->lifecycle->transition($payment, PaymentTransition::ProviderSucceeded);

        $this->assertSame($otherTenant->id, app(TenantContext::class)->getTenantId());
        app(TenantContext::class)->clear();
    }

    /**
     * @return array{0: Payment, 1: Collection<int, Booking>}
     */
    private function pendingGroup(int $slots = 1, float $amount = 100000): array
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => $amount]);
        $payment = Payment::factory()->create([
            'idtenant' => $this->tenant->id,
            'jumlah' => $amount,
            'email_pembayar' => 'pelanggan@example.com',
        ]);

        $bookings = collect(range(1, $slots))->map(fn (int $slot) => Booking::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $service->id,
            'idschedule' => Schedule::factory()->create([
                'idtenant' => $this->tenant->id,
                'idlayanan' => $service->id,
            ])->id,
            'idpayment' => $payment->id,
            'email' => 'pelanggan@example.com',
            'jam' => sprintf('%02d:00:00', 8 + $slot),
        ]));

        return [$payment, $bookings];
    }

    private function subscriptionPayment(): Payment
    {
        $plan = Plan::create([
            'namapaket' => 'pro',
            'hargabulanan' => 150000,
            'maxlayanan' => 10,
            'maxbooking' => 100,
            'isunlimited' => false,
        ]);

        return Payment::factory()->create([
            'idtenant' => $this->tenant->id,
            'idplan' => $plan->id,
            'tipe' => PaymentState::TIPE_SUBSCRIPTION,
            'jumlah' => 150000,
        ]);
    }
}

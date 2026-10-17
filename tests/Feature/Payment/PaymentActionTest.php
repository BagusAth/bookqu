<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Actions\Payment\CheckPaymentStatus;
use App\Actions\Payment\CreateBookingPayment;
use App\Actions\Payment\CreateSubscriptionPayment;
use App\Actions\Payment\ExpirePayment;
use App\Actions\Payment\ProcessPaymentWebhook;
use App\Actions\Payment\SynchronizePaymentStatus;
use App\Domain\Payment\PaymentState;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentActionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->tenant = Tenant::factory()->create([
            'iduser' => $this->user->id,
            'payment_mode' => 'platform',
        ]);

        app(TenantContext::class)->setTenantId($this->tenant->id);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        parent::tearDown();
    }

    public function test_synchronize_booking_payment_success(): void
    {
        Mail::fake();
        Notification::fake();

        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 50000]);
        $schedule = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal' => now()->addDay()->toDateString(),
            'jam_mulai' => '10:00:00',
        ]);

        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 50000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_MIDTRANS,
            'order_id'       => 'BKG-TEST-123',
            'nama_pembayar'  => 'Pelanggan Test',
            'email_pembayar' => 'pelanggan@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $booking = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Pelanggan Test',
            'nomorhp'        => '08123456789',
            'email'          => 'pelanggan@test.com',
            'tanggalbooking' => $schedule->tanggal,
            'jam'            => '10:00:00',
            'status'         => 'pending',
        ]);

        /** @var SynchronizePaymentStatus $syncAction */
        $syncAction = app(SynchronizePaymentStatus::class);
        $result = $syncAction->syncStatus($payment, [
            'transaction_status' => 'settlement',
            'payment_type'       => 'bank_transfer',
        ]);

        $this->assertEquals(PaymentState::STATUS_SUKSES, $result['status']);
        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->fresh()->status);
        $this->assertEquals('paid', $booking->fresh()->status);
        $this->assertEquals('bank_transfer', $payment->fresh()->metode);
    }

    public function test_synchronize_subscription_payment_success(): void
    {
        $plan = Plan::create([
            'namapaket'    => 'pro',
            'hargabulanan' => 150000,
            'maxlayanan'   => 10,
            'maxbooking'   => 100,
            'isunlimited'  => false,
        ]);

        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'idplan'         => $plan->id,
            'tipe'           => PaymentState::TIPE_SUBSCRIPTION,
            'jumlah'         => 150000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_MIDTRANS,
            'order_id'       => 'SUB-TEST-123',
            'nama_pembayar'  => 'Owner Test',
            'email_pembayar' => 'owner@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        /** @var SynchronizePaymentStatus $syncAction */
        $syncAction = app(SynchronizePaymentStatus::class);
        $result = $syncAction->syncStatus($payment, [
            'transaction_status' => 'settlement',
            'payment_type'       => 'gopay',
        ]);

        $this->assertEquals(PaymentState::STATUS_SUKSES, $result['status']);
        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->fresh()->status);

        $subscription = Subscription::where('idtenant', $this->tenant->id)
            ->where('idplan', $plan->id)
            ->where('status', 'active')
            ->first();

        $this->assertNotNull($subscription);
    }

    public function test_expire_payment_cancels_pending_bookings(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id]);
        $schedule = Schedule::factory()->create([
            'idtenant'  => $this->tenant->id,
            'idlayanan' => $service->id,
            'tanggal'   => now()->toDateString(),
            'jam_mulai' => '11:00:00',
        ]);

        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 50000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_MIDTRANS,
            'order_id'       => 'BKG-EXPIRE-123',
            'expired_at'     => now()->subMinutes(1),
            'nama_pembayar'  => 'Pelanggan Expired',
            'email_pembayar' => 'expired@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $booking = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Pelanggan Expired',
            'nomorhp'        => '08123456789',
            'email'          => 'expired@test.com',
            'tanggalbooking' => now()->toDateString(),
            'jam'            => '11:00:00',
            'status'         => 'pending',
        ]);

        /** @var ExpirePayment $expireAction */
        $expireAction = app(ExpirePayment::class);
        $result = $expireAction->execute($payment);

        $this->assertEquals(PaymentState::STATUS_GAGAL, $result['status']);
        $this->assertEquals(PaymentState::STATUS_GAGAL, $payment->fresh()->status);
        $this->assertEquals('cancelled', $booking->fresh()->status);
    }

    public function test_check_payment_status_fast_path(): void
    {
        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 50000,
            'status'         => PaymentState::STATUS_SUKSES,
            'metode'         => PaymentState::METODE_MIDTRANS,
            'order_id'       => 'BKG-FAST-123',
            'nama_pembayar'  => 'Pelanggan Fast',
            'email_pembayar' => 'fast@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        /** @var CheckPaymentStatus $checkAction */
        $checkAction = app(CheckPaymentStatus::class);
        $result = $checkAction->execute($payment);

        $this->assertEquals(PaymentState::STATUS_SUKSES, $result['status']);
        $this->assertEquals('settlement', $result['transaction_status']);
    }

    public function test_process_webhook_validates_signature(): void
    {
        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 50000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_MIDTRANS,
            'order_id'       => 'BKG-WEBHOOK-123',
            'nama_pembayar'  => 'Pelanggan Webhook',
            'email_pembayar' => 'webhook@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        /** @var ProcessPaymentWebhook $webhookAction */
        $webhookAction = app(ProcessPaymentWebhook::class);

        // Missing fields -> 400
        $result = $webhookAction->execute(['order_id' => 'BKG-WEBHOOK-123']);
        $this->assertFalse($result['success']);
        $this->assertEquals(400, $result['code']);

        // Unknown payment -> 404
        $result = $webhookAction->execute([
            'order_id'      => 'NONEXISTENT',
            'status_code'   => '200',
            'gross_amount'  => '50000.00',
            'signature_key' => 'fake_signature',
        ]);
        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['code']);

        // Invalid signature -> 403
        $result = $webhookAction->execute([
            'order_id'      => 'BKG-WEBHOOK-123',
            'status_code'   => '200',
            'gross_amount'  => '50000.00',
            'signature_key' => 'invalid_signature_hash',
        ]);
        $this->assertFalse($result['success']);
        $this->assertEquals(403, $result['code']);

        // Valid signature -> 200
        $serverKey = config('midtrans.server_key');
        $validSignature = hash('sha512', 'BKG-WEBHOOK-123' . '200' . '50000.00' . $serverKey);
        $result = $webhookAction->execute([
            'order_id'           => 'BKG-WEBHOOK-123',
            'status_code'        => '200',
            'gross_amount'       => '50000.00',
            'signature_key'      => $validSignature,
            'transaction_status' => 'settlement',
        ]);
        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['code']);
        $this->assertEquals(PaymentState::STATUS_SUKSES, $payment->fresh()->status);
    }
}

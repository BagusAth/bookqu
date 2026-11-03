<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Infrastructure\Payments\SingaPay\SingaPayClient;
use App\Infrastructure\Payments\SingaPay\SingaPayPaymentGateway;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery;
use Tests\Concerns\UsesCustomerAccess;
use Tests\TestCase;

class PaymentGatewayDriverTest extends TestCase
{
    use RefreshDatabase;
    use UsesCustomerAccess;

    protected User $user;

    protected Tenant $tenant;

    protected Service $layanan;

    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));

        $this->user = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->tenant = Tenant::create([
            'iduser' => $this->user->id,
            'namabisnis' => 'Klinik Sehat',
            'jenisbisnis' => 'Klinik',
            'slug' => 'klinik-sehat',
            'alamat' => 'Jl. Merdeka No. 1',
            'nomorhp' => '081234567890',
        ]);

        $this->layanan = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Konsultasi Umum',
            'harga' => 150000,
            'durasi' => 60,
            'status' => 'aktif',
        ]);

        $this->plan = Plan::create([
            'namapaket' => 'pro',
            'hargabulanan' => 200000,
            'maxlayanan' => 10,
            'maxbooking' => 100,
            'isunlimited' => false,
        ]);
    }

    public function test_manager_resolves_singapay_when_configured_as_default(): void
    {
        config(['payment.default' => 'singapay']);

        $manager = app(PaymentGatewayManager::class);
        $driver = $manager->driver();

        $this->assertInstanceOf(SingaPayPaymentGateway::class, $driver);
        $this->assertSame('singapay', $driver->getDriverName());
    }

    public function test_manager_resolves_midtrans_when_configured_as_default(): void
    {
        config(['payment.default' => 'midtrans']);

        $manager = app(PaymentGatewayManager::class);
        $driver = $manager->driver();

        $this->assertInstanceOf(MidtransPaymentGateway::class, $driver);
        $this->assertSame('midtrans', $driver->getDriverName());
    }

    public function test_manager_throws_on_unsupported_driver(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment driver [xendit].');

        $manager = app(PaymentGatewayManager::class);
        $manager->driver('xendit');
    }

    public function test_manager_resolves_driver_for_payment_based_on_historical_provider(): void
    {
        config(['payment.default' => 'singapay']);

        $midtransPayment = Payment::create([
            'idtenant' => $this->tenant->id,
            'tipe' => 'booking',
            'jumlah' => 150000,
            'status' => 'pending',
            'provider' => 'midtrans',
            'metode' => 'midtrans',
            'order_id' => 'BQ-MID-001',
        ]);

        $singapayPayment = Payment::create([
            'idtenant' => $this->tenant->id,
            'tipe' => 'booking',
            'jumlah' => 150000,
            'status' => 'pending',
            'provider' => 'singapay',
            'metode' => 'singapay',
            'order_id' => 'BQ-SNG-002',
        ]);

        $manager = app(PaymentGatewayManager::class);

        $midtransDriver = $manager->forPayment($midtransPayment);
        $singapayDriver = $manager->forPayment($singapayPayment);

        $this->assertInstanceOf(MidtransPaymentGateway::class, $midtransDriver);
        $this->assertSame('midtrans', $midtransDriver->getDriverName());

        $this->assertInstanceOf(SingaPayPaymentGateway::class, $singapayDriver);
        $this->assertSame('singapay', $singapayDriver->getDriverName());
    }

    public function test_customer_booking_initializes_singapay_payment_when_driver_is_singapay(): void
    {
        config(['payment.default' => 'singapay']);

        $targetDate = now()->addDays(2)->format('Y-m-d');

        $schedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->layanan->id,
            'tanggal' => $targetDate,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);

        $mockClient = Mockery::mock(SingaPayClient::class);
        $mockClient->shouldReceive('createPaymentLink')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'id' => 'sng-ext-12345',
                    'payment_url' => 'https://payment-b2b.singapay.id/checkout/sng-ext-12345',
                ],
            ]);

        $this->app->instance(SingaPayClient::class, $mockClient);

        $targetDate = now()->addDays(2)->format('Y-m-d');

        session([
            'booking' => [
                'tenant_id' => $this->tenant->id,
                'service_id' => $this->layanan->id,
                'tanggal' => $targetDate,
                'jam' => ['10:00'],
                'schedule_ids' => [$schedule->id],
            ],
        ]);

        $response = $this->post("/{$this->tenant->slug}/booking/checkout", [
            'namapelanggan' => 'Pelanggan Uji',
            'email' => 'pelanggan@example.com',
            'nomorhp' => '081299998888',
        ]);

        $payment = Payment::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertSame('singapay', $payment->provider);
        $this->assertSame('https://payment-b2b.singapay.id/checkout/sng-ext-12345', $payment->payment_url);
        $this->assertSame('sng-ext-12345', $payment->external_id);
        $this->assertNull($payment->snap_token);

        $this->assertCheckoutHandoff($response, $payment);
    }

    public function test_owner_subscription_checkout_initializes_singapay_payment(): void
    {
        config(['payment.default' => 'singapay']);

        $mockClient = Mockery::mock(SingaPayClient::class);
        $mockClient->shouldReceive('createPaymentLink')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'id' => 'sng-sub-77889',
                    'payment_url' => 'https://payment-b2b.singapay.id/checkout/sng-sub-77889',
                ],
            ]);

        $this->app->instance(SingaPayClient::class, $mockClient);

        $response = $this->actingAs($this->user)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->post('/owner/checkout', [
                'plan_id' => $this->plan->id,
                'nama_pembayar' => 'Dr. Budi',
                'email_pembayar' => 'budi@kliniksehat.com',
                'hp_pembayar' => '081234567890',
                'catatan' => 'Langganan Pro Bulanan',
            ]);

        $payment = Payment::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->where('tipe', 'subscription')->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertSame('singapay', $payment->provider);
        $this->assertSame('https://payment-b2b.singapay.id/checkout/sng-sub-77889', $payment->payment_url);
        $this->assertSame('sng-sub-77889', $payment->external_id);
        $this->assertNull($payment->snap_token);

        $response->assertRedirect(route('owner.checkout.payment', $payment));
    }
}

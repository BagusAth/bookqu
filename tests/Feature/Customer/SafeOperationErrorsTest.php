<?php

namespace Tests\Feature\Customer;

use App\Actions\Schedule\ClaimSlots;
use App\Actions\Schedule\SlotClaimRefused;
use App\Contracts\Payments\PaymentGatewayInterface;
use App\Domain\Schedule\SlotRefusal;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Monolog\Handler\TestHandler;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesCustomerAccess;
use Tests\TestCase;

class SafeOperationErrorsTest extends TestCase
{
    use RefreshDatabase;
    use UsesCustomerAccess;

    private const INTERNAL_ERROR = 'SQLSTATE[HY000] server_key=fixture-secret /private/config.php <script>internal</script>';

    private TestHandler $logs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        Mail::fake();
        Notification::fake();
        config(['payment.default' => 'midtrans']);
        $this->logs = new TestHandler;
        Log::swap(new Logger(new Monolog('safe-errors', [$this->logs])));
    }

    /** @return array<string, array{class-string<\Throwable>}> */
    public static function failures(): array
    {
        return ['exception' => [\RuntimeException::class], 'engine error' => [\Error::class]];
    }

    /** @return array{Tenant, Service, Schedule} */
    private function storefront(): array
    {
        $tenant = Tenant::factory()->create();
        $service = Service::factory()->create(['idtenant' => $tenant->id, 'harga' => 50000, 'durasi' => 60]);
        $schedule = Schedule::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id,
            'tanggal' => now()->addDays(4)->toDateString(), 'jam_mulai' => '10:00:00', 'jam_selesai' => '11:00:00',
        ]);

        return [$tenant, $service, $schedule];
    }

    /** @return array<string, mixed> */
    private function selection(Tenant $tenant, Service $service, Schedule $schedule): array
    {
        return ['booking' => [
            'tenant_id' => $tenant->id, 'service_id' => $service->id, 'tanggal' => $schedule->tanggal->toDateString(),
            'jam' => ['10:00'], 'schedule_ids' => [$schedule->id],
        ]];
    }

    #[DataProvider('failures')]
    public function test_customer_checkout_hides_gateway_errors_and_releases_failed_booking(string $failureClass): void
    {
        [$tenant, $service, $schedule] = $this->storefront();
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('getDriverName')->andReturn('midtrans');
        $gateway->shouldReceive('createBookingPayment')->once()->andThrow(new $failureClass(self::INTERNAL_ERROR));
        app(PaymentGatewayManager::class)->extend('midtrans', fn () => $gateway);

        $url = '/'.$tenant->slug.'/booking/checkout';
        $this->from($url)->withSession($this->selection($tenant, $service, $schedule))
            ->post($url, ['namapelanggan' => 'Pelanggan', 'email' => 'customer@example.test', 'nomorhp' => '081234567890'])
            ->assertRedirect($url)->assertSessionHas('error', 'Gagal memproses pembayaran. Silakan coba lagi.');
        $this->get($url)->assertOk()->assertDontSee('fixture-secret')->assertDontSee('SQLSTATE')->assertDontSee('/private/config.php');
        $payment = DB::table('payments')->where('idtenant', $tenant->id)->sole();
        $this->assertSame('gagal', $payment->status);
        $this->assertDatabaseHas('bookings', ['idpayment' => $payment->id, 'status' => 'cancelled']);
        $this->assertSafeDiagnostics('Payment Initialization Error (Booking)', $failureClass, 'payment_id', $payment->id);
    }

    public function test_subscription_checkout_hides_provider_errors_from_flash_and_logs(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::create(['namapaket' => 'pro', 'hargabulanan' => 200000, 'maxlayanan' => 10, 'maxbooking' => 100, 'isunlimited' => false]);
        $gateway = Mockery::mock(MidtransPaymentGateway::class)->makePartial();
        $gateway->shouldReceive('createSnapToken')->once()->andThrow(new \RuntimeException(self::INTERNAL_ERROR));
        app(PaymentGatewayManager::class)->extend('midtrans', fn () => $gateway);

        $this->actingAs($tenant->user)->post(route('owner.checkout.process'), [
            'plan_id' => $plan->id, 'nama_pembayar' => 'Pemilik', 'email_pembayar' => 'owner@example.test', 'hp_pembayar' => '081234567890',
        ])->assertRedirect(route('owner.subscription'))->assertSessionHas('pesan', 'Gagal memproses pembayaran. Silakan coba lagi.');

        $this->assertDatabaseHas('payments', ['idtenant' => $tenant->id, 'idplan' => $plan->id, 'status' => 'gagal']);
        $this->assertSafeDiagnostics('Owner checkout error', \RuntimeException::class, 'tenant_id', $tenant->id);
    }

    /** @return array<string, array{string}> */
    public static function singapayFailures(): array
    {
        return ['authentication' => ['oauth'], 'account lookup' => ['accounts'], 'provider error' => ['link'], 'missing URL' => ['missing-url']];
    }

    #[DataProvider('singapayFailures')]
    public function test_singapay_failure_response_bodies_do_not_reach_customer_or_logs(string $stage): void
    {
        config([
            'payment.default' => 'singapay', 'services.singapay.base_url' => 'https://gateway.example.test',
            'services.singapay.client_id' => 'fixture-client', 'services.singapay.client_secret' => 'fixture-client-secret',
            'services.singapay.api_key' => 'fixture-api', 'services.singapay.account_id' => $stage === 'accounts' ? null : 'fixture-account',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'gateway.example.test/api/v1.1/access-token/b2b' => $stage === 'oauth'
                ? Http::response(['message' => self::INTERNAL_ERROR], 401) : Http::response(['access_token' => 'fixture-access-token']),
            'gateway.example.test/api/v1.0/accounts' => Http::response(['message' => self::INTERNAL_ERROR], 500),
            'gateway.example.test/api/v1.0/payment-link-manage/*' => Http::response(['message' => self::INTERNAL_ERROR], $stage === 'missing-url' ? 200 : 500),
        ]);
        [$tenant, $service, $schedule] = $this->storefront();
        $url = '/'.$tenant->slug.'/booking/checkout';

        $this->from($url)->withSession($this->selection($tenant, $service, $schedule))
            ->post($url, ['namapelanggan' => 'Pelanggan', 'email' => 'customer@example.test', 'nomorhp' => '081234567890'])
            ->assertRedirect($url)->assertSessionHas('error', 'Gagal memproses pembayaran. Silakan coba lagi.');

        $payment = DB::table('payments')->where('idtenant', $tenant->id)->sole();
        $this->assertSame('gagal', $payment->status);
        $this->assertSafeDiagnostics('Payment Initialization Error (Booking)', \Exception::class, 'payment_id', $payment->id);
    }

    /** @return array<string, array{string, class-string<\Throwable>}> */
    public static function rescheduleFailures(): array
    {
        return [
            'owner exception' => ['owner', \RuntimeException::class], 'customer exception' => ['customer', \RuntimeException::class],
            'owner engine error' => ['owner', \Error::class], 'customer engine error' => ['customer', \Error::class],
        ];
    }

    #[DataProvider('rescheduleFailures')]
    public function test_reschedule_hides_internal_errors_and_keeps_original_slot(string $actor, string $failureClass): void
    {
        [$tenant, $service, $original] = $this->storefront();
        $booking = Booking::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'idschedule' => $original->id,
            'idpayment' => null, 'status' => 'paid', 'tanggalbooking' => $original->tanggal, 'jam' => '10:00:00',
        ]);
        $this->mock(ClaimSlots::class)->shouldReceive('execute')->once()->andThrow(new $failureClass(self::INTERNAL_ERROR));
        if ($actor === 'owner') {
            $this->actingAs($tenant->user);
            $url = route('owner.bookings.reschedule', $booking);
        } else {
            $this->grantCustomerAccess($booking);
            $url = route('booking.manage.reschedule.store', ['booking_code' => $booking->booking_code]);
        }

        $response = $this->postJson($url, ['schedule_id' => $original->id + 1000, 'tanggal' => now()->addDays(5)->toDateString()]);
        if ($actor === 'owner') {
            $response->assertStatus(422)->assertJsonPath('message', 'Terjadi kesalahan. Silakan coba lagi.');
        } else {
            $response->assertRedirect()->assertSessionHasErrors(['reschedule' => 'Terjadi kesalahan. Silakan coba lagi.']);
        }
        $response->assertDontSee('fixture-secret')->assertDontSee('SQLSTATE')->assertDontSee('/private/config.php');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'idschedule' => $original->id, 'status' => 'paid']);
        $this->assertSafeDiagnostics('RescheduleBooking: Exception', $failureClass, 'booking_id', $booking->id);
    }

    public function test_slot_refusal_keeps_its_customer_facing_business_message(): void
    {
        [$tenant, $service, $schedule] = $this->storefront();
        $this->mock(ClaimSlots::class)->shouldReceive('execute')->once()->andThrow(new SlotClaimRefused(SlotRefusal::Occupied, $schedule->id, '10:00'));
        $this->from('/'.$tenant->slug.'/booking/time')->withSession($this->selection($tenant, $service, $schedule))
            ->post('/'.$tenant->slug.'/booking/checkout', [
                'namapelanggan' => 'Pelanggan', 'email' => 'customer@example.test', 'nomorhp' => '081234567890',
            ])->assertSessionHasErrors(['jam' => SlotRefusal::Occupied->message('10:00')]);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_midtrans_status_failure_does_not_log_a_raw_provider_exception(): void
    {
        $payment = Payment::factory()->create(['provider' => 'midtrans']);
        $gateway = Mockery::mock(MidtransPaymentGateway::class)->makePartial();
        $gateway->shouldReceive('getTransactionStatus')->once()->andThrow(new \RuntimeException(self::INTERNAL_ERROR));

        $this->assertSame('unknown', $gateway->queryOutcome($payment)->result);
        $this->assertSafeDiagnostics('Midtrans status query failed', \RuntimeException::class, 'payment_id', $payment->id);
    }

    private function assertSafeDiagnostics(string $message, string $exceptionClass, string $idKey, int $id): void
    {
        $records = $this->logs->getRecords();
        $this->assertNotEmpty($records);
        $found = false;
        foreach ($records as $record) {
            $serialized = json_encode([$record->message, $record->context]);
            foreach (['fixture-secret', 'SQLSTATE', '/private/config.php', '<script>'] as $sensitive) {
                $this->assertStringNotContainsString($sensitive, $serialized);
            }
            if ($record->message === $message) {
                $this->assertSame($exceptionClass, $record->context['exception_type']);
                $this->assertSame($id, $record->context[$idKey]);
                $found = true;
            }
        }
        $this->assertTrue($found, 'Expected diagnostic record with safe operation context.');
    }
}

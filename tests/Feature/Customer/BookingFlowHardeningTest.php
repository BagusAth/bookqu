<?php

namespace Tests\Feature\Customer;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Domain\CustomerAccess\AccessProfile;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesCustomerAccess;
use Tests\TestCase;

class BookingFlowHardeningTest extends TestCase
{
    use RefreshDatabase;
    use UsesCustomerAccess;

    private const EXPIRED_HINT = 'Halaman ini sudah kedaluwarsa';

    private Tenant $tenant;

    private Service $service;

    private Schedule $schedule;

    private string $tomorrow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        config(['payment.default' => 'midtrans', 'app.url' => 'http://localhost']);

        $this->tenant = Tenant::create([
            'iduser' => User::factory()->create(['role' => 'owner'])->id,
            'namabisnis' => 'My Booking Business',
            'jenisbisnis' => 'Klinik',
            'slug' => 'my-business',
            'alamat' => 'Jalan Test',
            'nomorhp' => '08123456789',
        ]);
        // BQ-086: successful writes require a live subscription; assertions stay unchanged.
        app(CreateTrialSubscription::class)->execute($this->tenant)->forceFill(['created_at' => now()->subMinute()])->save();

        $this->service = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Program Konsultasi',
            'harga' => 200000,
            'durasi' => 60,
            'is_active' => true,
        ]);
        $this->tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $this->schedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->tomorrow,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status' => 'tersedia',
        ]);
    }

    public function test_storefront_booking_pages_are_never_stored_by_the_browser(): void
    {
        $response = $this->get('/my-business')->assertOk();

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_an_expired_form_token_returns_the_customer_to_the_form_with_a_hint(): void
    {
        $this->app->instance('env', 'local');
        $this->selectService();

        $this->from('/my-business/booking/date')
            ->post('/my-business/booking/select-date', ['tanggal' => $this->tomorrow])
            ->assertRedirect('/my-business/booking/date')
            ->assertSessionHasInput('tanggal', $this->tomorrow);

        $this->followingRedirects()->get('/my-business/booking/date')->assertOk()->assertSee(self::EXPIRED_HINT);
    }

    public function test_an_expired_token_on_a_finished_checkout_still_explains_itself_on_the_first_step(): void
    {
        $this->app->instance('env', 'local');

        $this->from('/my-business/booking/checkout')
            ->post('/my-business/booking/checkout', $this->customer())
            ->assertRedirect('/my-business/booking/checkout');

        $this->get('/my-business/booking/checkout')->assertRedirect('/my-business');
        $this->get('/my-business')->assertOk()->assertSee(self::EXPIRED_HINT);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_an_expired_token_on_a_json_request_answers_in_indonesian(): void
    {
        $this->app->instance('env', 'local');

        $this->postJson('/my-business/booking/select-date', ['tanggal' => $this->tomorrow])
            ->assertStatus(419)
            ->assertJsonPath('message', 'Sesi halaman telah berakhir. Muat ulang halaman lalu coba lagi.');
    }

    public function test_a_price_change_after_the_checkout_page_was_shown_is_refused_until_reviewed(): void
    {
        $this->selectSlot();
        $stale = $this->fingerprintFromCheckoutPage();
        $this->schedule->update(['harga_override' => 250000]);

        $this->post('/my-business/booking/checkout', $this->customer() + ['checkout_fingerprint' => $stale])
            ->assertRedirect('/my-business/booking/checkout')
            ->assertSessionHas('error');
        $this->assertDatabaseCount('payments', 0);

        $fresh = $this->fingerprintFromCheckoutPage();
        $this->post('/my-business/booking/checkout', $this->customer() + ['checkout_fingerprint' => $fresh])->assertRedirect();

        $this->assertSame('250000.00', (string) Payment::withoutGlobalScopes()->sole()->jumlah);
    }

    public function test_a_selection_changed_in_another_tab_is_not_booked_from_the_old_page(): void
    {
        $other = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->tomorrow,
            'jam_mulai' => '13:00:00',
            'jam_selesai' => '14:00:00',
            'status' => 'tersedia',
        ]);
        $this->selectSlot();
        $shown = $this->fingerprintFromCheckoutPage();
        session()->put('booking.jam', ['13:00']);
        session()->put('booking.schedule_ids', [$other->id]);

        $this->post('/my-business/booking/checkout', $this->customer() + ['checkout_fingerprint' => $shown])
            ->assertRedirect('/my-business/booking/checkout');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_customer_details_are_validated_on_the_server_with_indonesian_messages(): void
    {
        $this->selectSlot();

        $this->post('/my-business/booking/checkout', ['namapelanggan' => '', 'email' => 'bukan-email', 'nomorhp' => '08123'])
            ->assertSessionHasErrors([
                'namapelanggan' => 'Nama lengkap wajib diisi.',
                'email' => 'Email harus berupa alamat email yang valid.',
                'nomorhp' => 'Nomor WhatsApp / HP harus berisi 10–15 digit angka (boleh diawali +62).',
            ]);
        $this->assertDatabaseCount('payments', 0);

        $this->post('/my-business/booking/checkout', ['nomorhp' => '+62 812-3456-7890'] + $this->customer())->assertRedirect();
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_customer_who_leaves_the_payment_page_can_resume_their_unpaid_checkout(): void
    {
        $this->selectSlot();
        $this->post('/my-business/booking/checkout', $this->customer())->assertRedirect();
        $payment = Payment::withoutGlobalScopes()->sole();

        $this->get('/my-business')->assertOk()
            ->assertSee('Anda masih memiliki pembayaran yang belum selesai')
            ->assertSee('/my-business/booking/payment/'.$payment->order_id, false);

        $payment->update(['status' => 'gagal']);

        $this->get('/my-business')->assertOk()->assertDontSee('Anda masih memiliki pembayaran yang belum selesai');
    }

    public function test_cancelling_a_checkout_that_was_paid_meanwhile_returns_to_the_payment_page(): void
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'order_id' => 'BQ-TEST-PAID',
            'tipe' => 'booking',
            'metode' => 'midtrans',
            'jumlah' => 200000,
            'status' => 'sukses',
            'expired_at' => now()->addMinutes(15),
        ]);
        Booking::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $this->schedule->id,
            'idpayment' => $payment->id,
            'namapelanggan' => 'Charlie',
            'email' => 'charlie@example.com',
            'nomorhp' => '081234567810',
            'tanggalbooking' => $this->tomorrow,
            'jam' => '10:00',
            'status' => 'paid',
        ]);

        $this->grantCustomerAccess($payment, AccessProfile::Checkout)
            ->post('/my-business/booking/payment/'.$payment->order_id.'/cancel')
            ->assertRedirect('/my-business/booking/payment/'.$payment->order_id)
            ->assertSessionHas('error');

        $this->assertSame('sukses', $payment->fresh()->status);
    }

    private function selectService(): void
    {
        session(['booking' => ['tenant_id' => $this->tenant->id, 'service_id' => $this->service->id]]);
    }

    private function selectSlot(): void
    {
        session(['booking' => [
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'tanggal' => $this->tomorrow,
            'jam' => ['10:00'],
            'schedule_ids' => [$this->schedule->id],
        ]]);
    }

    private function fingerprintFromCheckoutPage(): string
    {
        $html = $this->get('/my-business/booking/checkout')->assertOk()->getContent();
        $this->assertSame(1, preg_match('/name="checkout_fingerprint" value="([a-f0-9]{64})"/', $html, $match));

        return $match[1];
    }

    /** @return array{namapelanggan: string, email: string, nomorhp: string} */
    private function customer(): array
    {
        return ['namapelanggan' => 'John Doe', 'email' => 'john@example.com', 'nomorhp' => '081234567890'];
    }
}

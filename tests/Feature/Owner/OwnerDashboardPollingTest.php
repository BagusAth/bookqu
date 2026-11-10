<?php

namespace Tests\Feature\Owner;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Beranda live refresh (`owner.dashboard.polling`). BQ-136 replaced the old all-time figures and
 * recent-activity list with the owner-portal.md §8.1 contract (figures, actions, today, tomorrow);
 * these tests keep their original intent on that contract.
 */
class OwnerDashboardPollingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $tenant;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->tenant = Tenant::create([
            'iduser' => $this->user->id,
            'namabisnis' => 'Polling Business',
            'jenisbisnis' => 'Layanan',
            'slug' => 'polling-business',
            'alamat' => 'Jalan Test',
            'nomorhp' => '08123456789',
        ]);
        // The page shows sections 2–4 once the new owner guide is done (AC-BRD-05).
        $this->tenant->forceFill(['owner_guide_completed_at' => now()])->save();

        $this->service = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Layanan Polling',
            'harga' => 100000,
            'durasi' => 60,
        ]);
    }

    private function todayBooking(array $attributes = [], ?Tenant $tenant = null, ?Service $service = null): Booking
    {
        $tenant ??= $this->tenant;
        $service ??= $this->service;
        $schedule = Schedule::create([
            'idtenant' => $tenant->id,
            'idlayanan' => $service->id,
            'tanggal' => Carbon::today(),
            'jam_mulai' => $attributes['jam'] ?? '10:00:00',
            'jam_selesai' => '23:59:00',
            'status' => 'tersedia',
        ]);

        return Booking::create(array_merge([
            'idtenant' => $tenant->id,
            'idlayanan' => $service->id,
            'idschedule' => $schedule->id,
            'namapelanggan' => 'John Doe',
            'email' => 'john@example.com',
            'nomorhp' => '08123',
            'tanggalbooking' => Carbon::today(),
            'jam' => '10:00:00',
            'status' => 'pending',
        ], $attributes));
    }

    private function figure(string $key): ?array
    {
        return collect($this->actingAs($this->user)->getJson(route('owner.dashboard.polling'))->assertOk()->json('data.figures'))
            ->firstWhere('key', $key);
    }

    public function test_owner_can_access_polling_endpoint_and_get_json()
    {
        $this->actingAs($this->user)
            ->getJson(route('owner.dashboard.polling'))
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['figures', 'actions', 'today', 'tomorrow' => ['count', 'first_time', 'first_customer'], 'next'],
            ]);
    }

    public function test_revenue_uses_payment_status_sukses()
    {
        foreach (['pending' => 100000, 'sukses' => 50000] as $status => $amount) {
            Payment::create([
                'idtenant' => $this->tenant->id,
                'tipe' => 'booking',
                'metode' => 'midtrans',
                'jumlah' => $amount,
                'status' => $status,
            ]);
        }

        $this->assertSame('Rp 50.000', $this->figure('revenue')['value']);
    }

    public function test_html_customer_names_stay_raw_data_in_polling_and_text_on_the_dashboard(): void
    {
        $payload = '<img src=x onerror=alert(1)>';
        $this->todayBooking(['namapelanggan' => $payload, 'jam' => '13:00:00']);

        $this->actingAs($this->user)
            ->getJson(route('owner.dashboard.polling'))
            ->assertJsonPath('data.today.0.customer', $payload)
            ->assertJsonPath('data.today.0.start', '13:00');

        $this->get(route('owner.dashboard'))
            ->assertOk()
            ->assertDontSee($payload, false)
            ->assertSee('x-text="row.customer"', false)
            ->assertDontSee('x-html', false);
    }

    public function test_booking_status_paid_is_returned_and_dynamic()
    {
        $booking = $this->todayBooking();

        $this->actingAs($this->user)->getJson(route('owner.dashboard.polling'))
            ->assertJsonPath('data.today.0.status.label', 'Menunggu bayar');

        $booking->update(['status' => 'paid']);

        $this->getJson(route('owner.dashboard.polling'))
            ->assertJsonPath('data.today.0.status.label', 'Terkonfirmasi');
    }

    public function test_polling_does_not_mutate_database()
    {
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('payments', 0);

        $this->actingAs($this->user)->getJson(route('owner.dashboard.polling'));

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_other_tenant_data_is_not_read()
    {
        $otherUser = User::factory()->create(['role' => 'owner']);
        $otherTenant = Tenant::create([
            'iduser' => $otherUser->id,
            'namabisnis' => 'Other Business',
            'slug' => 'other-business',
            'jenisbisnis' => 'Layanan',
            'alamat' => 'Alamat',
            'nomorhp' => '08123',
        ]);
        $otherService = Service::create(['idtenant' => $otherTenant->id, 'namalayanan' => 'Layanan', 'harga' => 1000, 'durasi' => 60]);
        $this->todayBooking(['namapelanggan' => 'Other Customer'], $otherTenant, $otherService);

        $this->assertSame('0', $this->figure('bookings')['value']);
        $this->getJson(route('owner.dashboard.polling'))->assertJsonPath('data.today', []);
    }

    public function test_owner_dashboard_view_displays_all_business_summary_components()
    {
        $this->actingAs($this->user)
            ->get(route('owner.dashboard'))
            ->assertStatus(200)
            ->assertSee('Booking hari ini')
            ->assertSee('Menunggu pembayaran')
            ->assertSee('Pendapatan hari ini')
            ->assertSee('Slot terisi hari ini')
            ->assertSee('Perlu tindakan')
            ->assertSee('Jadwal hari ini')
            ->assertSee('Link booking Anda')
            ->assertSee('Aksi cepat');
    }
}

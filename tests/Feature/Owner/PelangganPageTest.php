<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Booking;
use App\Models\CustomerNote;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BQ-137 (owner-portal.md §8.5): the Pelanggan page and the customer panel.
 */
class PelangganPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Tenant $tenant;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 09:00:00', 'Asia/Jakarta'));

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id]);
        app(CreateTrialSubscription::class)->execute($this->tenant);
        $this->service = Service::factory()->create(['idtenant' => $this->tenant->id, 'harga' => 100000, 'durasi' => 60]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function booking(array $attributes = [], ?Tenant $tenant = null): Booking
    {
        $tenant ??= $this->tenant;
        $service = $tenant->is($this->tenant) ? $this->service : Service::factory()->create(['idtenant' => $tenant->id]);
        $slot = Schedule::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id]);

        return Booking::factory()->create(array_merge([
            'idtenant' => $tenant->id,
            'idlayanan' => $service->id,
            'idschedule' => $slot->id,
            'namapelanggan' => 'Rani Putri',
            'email' => 'rani@example.com',
            'nomorhp' => '081234567890',
            'tanggalbooking' => '2026-11-10',
            'jam' => '10:00:00',
            'status' => 'paid',
            'created_at' => now()->subDays(10),
        ], $attributes));
    }

    private function pay(Booking $booking, float $amount, string $status = 'sukses'): Payment
    {
        $payment = Payment::create([
            'idtenant' => $booking->idtenant, 'idbooking' => $booking->id, 'tipe' => 'booking', 'jumlah' => $amount,
            'status' => $status, 'metode' => 'manual', 'order_id' => 'ORD-'.$booking->id.'-'.uniqid(),
        ]);
        $booking->forceFill(['idpayment' => $payment->id])->save();

        return $payment;
    }

    private function kasir(): User
    {
        $kasir = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $kasir->id, 'role' => 'kasir', 'accepted_at' => now()]);

        return $kasir;
    }

    public function test_page_shows_summary_quick_filters_and_the_customer_list(): void
    {
        $this->booking(['created_at' => now()->subMonths(3), 'tanggalbooking' => '2026-08-01']);
        $this->booking(['tanggalbooking' => '2026-11-12']);
        $this->booking(['namapelanggan' => 'Dewi', 'email' => 'dewi@example.com', 'nomorhp' => '0899', 'created_at' => now()->subDays(2)]);

        $this->actingAs($this->owner)->get(route('owner.customers'))
            ->assertOk()
            ->assertSee('Total pelanggan')
            ->assertSee('Baru bulan ini')
            ->assertSee('Kembali lagi')
            ->assertSee(route('owner.customers', ['filter' => 'new']))
            ->assertSee(route('owner.customers', ['filter' => 'regular']))
            ->assertSee(route('owner.customers', ['filter' => 'dormant']))
            ->assertSee('Rani Putri')
            ->assertSee('rani@example.com')
            ->assertSee('Dewi')
            ->assertViewHas('summary', ['total' => 2, 'new' => 1, 'returning' => 1]);
    }

    public function test_empty_state_explains_where_customers_come_from(): void
    {
        $this->actingAs($this->owner)->get(route('owner.customers'))
            ->assertOk()
            ->assertSee('Pelanggan muncul otomatis setelah booking pertama.');
    }

    public function test_ac_plg_01_one_row_per_identity_matching_the_note_identifier(): void
    {
        $this->booking(['email' => 'Rani@Example.com ']);
        $this->booking(['email' => 'rani@example.com', 'nomorhp' => '0811111']);
        $this->booking(['email' => '', 'nomorhp' => '0822 333', 'namapelanggan' => 'Tanpa Email']);

        $response = $this->actingAs($this->owner)->get(route('owner.customers'))->assertOk();
        $rows = collect($response->viewData('customers')->items());
        $this->assertCount(2, $rows);
        $this->assertSame(2, (int) $rows->firstWhere('identifier', 'rani@example.com')->total_bookings);

        $this->postJson(route('owner.customers.note'), ['customer_identifier' => 'rani@example.com', 'notes' => 'Suka sesi pagi'])->assertOk();
        $this->assertDatabaseHas('customer_notes', ['idtenant' => $this->tenant->id, 'customer_identifier' => 'rani@example.com']);

        $this->getJson(route('owner.customers.panel', ['identifier' => 'rani@example.com']))
            ->assertOk()
            ->assertJsonPath('identifier', 'rani@example.com')
            ->assertJsonPath('note', 'Suka sesi pagi');
    }

    public function test_quick_filters_select_new_regular_and_dormant_customers(): void
    {
        $this->booking(['namapelanggan' => 'Pelanggan Baru', 'email' => 'baru@example.com', 'created_at' => now()->subDays(3), 'tanggalbooking' => '2026-11-18']);
        foreach (['2026-09-01', '2026-10-01', '2026-11-01'] as $date) {
            $this->booking(['namapelanggan' => 'Pelanggan Tetap', 'email' => 'tetap@example.com', 'created_at' => now()->subMonths(4), 'tanggalbooking' => $date]);
        }
        $this->booking(['namapelanggan' => 'Lama Hilang', 'email' => 'hilang@example.com', 'created_at' => now()->subMonths(6), 'tanggalbooking' => '2026-08-01']);

        $this->actingAs($this->owner)->get(route('owner.customers', ['filter' => 'new']))
            ->assertSee('Pelanggan Baru')->assertDontSee('Pelanggan Tetap')->assertDontSee('Lama Hilang');
        $this->get(route('owner.customers', ['filter' => 'regular']))
            ->assertSee('Pelanggan Tetap')->assertDontSee('Pelanggan Baru')->assertDontSee('Lama Hilang');
        $this->get(route('owner.customers', ['filter' => 'dormant']))
            ->assertSee('Lama Hilang')->assertDontSee('Pelanggan Baru')->assertDontSee('Pelanggan Tetap');
    }

    public function test_ac_plg_02_totals_use_the_paid_amounts(): void
    {
        $paid = $this->booking();
        $this->pay($paid, 85000);
        $failed = $this->booking(['status' => 'pending']);
        $this->pay($failed, 100000, 'gagal');
        $cancelled = $this->booking(['status' => 'cancelled']);
        $this->pay($cancelled, 120000);

        $response = $this->actingAs($this->owner)->get(route('owner.customers'))->assertOk()->assertSee('Rp 85.000');
        $this->assertSame(85000.0, (float) collect($response->viewData('customers')->items())->first()->total_spent);

        $this->getJson(route('owner.customers.panel', ['identifier' => 'rani@example.com']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Total dibayar', 'value' => 'Rp 85.000'])
            ->assertJsonFragment(['label' => 'Dibatalkan', 'value' => '1']);
    }

    public function test_ac_plg_03_a_kasir_sees_no_spending_totals(): void
    {
        $this->pay($this->booking(), 85000);
        $kasir = $this->kasir();

        $this->actingAs($kasir)->get(route('owner.customers'))
            ->assertOk()
            ->assertSee('Rani Putri')
            ->assertDontSee('Total dibayar')
            ->assertDontSee('85.000');

        $panel = $this->getJson(route('owner.customers.panel', ['identifier' => 'rani@example.com']))->assertOk();
        $this->assertStringNotContainsString('85.000', (string) $panel->getContent());
        $this->assertStringNotContainsString('Total dibayar', (string) $panel->getContent());
        $this->assertSame([], $panel->json('payments'));
    }

    public function test_the_panel_gives_contact_links_figures_history_and_the_walk_in_prefill(): void
    {
        $this->pay($this->booking(['booking_code' => 'BKQ-RANI1']), 85000);
        $this->booking(['status' => 'cancelled', 'booking_code' => 'BKQ-RANI2']);

        $this->actingAs($this->owner)->getJson(route('owner.customers.panel', ['identifier' => 'rani@example.com']))
            ->assertOk()
            ->assertJsonPath('name', 'Rani Putri')
            ->assertJsonPath('phone', '081234567890')
            ->assertJsonPath('whatsapp_url', 'https://wa.me/6281234567890')
            ->assertJsonPath('email_url', 'mailto:rani@example.com')
            ->assertJsonPath('note_url', route('owner.customers.note'))
            ->assertJsonFragment(['label' => 'Booking', 'value' => '2'])
            ->assertJsonFragment(['label' => 'Tidak datang', 'value' => '0'])
            ->assertJsonFragment(['code' => 'BKQ-RANI2', 'status' => ['label' => 'Dibatalkan', 'tone' => 'neutral']])
            ->assertJsonPath('payments.0.amount', 'Rp 85.000')
            // AC-PLG-04
            ->assertJsonPath('walkin', ['namapelanggan' => 'Rani Putri', 'nomorhp' => '081234567890', 'email' => 'rani@example.com']);
    }

    public function test_ac_plg_04_the_walk_in_form_accepts_the_customer_prefill(): void
    {
        $this->booking();

        $this->actingAs($this->owner)->get(route('owner.customers'))
            ->assertOk()
            ->assertSee('x-on:walkin-prefill.window', false)
            ->assertSee('Buat booking');
    }

    public function test_the_panel_never_shows_another_business_customer(): void
    {
        $other = Tenant::factory()->create();
        $this->booking(['email' => 'asing@example.com'], $other);

        $this->actingAs($this->owner)->getJson(route('owner.customers.panel', ['identifier' => 'asing@example.com']))->assertNotFound();
        $this->getJson(route('owner.customers.panel'))->assertStatus(400);
    }

    public function test_search_and_identifiers_are_escaped(): void
    {
        $this->booking(['namapelanggan' => "O'Neil \"<b>\"", 'email' => "o'neil@example.com"]);

        $html = (string) $this->actingAs($this->owner)
            ->get(route('owner.customers', ['search' => '"><script>alert(1)</script>']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);

        $html = (string) $this->get(route('owner.customers'))->assertOk()->getContent();
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringNotContainsString("openCustomer('", $html);
        $this->assertStringContainsString('open-customer-panel', $html);
    }

    public function test_a_note_longer_than_2000_characters_is_refused(): void
    {
        $this->booking();

        $this->actingAs($this->owner)->postJson(route('owner.customers.note'), ['customer_identifier' => 'rani@example.com', 'notes' => str_repeat('a', 2001)])
            ->assertUnprocessable();
        $this->assertSame(0, CustomerNote::withoutGlobalScopes()->count());
    }

    public function test_shell_search_opens_the_customer_panel(): void
    {
        $this->booking();

        $this->actingAs($this->owner)->getJson(route('owner.search', ['q' => 'Rani']))
            ->assertOk()
            ->assertJsonPath('customers.0.panel_url', route('owner.customers.panel', ['identifier' => 'rani@example.com']));
    }
}

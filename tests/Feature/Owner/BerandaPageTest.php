<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\TenantPayoutAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * BQ-136 (owner-portal.md §8.1, FR-DASH-008): Beranda shows today, what needs action and the guide.
 */
class BerandaPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Tenant $tenant;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 10:30:00', 'Asia/Jakarta'));

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id, 'slug' => 'studio-beranda']);
        $this->subscribe('medium', 'active');
        $this->service = Service::factory()->create(['idtenant' => $this->tenant->id, 'namalayanan' => 'Foto Studio', 'harga' => 100000, 'durasi' => 60]);
    }

    private function subscribe(string $planName, string $status, ?Carbon $trialEnds = null, int $maxBooking = 0): Subscription
    {
        Subscription::query()->delete();
        $plan = Plan::updateOrCreate(['namapaket' => $planName], ['hargabulanan' => 100000, 'maxlayanan' => 0, 'maxbooking' => $maxBooking, 'isunlimited' => $maxBooking === 0]);

        return Subscription::create([
            'idtenant' => $this->tenant->id, 'idplan' => $plan->id, 'status' => $status,
            'trial_berakhir' => $status === 'trial' ? ($trialEnds ?? now()->addDays(10)) : null,
            'langganan_mulai' => now()->subMonth(), 'langganan_berakhir' => $status === 'trial' ? null : now()->addMonth(),
        ]);
    }

    /** Ticks every guide step, so the page shows the day instead of the guide. */
    private function finishGuide(): void
    {
        OpeningHour::create(['idtenant' => $this->tenant->id, 'day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '17:00']);
        Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => '2026-11-25', 'jam_mulai' => '09:00:00', 'jam_selesai' => '09:59:00']);
        Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => '2026-11-30', 'jam_mulai' => '09:00:00', 'jam_selesai' => '09:59:00']);
        TenantPayoutAccount::create(['idtenant' => $this->tenant->id, 'bank_kode' => 'bca', 'bank_nama' => 'BCA', 'nomor_rekening' => '1234567890', 'nama_pemilik_rekening' => 'Pemilik', 'is_default' => true, 'changed_at' => now()->subDays(3)]);
        $this->tenant->forceFill(['booking_link_shared_at' => now()->subDay()])->save();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function booking(string $date, string $start, string $end, array $attributes = []): Booking
    {
        $slot = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => $date, 'jam_mulai' => $start, 'jam_selesai' => $end]);

        return Booking::factory()->create(array_merge([
            'idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'idschedule' => $slot->id,
            'tanggalbooking' => $date, 'jam' => $start, 'status' => 'paid', 'namapelanggan' => 'Rani',
        ], $attributes));
    }

    private function member(string $role): User
    {
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $user->id, 'role' => $role, 'accepted_at' => now()]);

        return $user;
    }

    public function test_ac_brd_01_one_banner_by_priority(): void
    {
        $this->finishGuide();

        $html = (string) $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk()->getContent();
        $this->assertSame(0, substr_count($html, 'data-beranda-banner'));

        // 5. monthly booking limit at 80% or more
        $this->subscribe('small', 'active', maxBooking: 5);
        foreach (range(1, 4) as $i) {
            $this->booking('2026-11-0'.$i, '09:00:00', '09:59:00', ['status' => 'completed']);
        }
        $this->assertBanner('booking-limit');

        // 4. no payout account (Pemilik only) beats the limit
        TenantPayoutAccount::withoutGlobalScopes()->delete();
        $this->assertBanner('payout-account');
        $kasir = (string) $this->actingAs($this->member('kasir'))->get(route('owner.dashboard'))->getContent();
        $this->assertStringNotContainsString('data-beranda-banner="payout-account"', $kasir);
        $this->actingAs($this->owner);

        // 3. a service without an open slot in the next 7 days
        Service::factory()->create(['idtenant' => $this->tenant->id, 'namalayanan' => 'Tanpa Slot']);
        $this->assertBanner('slot-runway');

        // 2. trial ends within 3 days
        $this->subscribe('pro', 'trial', now()->addDays(2));
        $this->assertBanner('trial-ending');

        // 1. subscription ended
        $this->subscribe('pro', 'trial', now()->subDay());
        $this->assertBanner('subscription-ended')->assertSee('Langganan telah berakhir');
    }

    private function assertBanner(string $kind): TestResponse
    {
        $response = $this->get(route('owner.dashboard'))->assertOk();
        $html = (string) $response->getContent();
        $this->assertSame(1, substr_count($html, 'data-beranda-banner='), 'exactly one banner');
        $this->assertStringContainsString('data-beranda-banner="'.$kind.'"', $html);

        return $response;
    }

    public function test_ac_brd_02_revenue_today_is_the_sum_of_successful_booking_payments_today(): void
    {
        $this->finishGuide();
        $paid = $this->booking('2026-11-20', '09:00:00', '09:59:00');
        Payment::factory()->create(['idtenant' => $this->tenant->id, 'idbooking' => $paid->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 85000]);
        Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 40000]);
        Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'booking', 'status' => 'gagal', 'jumlah' => 99000]);
        Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 77000, 'created_at' => now()->subDay()]);
        Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'subscription', 'status' => 'sukses', 'jumlah' => 149000]);
        $other = Tenant::factory()->create();
        Payment::factory()->create(['idtenant' => $other->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 55000]);

        $this->actingAs($this->owner)->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee('Pendapatan hari ini')
            ->assertSee('Rp 125.000');

        $this->getJson(route('owner.dashboard.polling'))
            ->assertOk()
            ->assertJsonFragment(['key' => 'revenue', 'label' => 'Pendapatan hari ini', 'value' => 'Rp 125.000']);
    }

    public function test_ac_brd_03_a_kasir_sees_no_amount(): void
    {
        $this->finishGuide();
        $paid = $this->booking('2026-11-20', '09:00:00', '09:59:00');
        Payment::factory()->create(['idtenant' => $this->tenant->id, 'idbooking' => $paid->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 85000]);
        $kasir = $this->member('kasir');

        $this->actingAs($kasir)->get(route('owner.dashboard'))
            ->assertOk()
            ->assertDontSee('Pendapatan hari ini')
            ->assertDontSee('85.000')
            ->assertSee('Booking hari ini');

        $json = (string) $this->getJson(route('owner.dashboard.polling'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Rp ', $json);
        $this->assertStringNotContainsString('revenue', $json);
    }

    public function test_ac_brd_04_needs_action_items_link_to_their_place_and_disappear_once_resolved(): void
    {
        $this->finishGuide();
        $ended = $this->booking('2026-11-19', '09:00:00', '09:59:00', ['booking_code' => 'BKQ-ENDED']);
        $cancellation = DB::table('booking_cancellation_requests')->insertGetId([
            'idtenant' => $this->tenant->id, 'booking_id' => $ended->id, 'reason' => 'Pelanggan sakit', 'status' => 'pending', 'created_at' => now(),
        ]);
        $review = Review::create(['idtenant' => $this->tenant->id, 'idbooking' => $ended->id, 'rating' => 2, 'komentar' => 'Kurang', 'is_hidden' => false]);
        $payment = Payment::factory()->create(['idtenant' => $this->tenant->id, 'idbooking' => $ended->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 100000]);
        $refund = RefundRequest::create([
            'idtenant' => $this->tenant->id, 'idpayment' => $payment->id, 'booking_id' => $ended->id, 'reason' => 'Batal',
            'bank_nama' => 'BCA', 'nomor_rekening' => '123', 'nama_pemilik_rekening' => 'Rani', 'status' => 'open',
        ]);

        $actions = collect($this->actingAs($this->owner)->getJson(route('owner.dashboard.polling'))->assertOk()->json('data.actions'))->keyBy('key');
        $this->assertSame(1, $actions['refunds']['count']);
        $this->assertSame(1, $actions['cancellations']['count']);
        $this->assertSame(route('owner.bookings').'#permintaan-pembatalan', $actions['cancellations']['url']);
        $this->assertSame(1, $actions['ended']['count']);
        $this->assertSame(route('owner.bookings', ['status' => 'paid']), $actions['ended']['url']);
        $this->assertSame(1, $actions['reviews']['count']);
        $this->assertSame(route('owner.reviews'), $actions['reviews']['url']);

        DB::table('booking_cancellation_requests')->where('id', $cancellation)->update(['status' => 'rejected', 'decided_at' => now()]);
        $review->update(['balasan' => 'Maaf, kami perbaiki.', 'dibalas_pada' => now()]);
        $refund->update(['status' => 'rejected', 'decided_at' => now()]);
        $ended->update(['status' => 'completed']);

        $this->assertSame([], $this->getJson(route('owner.dashboard.polling'))->json('data.actions'));
        $this->get(route('owner.dashboard'))->assertSee('Tidak ada yang perlu ditindak');
    }

    public function test_ac_brd_05_the_guide_shows_until_its_steps_are_done_and_never_again(): void
    {
        $this->tenant->forceFill(['namabisnis' => '', 'alamat' => ''])->save();
        Service::withoutGlobalScopes()->delete();

        $this->actingAs($this->owner)->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee('data-owner-guide', false)
            ->assertSee('Lengkapi profil bisnis')
            ->assertSee('Tambah layanan pertama')
            ->assertSee('Atur jam buka dan buka slot')
            ->assertSee('Bagikan link booking')
            ->assertDontSee('Booking hari ini');

        $this->tenant->forceFill(['namabisnis' => 'Studio Beranda', 'jenisbisnis' => 'Foto', 'nomorhp' => '0812', 'alamat' => 'Jl. Mawar'])->save();
        $this->service = Service::factory()->create(['idtenant' => $this->tenant->id]);
        OpeningHour::create(['idtenant' => $this->tenant->id, 'day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '17:00']);
        Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => '2026-11-25']);
        $this->get(route('owner.dashboard'))->assertSee('data-owner-guide', false);

        $this->postJson(route('owner.dashboard.link-shared'))->assertNoContent();
        $this->assertNotNull($this->tenant->fresh()->booking_link_shared_at);

        $this->get(route('owner.dashboard'))->assertDontSee('data-owner-guide', false)->assertSee('Booking hari ini');
        $this->assertNotNull($this->tenant->fresh()->owner_guide_completed_at);

        Service::withoutGlobalScopes()->delete();
        $this->get(route('owner.dashboard'))->assertDontSee('data-owner-guide', false);
    }

    public function test_ac_brd_06_refresh_runs_only_while_visible_and_renders_text(): void
    {
        $this->finishGuide();
        $payload = '<img src=x onerror=alert(1)>';
        $this->booking('2026-11-20', '13:00:00', '13:59:00', ['namapelanggan' => $payload]);

        $this->actingAs($this->owner)->getJson(route('owner.dashboard.polling'))
            ->assertOk()
            ->assertJsonPath('data.today.0.customer', $payload);

        $html = (string) $this->get(route('owner.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('x-data="ownerBeranda(', $html);
        $this->assertStringNotContainsString($payload, $html);
        $this->assertStringNotContainsString('innerHTML', $html);
        $this->assertStringNotContainsString('x-html', $html);

        $script = (string) file_get_contents(public_path('js/owner-ui.js'));
        $this->assertStringContainsString('visibilitychange', $script);
        $this->assertStringContainsString("Alpine.data('ownerBeranda'", $script);
    }

    public function test_ac_brd_07_the_page_contains_no_chart(): void
    {
        $this->finishGuide();
        $this->booking('2026-11-20', '09:00:00', '09:59:00');

        $this->actingAs($this->owner)->get(route('owner.dashboard'))
            ->assertOk()
            ->assertDontSee('chart.umd', false)
            ->assertDontSee('<canvas', false);
    }

    public function test_today_lists_bookings_in_time_order_with_the_current_one_marked_and_tomorrow_summary(): void
    {
        $this->finishGuide();
        $this->booking('2026-11-20', '13:00:00', '13:59:00', ['namapelanggan' => 'Siang']);
        $this->booking('2026-11-20', '10:00:00', '10:59:00', ['namapelanggan' => 'Sekarang']);
        $this->booking('2026-11-20', '08:00:00', '08:59:00', ['namapelanggan' => 'Batal', 'status' => 'cancelled']);
        $this->booking('2026-11-21', '09:00:00', '09:59:00', ['namapelanggan' => 'Besok Pagi']);
        $this->booking('2026-11-21', '15:00:00', '15:59:00', ['namapelanggan' => 'Besok Sore']);

        $data = $this->actingAs($this->owner)->getJson(route('owner.dashboard.polling'))->assertOk()->json('data');

        $this->assertSame(['Sekarang', 'Siang'], array_column($data['today'], 'customer'));
        $this->assertTrue($data['today'][0]['in_progress']);
        $this->assertFalse($data['today'][1]['in_progress']);
        $this->assertSame('Terkonfirmasi', $data['today'][0]['status']['label']);
        $this->assertSame(2, $data['tomorrow']['count']);
        $this->assertSame('09:00', $data['tomorrow']['first_time']);

        $this->get(route('owner.dashboard'))->assertSee('Sedang berlangsung')->assertSee('Besok');
    }

    public function test_an_empty_day_names_the_next_booking(): void
    {
        $this->finishGuide();
        $this->booking('2026-11-23', '09:00:00', '09:59:00', ['namapelanggan' => 'Minggu Depan']);

        $this->actingAs($this->owner)->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada booking hari ini')
            ->assertSee('Minggu Depan');
    }

    public function test_the_link_card_and_quick_actions_follow_role_and_plan(): void
    {
        $this->finishGuide();

        $this->actingAs($this->owner)->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee('studio-beranda')
            ->assertSee('Salin link')
            ->assertSee('data-quick-action="walkin"', false)
            ->assertSee('data-quick-action="service"', false)
            ->assertSee('data-quick-action="team"', false);

        $this->actingAs($this->member('kasir'))->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee('data-quick-action="walkin"', false)
            ->assertDontSee('data-quick-action="service"', false)
            ->assertDontSee('data-quick-action="team"', false);

        $this->subscribe('small', 'active');
        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertDontSee('data-quick-action="team"', false);
    }

    public function test_marking_the_link_shared_only_touches_the_member_business(): void
    {
        $other = Tenant::factory()->create();

        $this->actingAs($this->member('kasir'))->postJson(route('owner.dashboard.link-shared'))->assertNoContent();

        $this->assertNotNull($this->tenant->fresh()->booking_link_shared_at);
        $this->assertNull($other->fresh()->booking_link_shared_at);
    }
}

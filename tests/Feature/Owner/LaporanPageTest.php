<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Refund;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * BQ-147 (owner-portal.md §8.13, FR-ANALYTICS-007): Laporan with one period filter, the tabs
 * Ringkasan, Pendapatan, Okupansi, Layanan and Pelanggan, and a CSV export per tab.
 */
class LaporanPageTest extends TestCase
{
    use RefreshDatabase;

    private const TABS = ['ringkasan', 'pendapatan', 'okupansi', 'layanan', 'pelanggan'];

    private User $owner;

    private Tenant $tenant;

    private Service $photo;

    private Service $makeup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 08:00:00'));
        config(['modules.reports' => 'released']);

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id]);
        $this->subscribe('medium');
        $this->photo = Service::factory()->create(['idtenant' => $this->tenant->id, 'namalayanan' => 'Foto Studio']);
        $this->makeup = Service::factory()->create(['idtenant' => $this->tenant->id, 'namalayanan' => 'Rias Wajah']);
    }

    private function subscribe(string $planName): void
    {
        Subscription::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->delete();
        $plan = Plan::updateOrCreate(['namapaket' => $planName], ['hargabulanan' => 100000, 'maxlayanan' => 0, 'maxbooking' => 0, 'isunlimited' => true]);
        Subscription::create([
            'idtenant' => $this->tenant->id, 'idplan' => $plan->id, 'status' => 'active',
            'langganan_mulai' => now()->subMonth(), 'langganan_berakhir' => now()->addMonth(),
        ]);
    }

    /**
     * One booking of one or more slots that share one payment.
     *
     * @param  array<string, mixed>  $payment
     * @param  array<string, mixed>  $booking
     * @return array{payment: ?Payment, bookings: list<Booking>, schedules: list<Schedule>}
     */
    private function booking(Service $service, string $date, int $slots = 1, string $status = 'paid', array $payment = [], array $booking = [], ?Tenant $tenant = null): array
    {
        $tenant ??= $this->tenant;
        $paymentRow = $payment === [] ? null : Payment::factory()->create(array_merge([
            'idtenant' => $tenant->id, 'tipe' => 'booking', 'status' => 'sukses', 'provider' => 'singapay', 'metode' => 'singapay',
        ], $payment));
        if ($paymentRow !== null) {
            DB::table('payments')->where('id', $paymentRow->id)->update(['created_at' => $payment['created_at'] ?? $date.' 09:00:00']);
        }

        $bookings = [];
        $schedules = [];
        for ($slot = 0; $slot < $slots; $slot++) {
            $start = sprintf('%02d:00:00', 9 + $slot);
            $schedule = Schedule::factory()->create([
                'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'tanggal' => $date,
                'jam_mulai' => $start, 'jam_selesai' => sprintf('%02d:00:00', 10 + $slot),
            ]);
            $bookings[] = Booking::factory()->create(array_merge([
                'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'idschedule' => $schedule->id,
                'idpayment' => $paymentRow?->id, 'tanggalbooking' => $date, 'jam' => $start, 'status' => $status,
                'booking_code' => 'BKQ-'.Str::upper(Str::random(8)),
            ], $booking));
            $schedules[] = $schedule;
        }
        DB::table('bookings')->whereIn('id', array_map(fn (Booking $row) => $row->id, $bookings))
            ->update(['created_at' => $booking['created_at'] ?? $date.' 08:00:00']);

        return ['payment' => $paymentRow, 'bookings' => $bookings, 'schedules' => $schedules];
    }

    private function refund(Payment $payment, Booking $booking, int $amount, string $date, ?string $cause = 'customer_request'): void
    {
        $refund = Refund::create([
            'booking_id' => $booking->id, 'payment_id' => $payment->id, 'jumlah' => $amount, 'status' => 'pending', 'cause' => $cause,
        ]);
        DB::table('refunds')->where('id', $refund->id)->update(['created_at' => $date.' 12:00:00']);
    }

    private function member(string $role): User
    {
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $user->id, 'role' => $role, 'accepted_at' => now()]);

        return $user;
    }

    /**
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function report(string $tab, array $query = []): array
    {
        $response = $this->actingAs($this->owner)->get(route('owner.analytics', ['tab' => $tab] + $query))->assertOk();

        return $response->viewData('report');
    }

    /**
     * @return list<list<string>>
     */
    private function csv(TestResponse $response): array
    {
        $content = (string) preg_replace('/^\xEF\xBB\xBF/', '', $response->streamedContent());

        return array_values(array_map(fn (string $line): array => str_getcsv($line, ',', '"', ''), array_filter(explode("\n", trim($content)), fn (string $line) => $line !== '')));
    }

    /**
     * Two payments this month (one online for three slots, one walk-in), plus rows that must not count.
     */
    private function seedMonth(): void
    {
        $this->booking($this->photo, '2026-11-05', 3, 'completed', ['jumlah' => 300000], ['email' => 'rani@example.com', 'namapelanggan' => 'Rani']);
        $walkIn = $this->booking($this->makeup, '2026-11-06', 1, 'paid', ['jumlah' => 100000, 'provider' => 'manual', 'metode' => 'cash'], ['email' => 'budi@example.com', 'namapelanggan' => 'Budi']);
        $this->booking($this->photo, '2026-11-07', 1, 'pending', ['jumlah' => 50000, 'status' => 'pending'], ['email' => 'cici@example.com', 'namapelanggan' => 'Cici']);
        $late = $this->booking($this->photo, '2026-11-08', 1, 'cancelled', ['jumlah' => 70000, 'status' => 'gagal'], ['email' => 'dodi@example.com', 'namapelanggan' => 'Dodi']);
        $this->refund($late['payment'], $late['bookings'][0], 70000, '2026-11-08', 'late_success');
        $this->refund($walkIn['payment'], $walkIn['bookings'][0], 100000, '2026-11-06', 'double-payment');
        Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'subscription', 'status' => 'sukses', 'jumlah' => 99000]);

        $previous = $this->booking($this->photo, '2026-10-10', 1, 'completed', ['jumlah' => 200000], ['email' => 'rani@example.com', 'namapelanggan' => 'Rani', 'created_at' => '2026-10-01 08:00:00']);
        $this->refund($previous['payment'], $previous['bookings'][0], 40000, '2026-11-10');
    }

    public function test_the_page_has_one_period_filter_and_five_tabs_and_the_schedule_report_redirects(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.analytics'))->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('name="periode"', false);
        foreach (['Ringkasan', 'Pendapatan', 'Okupansi', 'Layanan', 'Pelanggan'] as $label) {
            $response->assertSee($label);
        }
        $this->assertSame('ringkasan', $response->viewData('report')['tab']);

        $this->get('/owner/schedule-report?periode=bulan-lalu')
            ->assertStatus(301)
            ->assertRedirect(route('owner.analytics', ['tab' => 'okupansi', 'periode' => 'bulan-lalu']));
    }

    public function test_ac_lap_01_revenue_is_successful_booking_payments_minus_refunds_in_the_period(): void
    {
        $this->seedMonth();

        $revenue = $this->report('pendapatan')['revenue'];

        $this->assertEquals(400000, $revenue['gross']);
        $this->assertEquals(40000, $revenue['refunds']);
        $this->assertEquals(360000, $revenue['net']);
        $this->assertEquals(300000, $revenue['online']);
        $this->assertEquals(100000, $revenue['walkIn']);
        $this->assertEquals(360000, array_sum(array_column($revenue['series'], 'net')));
        $perService = collect($revenue['perService'])->keyBy('name');
        $this->assertEquals(260000, $perService['Foto Studio']['net']);
        $this->assertEquals(100000, $perService['Rias Wajah']['net']);

        $this->assertEquals(200000, $this->report('pendapatan', ['periode' => 'bulan-lalu'])['revenue']['gross']);
        $this->assertEquals(0, $this->report('pendapatan', ['periode' => 'bulan-lalu'])['revenue']['refunds']);
        $custom = $this->report('pendapatan', ['periode' => 'kustom', 'dari' => '2026-10-01', 'sampai' => '2026-11-30'])['revenue'];
        $this->assertEquals(560000, $custom['net']);
    }

    public function test_ac_lap_02_a_booking_of_several_slots_is_counted_once(): void
    {
        $this->seedMonth();

        $report = $this->report('layanan');

        // Rani's three slots, Budi, Cici and Dodi: four bookings this month.
        $this->assertSame(4, $report['summary']['bookings']);
        $services = collect($report['services'])->keyBy('name');
        $this->assertSame(3, $services['Foto Studio']['bookings']);
        $this->assertSame(1, $services['Rias Wajah']['bookings']);
        $this->assertSame('Foto Studio', $report['summary']['topService']);

        $rows = $this->csv($this->actingAs($this->owner)->get(route('owner.analytics.export', ['tab' => 'pendapatan']))->assertOk());
        $headers = array_values(array_filter($rows, fn (array $row) => in_array('Kode booking', $row, true)));
        $transactions = array_values(array_filter($rows, fn (array $row) => str_starts_with($row[0] ?? '', '2026-11-')));
        $this->assertSame(['Tanggal', 'Kode booking', 'Pelanggan', 'Layanan', 'Jumlah', 'Kanal', 'Jumlah slot'], $headers[0] ?? null);
        $this->assertCount(2, $transactions, 'one row per successful payment, never one per slot');
        $this->assertEquals(400000, array_sum(array_map(fn (array $row) => (float) $row[4], $transactions)));
    }

    public function test_ac_lap_03_export_and_data_are_refused_below_medium_and_small_sees_a_preview(): void
    {
        $this->seedMonth();
        $this->subscribe('small');

        $response = $this->actingAs($this->owner)->get(route('owner.analytics'))->assertOk()
            ->assertSee('Laporan tersedia di paket Medium')
            ->assertDontSee('Rp 360.000');
        $this->assertTrue($response->viewData('report')['locked']);
        $this->assertNull($response->viewData('report')['summary']);

        foreach (self::TABS as $tab) {
            $this->get(route('owner.analytics.export', ['tab' => $tab]))->assertRedirect(route('owner.subscription'));
        }

        $this->subscribe('medium');
        $kasir = $this->member('kasir');
        $this->actingAs($kasir)->get(route('owner.analytics'))->assertForbidden();
        $this->actingAs($kasir)->get(route('owner.analytics.export'))->assertForbidden();
        $this->actingAs($this->member('manajer'))->get(route('owner.analytics', ['tab' => 'pendapatan']))->assertOk()->assertSee('Rp 360.000');

        config(['modules.reports' => 'hidden']);
        $this->actingAs($this->owner)->get(route('owner.analytics'))->assertNotFound();
        $this->get(route('owner.analytics.export'))->assertNotFound();
        $this->get('/owner/schedule-report')->assertNotFound();
    }

    public function test_ac_lap_04_the_figures_of_one_period_are_the_same_on_every_tab(): void
    {
        $this->seedMonth();
        Review::create(['idtenant' => $this->tenant->id, 'idbooking' => Booking::withoutGlobalScopes()->where('namapelanggan', 'Budi')->value('id'), 'rating' => 4]);
        Review::create(['idtenant' => $this->tenant->id, 'idbooking' => Booking::withoutGlobalScopes()->where('namapelanggan', 'Rani')->value('id'), 'rating' => 5]);

        $summaries = array_map(fn (string $tab) => $this->report($tab)['summary'], self::TABS);
        foreach ($summaries as $summary) {
            $this->assertSame($summaries[0], $summary);
        }

        $summary = $summaries[0];
        $this->assertEquals(360000, $summary['revenue']);
        $this->assertSame(4, $summary['bookings']);
        $this->assertSame(3, $summary['newCustomers']);
        $this->assertEquals(25.0, $summary['cancellationRate']);
        $this->assertEquals(25.0, $summary['completionRate']);
        $this->assertEquals(4.5, $summary['averageRating']);

        $this->assertEquals($summary['revenue'], $this->report('pendapatan')['revenue']['net']);
        $services = $this->report('layanan')['services'];
        $this->assertSame($summary['bookings'], array_sum(array_column($services, 'bookings')));
        $this->assertEquals($summary['revenue'], array_sum(array_column($services, 'revenue')));
        $this->assertSame($summary['newCustomers'], $this->report('pelanggan')['customers']['new']);
    }

    public function test_okupansi_shows_the_rate_free_slots_and_busy_hours_of_the_period(): void
    {
        $this->booking($this->photo, '2026-11-16', 2, 'paid', ['jumlah' => 200000]);
        $this->booking($this->photo, '2026-11-17', 1, 'cancelled', ['jumlah' => 100000, 'status' => 'gagal']);
        Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->photo->id, 'tanggal' => '2026-11-18', 'jam_mulai' => '13:00:00', 'jam_selesai' => '14:00:00']);
        Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $this->photo->id, 'tanggal' => '2026-12-01']);

        $occupancy = $this->report('okupansi')['occupancy'];

        $this->assertSame(4, $occupancy['total']);
        $this->assertSame(2, $occupancy['booked']);
        $this->assertSame(2, $occupancy['free']);
        $this->assertEquals(50.0, $occupancy['rate']);
        // 16 Nov 2026 is a Monday: the two booked slots start at 09:00 and 10:00.
        $this->assertSame(1, $occupancy['heatmap']['cells'][1][9]);
        $this->assertSame(1, $occupancy['heatmap']['cells'][1][10]);
        $this->assertSame(0, $occupancy['heatmap']['cells'][2][9] ?? 0);
    }

    public function test_pelanggan_splits_new_and_returning_customers(): void
    {
        $this->seedMonth();

        $customers = $this->report('pelanggan')['customers'];

        // Rani booked before November; Budi, Cici and Dodi are new this month.
        $this->assertSame(4, $customers['active']);
        $this->assertSame(3, $customers['new']);
        $this->assertSame(1, $customers['returning']);
    }

    public function test_each_tab_exports_formula_safe_rows_of_the_own_business_only(): void
    {
        $this->booking($this->photo, '2026-11-05', 1, 'paid', ['jumlah' => 150000], ['namapelanggan' => '=HYPERLINK("http://evil")', 'email' => 'evil@example.com']);
        $this->photo->update(['namalayanan' => '=cmd|calc']);
        $other = Tenant::factory()->create();
        $otherService = Service::factory()->create(['idtenant' => $other->id, 'namalayanan' => 'Layanan Lain']);
        $this->booking($otherService, '2026-11-05', 1, 'paid', ['jumlah' => 999000], ['namapelanggan' => 'Pelanggan Lain'], $other);

        foreach (self::TABS as $tab) {
            $response = $this->actingAs($this->owner)->get(route('owner.analytics.export', ['tab' => $tab]))->assertOk();
            $this->assertStringContainsString('laporan-'.$tab.'-', (string) $response->headers->get('Content-Disposition'));
            $content = $response->streamedContent();
            $this->assertStringNotContainsString('Pelanggan Lain', $content);
            $this->assertStringNotContainsString('999000', $content);
            $this->assertStringNotContainsString('Layanan Lain', $content);
            foreach ($this->csv($response) as $row) {
                foreach ($row as $cell) {
                    $this->assertDoesNotMatchRegularExpression('/^[=+\-@]/', $cell, "Unsafe cell in the {$tab} export");
                }
            }
        }

        $this->assertStringContainsString("'=HYPERLINK", $this->actingAs($this->owner)->get(route('owner.analytics.export', ['tab' => 'pendapatan']))->streamedContent());
        $this->assertStringContainsString("'=cmd|calc", $this->actingAs($this->owner)->get(route('owner.analytics.export', ['tab' => 'layanan']))->streamedContent());
    }
}

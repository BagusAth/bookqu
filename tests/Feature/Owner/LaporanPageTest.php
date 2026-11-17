<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Report\GetReport;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Refund;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\Unit;
use App\Models\User;
use App\Support\ReportPeriod;
use App\Support\TenantContext;
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

    public function test_occupancy_counts_seats_and_deduplicates_a_shared_units_time_for_pro(): void
    {
        config(['modules.units' => 'released']);
        $this->subscribe('pro');
        $this->photo->update(['kapasitas' => 4]);
        $this->booking($this->photo, '2026-11-09', booking: ['peserta' => 2]);
        $this->booking($this->makeup, '2026-11-09', status: 'cancelled');
        $unit = Unit::factory()->create(['idtenant' => $this->tenant->id, 'name' => '=Studio', 'is_active' => true]);
        $unit->services()->attach([$this->photo->id, $this->makeup->id]);
        $foreign = Tenant::factory()->create();
        Unit::factory()->create(['idtenant' => $foreign->id, 'name' => 'Unit asing', 'is_active' => true]);
        $this->booking(Service::factory()->create(['idtenant' => $foreign->id]), '2026-11-09', tenant: $foreign);

        $report = $this->report('okupansi');
        $this->assertSame('=Studio', $report['summary']['topUnit']);
        $this->assertSame(2, $report['occupancy']['seats']);
        $this->assertSame(5, $report['occupancy']['capacity']);
        $this->assertSame(40.0, $report['occupancy']['rate']);
        $this->assertSame([['name' => '=Studio', 'offeredMinutes' => 60, 'usedMinutes' => 60, 'rate' => 100.0]], $report['occupancy']['units']);

        $csv = $this->csv($this->get(route('owner.analytics.export', ['tab' => 'okupansi']))->assertOk());
        $this->assertContains(["'=Studio", '60', '60', '100'], $csv);
        $this->subscribe('medium');
        $this->assertNull($this->report('okupansi')['occupancy']['units']);
    }

    public function test_promo_usage_counts_each_successful_payment_once_for_its_tenant(): void
    {
        config(['modules.promos' => 'released']);
        $promo = ['promo' => ['id' => 1, 'code' => 'HEMAT', 'discount' => 20000]];
        $this->booking($this->photo, '2026-11-09', slots: 2, payment: ['price_breakdown' => $promo], booking: ['namapelanggan' => 'Rani', 'email' => 'rani@example.test']);
        $this->booking($this->photo, '2026-11-10', status: 'pending', payment: ['status' => 'pending', 'price_breakdown' => $promo]);
        $foreign = Tenant::factory()->create();
        $this->booking(Service::factory()->create(['idtenant' => $foreign->id]), '2026-11-10',
            payment: ['price_breakdown' => $promo], tenant: $foreign);

        $report = $this->report('pelanggan');
        $this->assertSame([['customer' => 'Rani', 'code' => 'HEMAT', 'uses' => 1, 'discount' => 20000]], $report['customers']['promos']);
        $csv = $this->csv($this->get(route('owner.analytics.export', ['tab' => 'pelanggan']))->assertOk());
        $this->assertContains(['Rani', 'HEMAT', '1', '20000'], $csv);
    }

    public function test_the_page_has_one_period_filter_and_five_tabs_and_the_schedule_report_redirects(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.analytics'))->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('periode=bulan-lalu', false);
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
            ->assertDontSee('Rp360.000');
        $this->assertTrue($response->viewData('report')['locked']);
        $this->assertNull($response->viewData('report')['summary']);

        foreach (self::TABS as $tab) {
            $this->get(route('owner.analytics.export', ['tab' => $tab]))->assertRedirect(route('owner.subscription'));
        }

        $this->subscribe('medium');
        $kasir = $this->member('kasir');
        $this->actingAs($kasir)->get(route('owner.analytics'))->assertForbidden();
        $this->actingAs($kasir)->get(route('owner.analytics.export'))->assertForbidden();
        $this->actingAs($this->member('manajer'))->get(route('owner.analytics', ['tab' => 'pendapatan']))->assertOk()->assertSee('Rp360.000');

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

    public function test_system_closed_payments_are_follow_up_candidates_for_this_tenant_only(): void
    {
        $makeUnpaid = function (Service $service, string $date, string $name, string $email, ?Tenant $tenant = null): Booking {
            $booking = $this->booking($service, $date, status: 'cancelled', payment: ['status' => 'gagal'], booking: [
                'namapelanggan' => $name, 'email' => $email, 'nomorhp' => '081234567890',
            ], tenant: $tenant)['bookings'][0];
            BookingLog::record($booking->id, 'cancelled', 'Pembayaran ditutup.');

            return $booking;
        };

        $makeUnpaid($this->photo, '2026-11-02', 'Perlu Dihubungi', 'followup@example.test');
        $makeUnpaid($this->photo, '2026-11-03', 'Sudah Booking Lagi', 'repeat@example.test');
        $ownerCancelled = $makeUnpaid($this->photo, '2026-11-04', 'Dibatalkan Pemilik', 'cancelled@example.test');
        BookingLog::record($ownerCancelled->id, 'cancelled', 'Dibatalkan oleh bisnis.', ['actor' => 'owner']);
        $makeUnpaid($this->photo, '2026-11-05', 'Calon Terbaru', 'newest@example.test');
        $this->booking($this->photo, '2026-12-01', status: 'completed', booking: ['email' => ' REPEAT@example.test ']);

        $otherTenant = Tenant::factory()->create();
        $otherService = Service::factory()->create(['idtenant' => $otherTenant->id]);
        $makeUnpaid($otherService, '2026-11-06', 'Pelanggan Asing', 'foreign@example.test', $otherTenant);
        $this->booking($otherService, '2026-11-07', status: 'paid', booking: ['email' => 'followup@example.test'], tenant: $otherTenant);
        $makeUnpaid($this->photo, '2026-10-30', 'Di Luar Periode', 'lastmonth@example.test');
        $this->booking($this->photo, '2026-11-08', status: 'cancelled', payment: ['status' => 'gagal'], booking: ['namapelanggan' => 'Tanpa Log Sistem']);

        $response = $this->actingAs($this->owner)->get(route('owner.analytics', ['tab' => 'pelanggan']))->assertOk()
            ->assertSee('Pembayaran tidak selesai')->assertSee('https://wa.me/6281234567890');
        $this->assertSame(['Calon Terbaru', 'Perlu Dihubungi'], array_column($response->viewData('report')['customers']['unpaid'], 'name'));

        $this->actingAs($this->member('kasir'))->get(route('owner.analytics', ['tab' => 'pelanggan']))->assertForbidden();
    }

    public function test_report_action_compares_an_equal_custom_period_and_counts_lead_time_once_per_booking(): void
    {
        $this->booking($this->photo, '2026-11-09', 2, 'paid', ['jumlah' => 60000], ['created_at' => '2026-11-05 08:00:00']);
        $this->booking($this->photo, '2026-11-10', booking: ['created_at' => '2026-11-10 08:00:00']);
        $this->booking($this->photo, '2026-11-06', payment: ['jumlah' => 25000]);

        $period = ReportPeriod::fromQuery(['periode' => 'kustom', 'dari' => '2026-11-08', 'sampai' => '2026-11-10']);
        app(TenantContext::class)->setTenantId((int) $this->tenant->id);
        $action = app(GetReport::class);
        $customers = $action->execute($this->tenant, $period, 'pelanggan');
        $summary = $action->execute($this->tenant, $period, 'ringkasan');

        $this->assertSame(2.0, $customers['customers']['advanceDays']);
        $this->assertSame('5 Nov 2026 – 7 Nov 2026', $summary['comparison']['label']);
        $this->assertSame(25000.0, $summary['comparison']['net']);
        $this->assertSame(1, $summary['comparison']['bookings']);
        $this->assertSame('Pendapatan naik dan jumlah booking naik dibanding periode sebelumnya.', $summary['conclusion']);
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

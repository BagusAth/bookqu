<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Report\GetReport;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ReportPeriod;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * BQ-111: grouped analytics queries, per-transaction amounts and formula-safe CSV exports,
 * on the Laporan page and its exports since BQ-147.
 */
class AnalyticsExportAndAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Tenant $tenant;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-15 10:00:00', 'Asia/Jakarta'));

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id]);
        $this->service = Service::factory()->create(['idtenant' => $this->tenant->id, 'namalayanan' => 'Foto Studio', 'harga' => 100000, 'durasi' => 60]);

        $plan = Plan::firstOrCreate(['namapaket' => 'pro'], ['hargabulanan' => 100000, 'maxlayanan' => 10, 'maxbooking' => 500, 'isunlimited' => false]);
        Subscription::create(['idtenant' => $this->tenant->id, 'idplan' => $plan->id, 'status' => 'trial', 'trial_berakhir' => now()->addDays(7)]);
    }

    public function test_monthly_and_daily_series_are_grouped_correctly_with_few_queries(): void
    {
        $this->paidPayment(150000, '2026-11-02 09:00:00');
        $this->paidPayment(50000, '2026-11-14 09:00:00');
        $this->paidPayment(70000, '2026-09-30 23:00:00');
        $this->paidPayment(999999, '2025-10-31 12:00:00');
        Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'booking', 'status' => 'pending', 'jumlah' => 40000, 'created_at' => '2026-11-03 09:00:00']);
        $otherTenant = Tenant::factory()->create();
        Payment::factory()->create(['idtenant' => $otherTenant->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 80000, 'created_at' => '2026-11-03 09:00:00']);

        $this->bookingOn('2026-11-15');
        $this->bookingOn('2026-11-15', '11:00:00');
        $this->bookingOn('2026-11-01');
        $this->bookingOn('2026-10-17');
        $this->bookingOn('2026-08-05');

        // BQ-147: the series are part of Laporan (GetReport) for the chosen period; still grouped queries.
        app(TenantContext::class)->setTenantId((int) $this->tenant->id);
        DB::enableQueryLog();
        $year = app(GetReport::class)->execute($this->tenant, ReportPeriod::fromQuery(['periode' => '12-bulan']), 'pendapatan');
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();
        $month = app(GetReport::class)->execute($this->tenant, ReportPeriod::fromQuery([]), 'pendapatan');

        $this->assertLessThanOrEqual(10, $queries);
        $this->assertCount(12, $year['revenue']['series']);
        $this->assertEquals(200000, $year['revenue']['series'][11]['gross']);
        $this->assertEquals(0, $year['revenue']['series'][10]['gross']);
        $this->assertEquals(70000, $year['revenue']['series'][9]['gross']);
        $this->assertEquals(270000, $year['revenue']['gross']);
        $this->assertSame(5, $year['summary']['bookings']);

        $this->assertCount(30, $month['revenue']['series']);
        $this->assertEquals(150000, $month['revenue']['series'][1]['gross']);
        $this->assertEquals(50000, $month['revenue']['series'][13]['gross']);
        $this->assertSame(3, $month['summary']['bookings']);
    }

    public function test_laporan_page_renders_the_series(): void
    {
        config(['modules.reports' => 'released']);
        $this->paidPayment(150000, '2026-11-02 09:00:00');

        $this->actingAs($this->owner)->get('/owner/analytics?tab=pendapatan')->assertOk()->assertSee('Laporan')->assertSee('Rp 150.000');
    }

    public function test_analytics_export_neutralises_spreadsheet_formulas(): void
    {
        config(['modules.reports' => 'released']);
        $this->bookingOn('2026-11-16', '09:00:00', ['namapelanggan' => '=HYPERLINK("http://evil")', 'email' => 'evil@example.com', 'idpayment' => $this->paidPayment(100000, '2026-11-10 09:00:00')->id, 'status' => 'paid']);
        $this->bookingOn('2026-11-16', '10:00:00', ['namapelanggan' => '-2+3', 'email' => 'ok@example.com', 'idpayment' => $this->paidPayment(100000, '2026-11-10 10:00:00')->id, 'status' => 'paid']);

        // BQ-147: customer names appear in the Pendapatan (transactions) and Pelanggan exports.
        foreach (['pendapatan' => 2, 'pelanggan' => 0] as $tab => $column) {
            $rows = $this->rows($this->actingAs($this->owner)->get('/owner/analytics/export?tab='.$tab)->assertOk()->streamedContent());
            $names = array_column($rows, $column);
            $this->assertContains("'=HYPERLINK(\"http://evil\")", $names);
            $this->assertContains("'-2+3", $names);
            $this->assertNotContains('=HYPERLINK("http://evil")', $names);
        }
    }

    public function test_multi_slot_transaction_amount_is_written_once(): void
    {
        config(['modules.reports' => 'released']);
        $payment = Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 300000]);
        foreach (['09:00:00', '10:00:00', '11:00:00'] as $time) {
            $this->bookingOn('2026-11-16', $time, ['idpayment' => $payment->id, 'status' => 'paid']);
        }
        $single = Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 120000]);
        $this->bookingOn('2026-11-17', '09:00:00', ['idpayment' => $single->id, 'status' => 'paid']);

        // BQ-147: the Pendapatan export lists one row per payment with its number of slots.
        $rows = $this->rows($this->actingAs($this->owner)->get('/owner/analytics/export?tab=pendapatan')->assertOk()->streamedContent());
        $transactions = array_values(array_filter($rows, fn (array $row): bool => str_starts_with($row[0], '2026-11-15 ')));

        $this->assertCount(2, $transactions);
        $this->assertEquals(420000, array_sum(array_map(fn (array $row): float => (float) $row[4], $transactions)));
        $this->assertSame(['300000', '3'], [$transactions[0][4], $transactions[0][6]]);
        $this->assertSame(['120000', '1'], [$transactions[1][4], $transactions[1][6]]);
        $this->assertSame('Foto Studio', $transactions[0][3]);
    }

    public function test_analytics_export_contains_only_the_owners_bookings(): void
    {
        config(['modules.reports' => 'released']);
        $this->bookingOn('2026-11-16', '09:00:00', ['namapelanggan' => 'Pelanggan Saya']);
        $other = Tenant::factory()->create();
        $otherService = Service::factory()->create(['idtenant' => $other->id]);
        $otherSlot = Schedule::factory()->create(['idtenant' => $other->id, 'idlayanan' => $otherService->id]);
        Booking::factory()->create(['idtenant' => $other->id, 'idlayanan' => $otherService->id, 'idschedule' => $otherSlot->id, 'namapelanggan' => 'Pelanggan Lain']);

        $rows = $this->rows($this->actingAs($this->owner)->get('/owner/analytics/export?tab=pelanggan')->streamedContent());
        $list = array_slice($rows, (int) array_search(['Pelanggan', 'Booking', 'Jenis'], $rows, true) + 1);

        $this->assertSame(['Pelanggan Saya'], array_column($list, 0));
    }

    public function test_schedule_report_export_moves_to_the_okupansi_export(): void
    {
        // BQ-147: the schedule report is Laporan → Okupansi; its export has no free-text cells left.
        config(['modules.reports' => 'released']);

        $this->actingAs($this->owner)->get('/owner/schedule-report/export?periode=bulan-lalu')
            ->assertStatus(301)
            ->assertRedirect(route('owner.analytics.export', ['tab' => 'okupansi', 'periode' => 'bulan-lalu']));
    }

    public function test_exports_are_not_reachable_while_the_modules_are_hidden(): void
    {
        config(['modules.reports' => 'hidden']);

        $this->actingAs($this->owner)->get('/owner/analytics/export')->assertNotFound();
        $this->actingAs($this->owner)->get('/owner/schedule-report/export')->assertNotFound();
    }

    private function paidPayment(int $amount, string $createdAt): Payment
    {
        return Payment::factory()->create([
            'idtenant' => $this->tenant->id,
            'tipe' => 'booking',
            'status' => 'sukses',
            'jumlah' => $amount,
            'created_at' => $createdAt,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function bookingOn(string $date, string $time = '09:00:00', array $attributes = []): Booking
    {
        $slot = Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $date,
            'jam_mulai' => $time,
            'jam_selesai' => Carbon::parse($time)->addMinutes(59)->format('H:i:s'),
        ]);

        return Booking::factory()->create(array_merge([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $slot->id,
            'tanggalbooking' => $date,
            'jam' => $time,
            'status' => 'pending',
        ], $attributes));
    }

    /**
     * @return list<list<string>>
     */
    private function rows(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv;
        $lines = array_values(array_filter(preg_split('/\r?\n/', trim($csv)) ?: [], fn (string $line): bool => $line !== ''));

        return array_map(fn (string $line): array => str_getcsv($line, ',', '"', ''), $lines);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Dashboard\GetOwnerAnalyticsTrends;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * BQ-111: grouped analytics queries, per-transaction amounts and formula-safe CSV exports.
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

        app(TenantContext::class)->setTenantId((int) $this->tenant->id);
        DB::enableQueryLog();
        $trends = app(GetOwnerAnalyticsTrends::class)->execute((int) $this->tenant->id);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(3, $queries);
        $this->assertCount(12, $trends['revenueperbulan']);
        $this->assertSame('Dec 2025', $trends['labelbulanpanjang'][0]);
        $this->assertSame('Nov 2026', $trends['labelbulanpanjang'][11]);
        $this->assertEquals(200000, $trends['revenueperbulan'][11]);
        $this->assertEquals(0, $trends['revenueperbulan'][10]);
        $this->assertEquals(70000, $trends['revenueperbulan'][9]);
        $this->assertSame(3, $trends['bookingperbulan'][11]);
        $this->assertSame(1, $trends['bookingperbulan'][10]);
        $this->assertSame(1, $trends['bookingperbulan'][8]);

        $this->assertCount(30, $trends['bookingperhari']);
        $this->assertSame('15 Nov', $trends['labelhari'][29]);
        $this->assertSame(2, $trends['bookingperhari'][29]);
        $this->assertSame(1, $trends['bookingperhari'][15]);
        $this->assertSame(1, $trends['bookingperhari'][0]);
    }

    public function test_analytics_page_still_renders_the_series(): void
    {
        config(['modules.analytics' => 'released']);
        $this->paidPayment(150000, '2026-11-02 09:00:00');

        $this->actingAs($this->owner)->get('/owner/analytics')->assertOk()->assertSee('Analytics');
    }

    public function test_analytics_export_neutralises_spreadsheet_formulas(): void
    {
        config(['modules.analytics' => 'released']);
        $this->bookingOn('2026-11-16', '09:00:00', ['namapelanggan' => '=HYPERLINK("http://evil")', 'email' => '@evil@example.com', 'nomorhp' => '+6281234']);
        $this->bookingOn('2026-11-16', '10:00:00', ['namapelanggan' => '-2+3', 'email' => 'ok@example.com']);

        $csv = $this->actingAs($this->owner)->get('/owner/analytics/export')->assertOk()->streamedContent();
        $rows = $this->rows($csv);

        $names = array_column(array_slice($rows, 1), 2);
        $this->assertContains("'=HYPERLINK(\"http://evil\")", $names);
        $this->assertContains("'-2+3", $names);
        $this->assertContains("'@evil@example.com", array_column(array_slice($rows, 1), 3));
        $this->assertContains("'+6281234", array_column(array_slice($rows, 1), 4));
        $this->assertNotContains('=HYPERLINK("http://evil")', $names);
    }

    public function test_multi_slot_transaction_amount_is_written_once(): void
    {
        config(['modules.analytics' => 'released']);
        $payment = Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 300000]);
        foreach (['09:00:00', '10:00:00', '11:00:00'] as $time) {
            $this->bookingOn('2026-11-16', $time, ['idpayment' => $payment->id, 'status' => 'paid']);
        }
        $single = Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 120000]);
        $this->bookingOn('2026-11-17', '09:00:00', ['idpayment' => $single->id, 'status' => 'paid']);

        $csv = $this->actingAs($this->owner)->get('/owner/analytics/export')->assertOk()->streamedContent();
        $rows = array_slice($this->rows($csv), 1);

        $this->assertSame(['Tanggal', 'Nama Program', 'Nama Pelanggan', 'Email', 'Nomor HP', 'Status', 'Harga', 'Keterangan Harga'], $this->rows($csv)[0]);
        $this->assertEquals(420000, array_sum(array_map(fn (array $row): float => (float) $row[6], $rows)));
        $groupRows = array_values(array_filter($rows, fn (array $row): bool => $row[0] === '2026-11-16'));
        $this->assertCount(3, $groupRows);
        $this->assertSame(['300000.00', '', ''], array_column($groupRows, 6));
        $this->assertSame('Total transaksi 3 sesi', $groupRows[0][7]);
        $this->assertSame('Termasuk dalam total transaksi', $groupRows[1][7]);
    }

    public function test_analytics_export_contains_only_the_owners_bookings(): void
    {
        config(['modules.analytics' => 'released']);
        $this->bookingOn('2026-11-16', '09:00:00', ['namapelanggan' => 'Pelanggan Saya']);
        $other = Tenant::factory()->create();
        $otherService = Service::factory()->create(['idtenant' => $other->id]);
        $otherSlot = Schedule::factory()->create(['idtenant' => $other->id, 'idlayanan' => $otherService->id]);
        Booking::factory()->create(['idtenant' => $other->id, 'idlayanan' => $otherService->id, 'idschedule' => $otherSlot->id, 'namapelanggan' => 'Pelanggan Lain']);

        $rows = array_slice($this->rows($this->actingAs($this->owner)->get('/owner/analytics/export')->streamedContent()), 1);

        $this->assertSame(['Pelanggan Saya'], array_column($rows, 2));
        $this->assertSame('Foto Studio', $rows[0][1]);
    }

    public function test_schedule_report_export_neutralises_spreadsheet_formulas(): void
    {
        config(['modules.schedule-report' => 'released']);
        $this->bookingOn('2026-11-16', '09:00:00', ['namapelanggan' => '=cmd|"/C calc"!A0', 'status' => 'paid']);

        $csv = $this->actingAs($this->owner)->get('/owner/schedule-report/export?period=all')->assertOk()->streamedContent();

        $this->assertStringContainsString("'=cmd|", $csv);
        $this->assertStringNotContainsString(',=cmd|', $csv);
        $this->assertStringNotContainsString(',"=cmd|', $csv);
    }

    public function test_exports_are_not_reachable_while_the_modules_are_hidden(): void
    {
        config(['modules.analytics' => 'hidden', 'modules.schedule-report' => 'hidden']);

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

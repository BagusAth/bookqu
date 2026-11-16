<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Booking;

use App\Actions\Booking\CreateBooking;
use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * BQ-151/152 on a real InnoDB engine (ADR-0017): claims for the last seat and for a shared
 * unit wait for the tenant lock, and the seat index rejects a second writer that skips it.
 */
#[Group('mysql')]
class BookingSeatLockingTest extends TestCase
{
    use DatabaseTruncation;

    private const string SECOND_CONNECTION = 'mysql_concurrent';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        $this->travelTo(now('Asia/Jakarta')->setTime(8, 0));

        config(['database.connections.'.self::SECOND_CONNECTION => config('database.connections.mysql')]);
        $this->beforeApplicationDestroyed(function (): void {
            DB::connection(self::SECOND_CONNECTION)->rollBack();
            DB::purge(self::SECOND_CONNECTION);
            $this->truncateTablesForAllConnections();
        });
    }

    /** @return array{Tenant, Service, Schedule} */
    private function slot(int $capacity): array
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenantId($tenant->id);
        app(CreateTrialSubscription::class)->execute($tenant);
        $service = Service::factory()->for($tenant, 'tenant')->create(['harga' => 0, 'kapasitas' => $capacity]);
        $schedule = Schedule::factory()->create([
            'idtenant' => $tenant->id,
            'idlayanan' => $service->id,
            'tanggal' => now('Asia/Jakarta')->addDay()->toDateString(),
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '10:59:00',
        ]);

        return [$tenant, $service, $schedule];
    }

    private function claim(Tenant $tenant, Service $service, Schedule $schedule): void
    {
        app(CreateBooking::class)->execute(
            $tenant,
            $service,
            $schedule->tanggal->toDateString(),
            ['10:00'],
            [$schedule->id],
            ['namapelanggan' => 'Rani', 'nomorhp' => '081234567890', 'email' => 'rani@example.com', 'catatan' => null],
        );
    }

    private function holdTenantLock(Tenant $tenant): void
    {
        $other = DB::connection(self::SECOND_CONNECTION);
        $other->beginTransaction();
        $other->table('tenants')->where('id', $tenant->id)->lockForUpdate()->first();
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
    }

    public function test_claim_for_the_last_seat_waits_for_the_tenant_lock(): void
    {
        [$tenant, $service, $schedule] = $this->slot(capacity: 2);
        $this->claim($tenant, $service, $schedule);
        $this->holdTenantLock($tenant);

        try {
            $this->claim($tenant, $service, $schedule);
            $this->fail('The claim for the last seat did not wait for the tenant lock.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Lock wait timeout exceeded', $exception->getMessage());
        }
    }

    public function test_seat_index_rejects_a_writer_that_skips_the_claim(): void
    {
        [$tenant, $service, $schedule] = $this->slot(capacity: 1);
        $this->claim($tenant, $service, $schedule);
        $this->assertSame(1, Booking::where('idschedule', $schedule->id)->count());

        $this->expectException(UniqueConstraintViolationException::class);
        Booking::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id, 'idschedule' => $schedule->id, 'status' => 'paid']);
    }

    public function test_claim_on_a_shared_unit_waits_for_the_tenant_lock(): void
    {
        [$tenant, $service, $schedule] = $this->slot(capacity: 1);
        $unit = Unit::factory()->create(['idtenant' => $tenant->id]);
        $unit->services()->sync([$service->id]);
        $this->holdTenantLock($tenant);

        try {
            $this->claim($tenant, $service, $schedule);
            $this->fail('The unit claim did not wait for the tenant lock.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Lock wait timeout exceeded', $exception->getMessage());
        }
    }
}

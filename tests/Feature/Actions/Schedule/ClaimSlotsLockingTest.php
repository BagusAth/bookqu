<?php

namespace Tests\Feature\Actions\Schedule;

use App\Actions\Schedule\ClaimSlots;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Proves the tenant-first protocol on a real InnoDB engine (ADR-0005 decision 4): a
 * claim waits for any transaction that holds the tenant row. The expiry sweep holds
 * that row while the Payment lifecycle reclaims a payment, so sweep and claim on the
 * same tenant are serialized (BQ-071 → BQ-012).
 */
#[Group('mysql')]
class ClaimSlotsLockingTest extends TestCase
{
    use DatabaseTruncation;

    private const string SECOND_CONNECTION = 'mysql_concurrent';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.'.self::SECOND_CONNECTION => config('database.connections.mysql')]);

        $this->beforeApplicationDestroyed(function (): void {
            DB::connection(self::SECOND_CONNECTION)->rollBack();
            DB::purge(self::SECOND_CONNECTION);
            $this->truncateTablesForAllConnections();
        });
    }

    public function test_claim_waits_for_the_tenant_lock_before_touching_schedules(): void
    {
        $tenant = Tenant::factory()->create();
        $service = Service::factory()->create(['idtenant' => $tenant->id]);
        $schedule = Schedule::factory()->create([
            'idtenant' => $tenant->id,
            'idlayanan' => $service->id,
            'tanggal' => now()->addDays(3)->toDateString(),
        ]);

        $otherTransaction = DB::connection(self::SECOND_CONNECTION);
        $otherTransaction->beginTransaction();
        $otherTransaction->table('tenants')->where('id', $tenant->id)->lockForUpdate()->first();

        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

        try {
            DB::transaction(fn () => app(ClaimSlots::class)->execute($tenant, (int) $service->id, [$schedule->id]));
            $this->fail('ClaimSlots claimed a slot while another transaction held the tenant row.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Lock wait timeout exceeded', $exception->getMessage());
            $this->assertMatchesRegularExpression('/^select .* from `tenants` .* for update$/', $exception->getSql());
        }
    }
}

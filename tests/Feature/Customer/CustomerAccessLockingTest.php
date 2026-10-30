<?php

namespace Tests\Feature\Customer;

use App\Actions\CustomerAccess\IssueCustomerAccess;
use App\Actions\CustomerAccess\ResolveCustomerAccess;
use App\Actions\CustomerAccess\RevokeCustomerAccess;
use App\Domain\CustomerAccess\AccessProfile;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

#[Group('mysql')]
class CustomerAccessLockingTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.customer_access_concurrent' => config('database.connections.mysql')]);
        $this->beforeApplicationDestroyed(function (): void {
            DB::connection('customer_access_concurrent')->rollBack();
            DB::purge('customer_access_concurrent');
            $this->truncateTablesForAllConnections();
        });
    }

    public function test_family_revocation_waits_for_tenant_mutex_and_invalidates_preexisting_grants(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $payment = Payment::factory()->create(['idtenant' => $tenant->id]);
        Booking::factory()->create(['idtenant' => $tenant->id, 'idpayment' => $payment->id]);
        $grant = app(IssueCustomerAccess::class)->execute($payment, AccessProfile::Management)->grant;
        $other = DB::connection('customer_access_concurrent');
        $other->beginTransaction();
        $other->table('tenants')->where('id', $tenant->id)->lockForUpdate()->first();
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        try {
            app(RevokeCustomerAccess::class)->execute($grant, true);
            $this->fail('Revocation must acquire the tenant mutex.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Lock wait timeout exceeded', $exception->getMessage());
            $this->assertMatchesRegularExpression('/^select .* from `tenants` .* for update$/', $exception->getSql());
            $this->assertSame(1, (int) $payment->fresh()->customer_access_version);
        } finally {
            $other->rollBack();
            DB::purge('customer_access_concurrent');
        }
        app(RevokeCustomerAccess::class)->execute($grant, true);
        $this->assertSame(2, (int) $payment->fresh()->customer_access_version);
        $this->expectException(HttpException::class);
        app(ResolveCustomerAccess::class)->execute($grant->id);
    }
}

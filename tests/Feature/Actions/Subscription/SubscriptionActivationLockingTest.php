<?php

namespace Tests\Feature\Actions\Subscription;

use App\Actions\Subscription\ActivateSubscription;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('mysql')]
class SubscriptionActivationLockingTest extends TestCase
{
    use DatabaseTruncation;

    public function test_activation_waits_for_the_tenant_lock_before_renewing(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        config(['database.connections.subscription_concurrent' => config('database.connections.mysql')]);
        $tenant = Tenant::factory()->create();
        $plan = Plan::create(['namapaket' => 'pro', 'hargabulanan' => 499000, 'maxlayanan' => 0, 'maxbooking' => 0, 'isunlimited' => true]);
        $payment = Payment::factory()->create(['idtenant' => $tenant->id, 'idplan' => $plan->id, 'tipe' => 'subscription', 'status' => 'sukses']);
        $other = DB::connection('subscription_concurrent');
        $other->beginTransaction();
        $other->table('tenants')->where('id', $tenant->id)->lockForUpdate()->first();
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
        try {
            app(ActivateSubscription::class)->execute($payment);
            $this->fail('Activation bypassed the tenant lock.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Lock wait timeout exceeded', $exception->getMessage());
            $this->assertMatchesRegularExpression('/^select .* from `tenants` .* for update$/', $exception->getSql());
            $this->assertDatabaseCount('subscriptions', 0);
        } finally {
            $other->rollBack();
            DB::purge('subscription_concurrent');
            $payment->delete();
            $tenant->delete();
            $plan->delete();
            DB::table('users')->where('id', $tenant->iduser)->delete();
        }
    }
}

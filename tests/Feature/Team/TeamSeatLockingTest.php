<?php

namespace Tests\Feature\Team;

use App\Actions\Team\ManageTeam;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('mysql')]
class TeamSeatLockingTest extends TestCase
{
    use DatabaseTruncation;

    public function test_both_inviting_and_accepting_wait_for_the_tenant_lock(): void
    {
        $this->travelTo(now()->startOfDay());
        config(['database.connections.team_concurrent' => config('database.connections.mysql')]);
        $tenant = Tenant::factory()->create();
        $owner = $tenant->user;
        $invitationId = DB::table('tenant_invitations')->insertGetId([
            'idtenant' => $tenant->id,
            'invited_by' => $owner->id,
            'email' => 'team@example.com',
            'role' => 'kasir',
            'selector' => str_repeat('a', 32),
            'secret_hash' => str_repeat('a', 64),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
        ]);
        $other = DB::connection('team_concurrent');
        $other->beginTransaction();
        $other->table('tenants')->where('id', $tenant->id)->lockForUpdate()->first();
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

        try {
            $team = app(ManageTeam::class);
            foreach ([
                fn () => $team->invite($tenant, $owner, 'another@example.com', 'kasir'),
                fn () => $team->accept($invitationId, null, []),
            ] as $operation) {
                try {
                    $operation();
                    $this->fail('The team operation bypassed the tenant lock.');
                } catch (QueryException $exception) {
                    $this->assertStringContainsString('Lock wait timeout exceeded', $exception->getMessage());
                    $this->assertMatchesRegularExpression('/^select .* from `tenants` .* for update$/', $exception->getSql());
                }
            }
            $this->assertDatabaseCount('tenant_invitations', 1);
            $this->assertDatabaseCount('tenant_members', 1);
        } finally {
            $other->rollBack();
            DB::purge('team_concurrent');
            $tenant->delete();
            $owner->delete();
        }
    }
}

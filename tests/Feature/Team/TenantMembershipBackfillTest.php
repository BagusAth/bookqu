<?php

namespace Tests\Feature\Team;

use App\Models\Tenant;
use App\Models\TenantMember;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('mysql')]
class TenantMembershipBackfillTest extends TestCase
{
    use DatabaseTruncation;

    public function test_migration_backfills_the_existing_owning_account_as_accepted_pemilik(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        Schema::drop('tenant_members');
        $migration = require database_path('migrations/2026_11_04_030007_create_tenant_members_table.php');
        $migration->up();
        $this->assertDatabaseHas('tenant_members', ['idtenant' => $tenant->id, 'iduser' => $tenant->iduser, 'role' => 'pemilik', 'revoked_at' => null]);
        // scope-bypass: migration verification outside a request, isolated test database.
        $this->assertNotNull(TenantMember::withoutGlobalScopes()->sole()->accepted_at);
    }
}

<?php

namespace Tests\Feature\Database;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_seeded_owner_has_an_active_pemilik_membership_and_can_open_the_owner_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenants = Tenant::query()->get();
        $this->assertNotEmpty($tenants);

        foreach ($tenants as $tenant) {
            $this->assertTrue(DB::table('tenant_members')->where('idtenant', $tenant->id)->where('iduser', $tenant->iduser)
                ->where('role', 'pemilik')->whereNotNull('accepted_at')->whereNull('revoked_at')->exists());
        }

        $owner = User::query()->findOrFail($tenants->first()->iduser);
        $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($owner);
    }
}

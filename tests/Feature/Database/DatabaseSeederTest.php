<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The local demo data must give every seeded owner a usable portal (BQ-162 QA finding T-05):
 * the seeder runs WithoutModelEvents, so Tenant::created cannot create the owner membership.
 * One test, because seeding the full demo data takes several seconds.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_owners_and_demo_team_reach_the_pages_of_their_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenants = Tenant::withoutGlobalScopes()->get();
        $this->assertCount(5, $tenants);
        foreach ($tenants as $tenant) {
            $this->assertSame('pemilik', TenantMember::withoutGlobalScopes()
                ->where('idtenant', $tenant->id)->where('iduser', $tenant->iduser)->value('role'), $tenant->slug);
        }

        $owner = User::where('email', 'brama@bookqu.test')->firstOrFail();
        $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk();
        $this->actingAs($owner)->get(route('owner.payments'))->assertOk();

        $manajer = User::where('email', 'manajer@bookqu.test')->firstOrFail();
        $this->actingAs($manajer)->get(route('owner.analytics'))->assertOk();
        $this->actingAs($manajer)->get(route('owner.payments'))->assertForbidden();

        $kasir = User::where('email', 'kasir@bookqu.test')->firstOrFail();
        $this->actingAs($kasir)->get(route('owner.bookings'))->assertOk();
        $this->actingAs($kasir)->get(route('owner.analytics'))->assertForbidden();
    }
}

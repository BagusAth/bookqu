<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Actions\Booking\GetPromoFigures;
use App\Models\Asset;
use App\Models\Booking;
use App\Models\OwnerBlockedDate;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use App\Models\Voucher;
use App\Support\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('public');
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

    /**
     * BQ-162 QA T-21, T-22, T-40: no closed date holds active bookings, Promo's usage figures match
     * the vouchers' used_count, and every seeded asset has its file.
     */
    public function test_seeded_demo_data_is_consistent(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        foreach (Tenant::withoutGlobalScopes()->whereIn('slug', ['brama-digital', 'brama-studio', 'brama-agency'])->get() as $tenant) {
            $closedWithBookings = OwnerBlockedDate::withoutGlobalScopes()->where('idtenant', $tenant->id)->get()
                ->filter(fn (OwnerBlockedDate $closed): bool => Booking::withoutGlobalScopes()->where('idtenant', $tenant->id)
                    ->whereDate('tanggalbooking', $closed->tanggal)->whereIn('status', ['pending', 'paid'])->exists());
            $this->assertCount(0, $closedWithBookings, "{$tenant->slug} closes a date that has active bookings");

            app(TenantContext::class)->setTenantId((int) $tenant->id);
            $figures = app(GetPromoFigures::class)->execute($tenant);
            $this->assertSame((int) Voucher::withoutGlobalScopes()->where('idtenant', $tenant->id)->sum('used_count'), $figures['uses'], $tenant->slug);
            $this->assertGreaterThan(0, $figures['uses'], $tenant->slug);

            foreach (Asset::withoutGlobalScopes()->where('idtenant', $tenant->id)->get(['file_path', 'mime_type']) as $asset) {
                Storage::disk('public')->assertExists($asset->file_path);
                $this->assertStringStartsWith('image/', (string) $asset->mime_type, 'the gallery holds images only');
            }
        }
    }
}

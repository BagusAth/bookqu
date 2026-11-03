<?php

namespace Tests\Feature\Subscription;

use App\Actions\Owner\GetOwnerNavigation;
use App\Actions\Subscription\CreateTrialSubscription;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Team\TenantPermission;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReleasePlanGatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_features_fail_closed_even_for_a_live_trial(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $subscription = new Subscription(['status' => 'trial', 'trial_berakhir' => now()->addDay()]);
        $subscription->setRelation('plan', new Plan(['namapaket' => 'pro']));
        $this->assertFalse(EntitlementRules::canAccessFeature($subscription, 'unregistered-capability')['allowed']);
    }

    public function test_ac_pkt_01_comparison_omits_withdrawn_and_unbuilt_promises_and_uses_checkout_prices(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        Plan::create(['namapaket' => 'small', 'hargabulanan' => 169000, 'maxlayanan' => 5, 'maxbooking' => 300]);
        Plan::create(['namapaket' => 'medium', 'hargabulanan' => 299000, 'maxlayanan' => 0, 'maxbooking' => 500]);
        Plan::create(['namapaket' => 'pro', 'hargabulanan' => 499000, 'maxlayanan' => 0, 'maxbooking' => 0, 'isunlimited' => true]);
        $landing = $this->get('/')->assertOk()->assertSee('169.000')->assertDontSee('Rp149.000');
        $owner = $this->actingAs($tenant->user)->get('/owner/subscription')->assertOk();
        foreach ([$landing, $owner] as $response) {
            $response->assertDontSee('BYOPG')->assertDontSee('Multi-location')->assertDontSee('WhatsApp &amp; email reminders', false);
        }
    }

    public function test_redesign_capabilities_start_hidden(): void
    {
        foreach (['units', 'promos', 'add-ons', 'team', 'booking-page', 'landing-page', 'integrations', 'api', 'reports', 'refunds', 'help'] as $module) {
            $this->assertSame('hidden', config('modules.'.$module), $module);
        }
    }

    public function test_each_redesign_route_group_observes_module_then_feature_then_role(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $subscription = app(CreateTrialSubscription::class)->execute($tenant);
        $pro = $subscription->plan;
        $small = Plan::create(['namapaket' => 'small', 'hargabulanan' => 149000, 'maxlayanan' => 5, 'maxbooking' => 300]);
        $cashier = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $cashier->id, 'role' => 'kasir', 'accepted_at' => now()]);
        foreach (['units' => 'units', 'promos' => 'promos', 'team' => 'team', 'booking-page' => 'booking-page', 'landing-page' => 'landing-page', 'integrations' => 'integrations', 'api' => 'api', 'reports' => 'analytics', 'refunds' => 'refunds', 'help' => 'help', 'add-ons' => 'add-ons'] as $module => $feature) {
            $prefix = $module === 'add-ons' ? 'additional-items' : $module;
            $name = 'owner.'.$prefix.'.probe';
            $path = '/owner/gate-probe/'.$module;
            Route::middleware(['web', 'auth', 'tenant', 'module:'.$module, 'subscription:'.$feature, 'tenant.permission'])->get($path, fn () => response('ok'))->name($name);
            config(['modules.'.$module => 'hidden']);
            $this->actingAs($tenant->user)->get($path)->assertNotFound();
            config(['modules.'.$module => 'released']);
            $subscription->update(['idplan' => $pro->id, 'status' => 'active', 'langganan_berakhir' => now()->addMonth()]);
            $this->get($path)->assertOk();
            if (! (config('plans.small.capabilities', [])[$feature] ?? false)) {
                $subscription->update(['idplan' => $small->id]);
                $this->get($path)->assertRedirect(route('owner.subscription'));
                $subscription->update(['idplan' => $pro->id]);
            }
            $ability = TenantPermission::routeAbility($name, 'GET');
            $response = $this->actingAs($cashier)->get($path);
            if (TenantPermission::allowsRole('kasir', $ability)) {
                $response->assertOk();
            } else {
                $response->assertForbidden();
            }
        }
    }

    public function test_navigation_uses_the_same_capabilities_and_hides_unauthorized_menus(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        app(CreateTrialSubscription::class)->execute($tenant);
        $cashier = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $cashier->id, 'role' => 'kasir', 'accepted_at' => now()]);
        $items = app(GetOwnerNavigation::class)->execute($cashier);
        $this->assertSame(['dashboard', 'calendar', 'bookings', 'customers', 'settings'], array_column($items, 'id'));
        $this->actingAs($cashier)->get('/owner/dashboard')->assertOk()->assertDontSee('id="nav-layanan"', false)->assertDontSee('id="nav-pembayaran"', false);
    }
}

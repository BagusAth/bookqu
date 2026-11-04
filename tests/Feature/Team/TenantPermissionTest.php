<?php

namespace Tests\Feature\Team;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Domain\Team\TenantPermission;
use App\Models\Booking;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use App\Notifications\NewBookingOwnerNotification;
use App\Notifications\TrialExpiringNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_matrix_fails_closed_and_separates_money_configuration_and_counter_work(): void
    {
        foreach (['bookings.view', 'bookings.walkin', 'bookings.complete', 'bookings.reschedule', 'customers.view', 'account.manage', 'notifications.view'] as $ability) {
            foreach (['pemilik', 'manajer', 'kasir'] as $role) {
                $this->assertTrue(TenantPermission::allowsRole($role, $ability), $role.':'.$ability);
            }
        }
        foreach (['bookings.cancel', 'refunds.decide', 'services.manage', 'schedule.manage', 'reviews.manage', 'reports.view', 'settings.manage'] as $ability) {
            $this->assertTrue(TenantPermission::allowsRole('pemilik', $ability));
            $this->assertTrue(TenantPermission::allowsRole('manajer', $ability));
            $this->assertFalse(TenantPermission::allowsRole('kasir', $ability));
        }
        foreach (['payouts.manage', 'subscription.manage', 'team.manage', 'business.delete'] as $ability) {
            $this->assertTrue(TenantPermission::allowsRole('pemilik', $ability));
            $this->assertFalse(TenantPermission::allowsRole('manajer', $ability));
            $this->assertFalse(TenantPermission::allowsRole('kasir', $ability));
        }
        $this->assertFalse(TenantPermission::allowsRole('kasir', 'revenue.view'));
        $this->assertFalse(TenantPermission::allowsRole('unknown', 'bookings.view'));
        $this->assertFalse(TenantPermission::allowsRole('pemilik', 'unknown'));
    }

    public function test_membership_is_reread_and_revocation_blocks_the_next_request(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $member = TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $user->id, 'role' => 'kasir', 'accepted_at' => now()]);
        $this->actingAs($user)->get('/owner/bookings')->assertOk();
        $this->get('/owner/settings/balance')->assertForbidden();
        $member->update(['revoked_at' => now()]);
        $this->get('/owner/bookings')->assertForbidden();
    }

    public function test_cashier_cannot_cancel_by_forging_the_general_status_endpoint(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $user->id, 'role' => 'kasir', 'accepted_at' => now()]);
        $booking = Booking::factory()->create(['idtenant' => $tenant->id]);
        $this->actingAs($user)->patch('/owner/bookings/'.$booking->id.'/status', ['status' => 'cancelled'])->assertForbidden();
        $this->assertNotSame('cancelled', $booking->fresh()->status);
    }

    public function test_cashier_dashboard_and_polling_omit_revenue_figures(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $user->id, 'role' => 'kasir', 'accepted_at' => now()]);
        $this->actingAs($user)->get('/owner/dashboard')->assertOk()->assertViewMissing('totalrevenue')->assertViewMissing('datarevenueperbulan');
        $this->getJson('/owner/dashboard/polling')->assertOk()->assertJsonMissingPath('data.total_revenue');
    }

    public function test_resource_policies_hide_foreign_tenant_records(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenant = Tenant::factory()->create(['iduser' => $owner->id]);
        $foreign = Tenant::factory()->create();
        $this->actingAs($owner);
        foreach (['Booking', 'Service', 'Category', 'Schedule', 'OwnerBlockedDate', 'CustomerNote', 'Review', 'OwnerPayout', 'TenantPayoutAccount', 'Tenant', 'Staff', 'Resource', 'Asset', 'Voucher', 'AdditionalItem', 'Payment', 'Subscription', 'TenantMember'] as $name) {
            $class = 'App\\Models\\'.$name;
            $record = (new $class)->forceFill(['idtenant' => $foreign->id, 'id' => $foreign->id]);
            $result = Gate::inspect('operate', [$record, 'settings.manage']);
            $this->assertFalse($result->allowed(), $name);
            $this->assertSame(404, $result->status(), $name);
        }
    }

    public function test_cashier_notification_feed_excludes_financial_notifications(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $user->id, 'role' => 'kasir', 'accepted_at' => now()]);
        $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => NewBookingOwnerNotification::class, 'data' => ['title' => 'Booking boleh dibaca', 'event_type' => 'new_booking']]);
        $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => TrialExpiringNotification::class, 'data' => ['title' => 'Paket rahasia']]);
        $this->actingAs($user)->getJson('/owner/notifications')->assertOk()->assertSee('Booking boleh dibaca')->assertDontSee('Paket rahasia')->assertJsonPath('unread_count', 1);
    }

    public function test_every_owner_route_has_a_known_role_ability_and_the_permission_gate(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! str_starts_with($name, 'owner.')) {
                continue;
            }
            $this->assertContains('tenant.permission', $route->gatherMiddleware(), $name);
            $ability = TenantPermission::routeAbility($name, $route->methods()[0], $name === 'owner.bookings.status' ? 'completed' : null);
            $this->assertNotNull($ability, $name);
            $this->assertTrue(TenantPermission::allowsRole($ability === 'bookings.request-cancel' ? 'kasir' : 'pemilik', $ability), $name);
        }
    }

    public function test_cashier_reads_own_account_without_owner_identity_or_business_controls(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $ownerEmail = $tenant->user->email;
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now(), 'email' => 'cashier@example.test']);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $user->id, 'role' => 'kasir', 'accepted_at' => now()]);
        $this->actingAs($user)->get('/owner/settings')->assertOk()->assertSee('cashier@example.test')->assertDontSee($ownerEmail)->assertDontSee('id="business-profile"', false)->assertDontSee('id="danger-zone"', false);
    }

    public function test_delivery_refuses_owner_only_notifications_and_omits_cashier_amounts(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $user->id, 'role' => 'kasir', 'accepted_at' => now()]);
        $subscription = app(CreateTrialSubscription::class)->execute($tenant);
        Notification::sendNow($user, new TrialExpiringNotification($subscription), ['database']);
        $this->assertDatabaseCount('notifications', 0);
        $booking = Booking::factory()->create(['idtenant' => $tenant->id]);
        Notification::sendNow($user, new NewBookingOwnerNotification($booking), ['database']);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertArrayNotHasKey('amount', $user->notifications()->sole()->data);
    }
}

<?php

namespace Tests\Feature\Team;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Jobs\SendTeamInvitation;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_outstanding_invitations_reserve_seats_and_only_the_owner_can_invite(): void
    {
        $this->travelTo(now()->startOfDay());
        config(['modules.team' => 'released', 'plans.pro.team_limit' => 1]);
        Queue::fake();
        $tenant = Tenant::factory()->create();
        app(CreateTrialSubscription::class)->execute($tenant);
        $this->actingAs($tenant->user);
        $this->post('/owner/settings/team/invitations', ['email' => 'first@example.com', 'role' => 'kasir'])->assertRedirect();
        $this->postJson('/owner/settings/team/invitations', ['email' => 'second@example.com', 'role' => 'manajer'])->assertUnprocessable();
        $this->assertDatabaseCount('tenant_invitations', 1);
        $this->postJson('/owner/settings/team/invitations', ['email' => 'owner@example.com', 'role' => 'pemilik'])->assertUnprocessable();
    }

    public function test_invited_account_accepts_once_and_loses_access_on_revocation(): void
    {
        $this->travelTo(now()->startOfDay());
        config(['modules.team' => 'released']);
        Queue::fake();
        $tenant = Tenant::factory()->create();
        app(CreateTrialSubscription::class)->execute($tenant);
        $email = 'cashier@example.com';
        $this->actingAs($tenant->user)->get('/owner/settings/team')->assertOk();
        $this->post('/owner/settings/team/invitations', ['email' => $email, 'role' => 'kasir'])->assertRedirect();
        $credential = $this->credential();
        $this->actingAs(User::factory()->create())->post('/auth/undangan-tim/buka', ['credential' => $credential])->assertStatus(303);
        $this->post('/auth/undangan-tim/terima')->assertForbidden();
        auth()->logout();
        $this->post('/auth/undangan-tim/terima', [
            'name' => 'Kasir Baru', 'nomorhp' => '081234567890',
            'password' => 'Bookqu-test-password-2026', 'password_confirmation' => 'Bookqu-test-password-2026',
        ])->assertRedirect(route('owner.dashboard'));
        $user = User::where('email', $email)->firstOrFail();
        $member = DB::table('tenant_members')->where('iduser', $user->id)->first();
        $this->assertSame($tenant->id, (int) $member->idtenant);
        $this->assertSame('kasir', $member->role);
        $this->assertDatabaseCount('tenants', 1);
        $this->post('/auth/undangan-tim/buka', ['credential' => $credential])->assertNotFound();
        $this->get('/owner/bookings')->assertOk();
        $this->post('/owner/settings/team/invitations', ['email' => 'other@example.com', 'role' => 'kasir'])->assertForbidden();

        $this->actingAs($tenant->user)->patch('/owner/settings/team/members/'.$member->id, ['role' => 'manajer'])->assertRedirect();
        $this->assertDatabaseHas('tenant_members', ['id' => $member->id, 'role' => 'manajer']);
        $this->delete('/owner/settings/team/members/'.$member->id)->assertRedirect();
        $this->actingAs($user)->get('/owner/bookings')->assertForbidden();
        $this->assertGuest();
    }

    public function test_acceptance_rechecks_seats_and_expired_credentials_are_refused(): void
    {
        $this->travelTo(now()->startOfDay());
        config(['modules.team' => 'released']);
        Queue::fake();
        $tenant = Tenant::factory()->create();
        app(CreateTrialSubscription::class)->execute($tenant);
        $invitee = User::factory()->create(['role' => 'customer']);
        $this->actingAs($tenant->user)->post('/owner/settings/team/invitations', ['email' => $invitee->email, 'role' => 'manajer'])->assertRedirect();
        $credential = $this->credential();
        $this->actingAs($invitee)->post('/auth/undangan-tim/buka', ['credential' => $credential])->assertStatus(303);
        config(['plans.pro.team_limit' => 0]);
        $this->postJson('/auth/undangan-tim/terima')->assertUnprocessable();
        $this->assertDatabaseMissing('tenant_members', ['iduser' => $invitee->id]);
        $this->travelTo(now()->addDay());
        $this->post('/auth/undangan-tim/buka', ['credential' => $credential])->assertNotFound();
    }

    public function test_cashier_requests_cancellation_and_manager_approval_refunds_online_payment_once(): void
    {
        $this->travelTo(now()->startOfDay());
        $tenant = Tenant::factory()->create();
        $cashier = User::factory()->create(['role' => 'owner']);
        $manager = User::factory()->create(['role' => 'owner']);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $cashier->id, 'role' => 'kasir', 'accepted_at' => now()]);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $manager->id, 'role' => 'manajer', 'accepted_at' => now()]);
        $payment = Payment::factory()->create(['idtenant' => $tenant->id, 'status' => 'sukses', 'provider' => 'singapay', 'jumlah' => 55000]);
        $booking = Booking::factory()->create(['idtenant' => $tenant->id, 'idpayment' => $payment->id, 'status' => 'paid']);
        $other = Booking::factory()->create(['idtenant' => $tenant->id, 'idpayment' => $payment->id, 'status' => 'paid']);
        $requestUrl = '/owner/bookings/'.$booking->id.'/cancellation-requests';
        $this->actingAs($cashier)->post($requestUrl, ['reason' => 'Lokasi ditutup', 'iduser' => $manager->id])->assertRedirect();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'paid']);
        $this->assertDatabaseCount('refunds', 0);
        $id = DB::table('booking_cancellation_requests')->value('id');
        $decisionUrl = '/owner/bookings/cancellation-requests/'.$id;
        $this->patch($decisionUrl, ['decision' => 'approve'])->assertForbidden();
        $this->actingAs(Tenant::factory()->create()->user)->patch($decisionUrl, ['decision' => 'approve'])->assertNotFound();
        $this->actingAs($manager)->get('/owner/bookings')->assertOk()->assertSee('Lokasi ditutup');
        $this->patch($decisionUrl, ['decision' => 'approve'])->assertRedirect();
        $this->patch($decisionUrl, ['decision' => 'approve'])->assertConflict();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('bookings', ['id' => $other->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('refunds', ['payment_id' => $payment->id, 'cause' => 'business_cancel', 'jumlah' => 55000]);
        $this->assertDatabaseCount('refunds', 1);
        $logs = DB::table('booking_logs')->where('booking_id', $booking->id)->pluck('metadata', 'event');
        $this->assertSame($cashier->id, json_decode($logs['cancellation_requested'], true)['actor_iduser']);
        $this->assertSame('manajer', json_decode($logs['cancellation_approved'], true)['actor_role']);
    }

    public function test_rejected_request_preserves_booking_and_activity_is_owner_only(): void
    {
        $this->travelTo(now()->startOfDay());
        config(['modules.team' => 'released']);
        $tenant = Tenant::factory()->create();
        $cashier = User::factory()->create(['role' => 'owner']);
        $manager = User::factory()->create(['role' => 'owner']);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $cashier->id, 'role' => 'kasir', 'accepted_at' => now()]);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $manager->id, 'role' => 'manajer', 'accepted_at' => now()]);
        $booking = Booking::factory()->create(['idtenant' => $tenant->id, 'status' => 'paid']);
        $this->actingAs($cashier)->post('/owner/bookings/'.$booking->id.'/cancellation-requests', ['reason' => 'Salah jadwal'])->assertRedirect();
        $id = DB::table('booking_cancellation_requests')->value('id');
        $this->actingAs($manager)->patch('/owner/bookings/cancellation-requests/'.$id, ['decision' => 'reject'])->assertRedirect();
        $this->assertDatabaseHas('booking_cancellation_requests', ['id' => $id, 'status' => 'rejected', 'decided_by' => $manager->id]);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'paid']);
        $this->assertDatabaseCount('refunds', 0);

        app(CreateTrialSubscription::class)->execute($tenant);
        $this->post('/owner/categories', ['name' => 'Lapangan', 'iduser' => $cashier->id])->assertRedirect();
        $this->assertDatabaseHas('tenant_activity_logs', [
            'idtenant' => $tenant->id, 'iduser' => $manager->id, 'role' => 'manajer', 'event' => 'categories.created',
        ]);
        $this->get('/owner/settings/team')->assertForbidden();
        $this->actingAs($tenant->user)->get('/owner/settings/team')->assertOk()->assertSee($manager->namalengkap);
    }

    public function test_existing_account_accepts_with_its_invited_email_without_creating_another_user(): void
    {
        $this->travelTo(now()->startOfDay());
        config(['modules.team' => 'released']);
        Queue::fake();
        $tenant = Tenant::factory()->create();
        app(CreateTrialSubscription::class)->execute($tenant);
        $invitee = User::factory()->create(['role' => 'customer']);
        $this->actingAs($tenant->user)->post('/owner/settings/team/invitations', [
            'email' => strtoupper($invitee->email), 'role' => 'manajer',
        ])->assertRedirect();
        $this->actingAs($invitee)->post('/auth/undangan-tim/buka', ['credential' => $this->credential()])->assertStatus(303);
        $this->get('/auth/undangan-tim')->assertOk()->assertSee($invitee->email);
        $this->post('/auth/undangan-tim/terima')->assertRedirect(route('owner.dashboard'));
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('tenant_members', ['iduser' => $invitee->id, 'idtenant' => $tenant->id, 'role' => 'manajer']);
    }

    public function test_approved_cancellation_closes_pending_payment_and_rejects_duplicate_requests(): void
    {
        $this->travelTo(now()->startOfDay());
        $tenant = Tenant::factory()->create();
        $cashier = User::factory()->create(['role' => 'owner']);
        TenantMember::create(['idtenant' => $tenant->id, 'iduser' => $cashier->id, 'role' => 'kasir', 'accepted_at' => now()]);
        $payment = Payment::factory()->create(['idtenant' => $tenant->id, 'status' => 'pending', 'provider' => 'singapay', 'tipe' => 'booking']);
        $booking = Booking::factory()->create(['idtenant' => $tenant->id, 'idpayment' => $payment->id, 'status' => 'pending']);
        $url = '/owner/bookings/'.$booking->id.'/cancellation-requests';
        $this->actingAs($cashier)->post($url, ['reason' => 'Lokasi ditutup'])->assertRedirect();
        $this->post($url, ['reason' => 'Duplikat'])->assertConflict();
        $id = DB::table('booking_cancellation_requests')->value('id');
        $this->actingAs($tenant->user)->patch('/owner/bookings/cancellation-requests/'.$id, ['decision' => 'approve'])->assertRedirect();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'gagal']);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
        $this->assertDatabaseCount('refunds', 0);
    }

    private function credential(): string
    {
        $job = Queue::pushed(SendTeamInvitation::class)->last();

        return (new \ReflectionProperty($job, 'credential'))->getValue($job);
    }
}

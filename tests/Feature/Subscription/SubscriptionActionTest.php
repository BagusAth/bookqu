<?php

declare(strict_types=1);

namespace Tests\Feature\Subscription;

use App\Actions\Subscription\ActivateSubscription;
use App\Actions\Subscription\CreateTrialSubscription;
use App\Actions\Subscription\GetSubscriptionOverview;
use App\Domain\Subscription\PlanCapability;
use App\Domain\Subscription\SubscriptionState;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_trial_subscription_action(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $tenant = Tenant::create([
            'iduser'      => $owner->id,
            'namabisnis'  => 'New Trial Spa',
            'slug'        => 'new-trial-spa',
            'jenisbisnis' => 'Spa',
            'alamat'      => 'Jl. Spa No. 1',
            'nomorhp'     => '0811111111',
        ]);

        $action = new CreateTrialSubscription();
        $subscription = $action->execute($tenant, 7);

        $this->assertEquals(SubscriptionState::STATUS_TRIAL, $subscription->status);
        $this->assertEquals($tenant->id, $subscription->idtenant);
        $this->assertNotNull($subscription->trial_berakhir);
        $this->assertEquals(PlanCapability::PLAN_PRO, $subscription->plan->namapaket);
    }

    public function test_activate_subscription_action_is_idempotent(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $tenant = Tenant::create([
            'iduser'      => $owner->id,
            'namabisnis'  => 'Salon Pro',
            'slug'        => 'salon-pro',
            'jenisbisnis' => 'Salon',
            'alamat'      => 'Jl. Salon 2',
            'nomorhp'     => '0822222222',
        ]);

        $plan = Plan::create([
            'namapaket'    => 'pro',
            'hargabulanan' => 499000,
            'maxlayanan'   => 0,
            'maxbooking'   => 0,
            'isunlimited'  => true,
        ]);

        $payment = Payment::create([
            'idtenant'   => $tenant->id,
            'idplan'     => $plan->id,
            'tipe'       => 'subscription',
            'jumlah'     => 499000,
            'status'     => 'sukses',
            'metode'     => 'bank_transfer',
            'order_id'   => 'BQ-20260926-9999',
            'expired_at' => now()->addHour(),
        ]);

        $action = new ActivateSubscription();

        // First execution
        $sub1 = $action->execute($payment);
        $this->assertEquals(SubscriptionState::STATUS_ACTIVE, $sub1->status);
        $this->assertEquals($tenant->id, $sub1->idtenant);

        // Second execution with same payment (idempotent)
        $sub2 = $action->execute($payment);
        $this->assertEquals($sub1->id, $sub2->id);

        $totalActive = Subscription::withoutGlobalScopes()->where('idtenant', $tenant->id)->where('status', 'active')->count();
        $this->assertEquals(1, $totalActive);
    }

    public function test_get_subscription_overview_action(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $tenant = Tenant::create([
            'iduser'      => $owner->id,
            'namabisnis'  => 'Barber Small',
            'slug'        => 'barber-small',
            'jenisbisnis' => 'Barbershop',
            'alamat'      => 'Jl. Barber 3',
            'nomorhp'     => '0833333333',
        ]);

        $plan = Plan::create([
            'namapaket'    => 'small',
            'hargabulanan' => 149000,
            'maxlayanan'   => 5,
            'maxbooking'   => 300,
            'isunlimited'  => false,
        ]);

        Subscription::create([
            'idtenant' => $tenant->id,
            'idplan'   => $plan->id,
            'status'   => 'active',
        ]);

        $action = new GetSubscriptionOverview();
        $overview = $action->execute($tenant);

        $this->assertNotNull($overview['langgananaktif']);
        $this->assertEquals(5, $overview['maxlayanan']);
        $this->assertEquals(300, $overview['maxbooking']);
        $this->assertEquals(2, $overview['maxstaff']);
        $this->assertFalse($overview['statustrial']);
    }

    public function test_trial_expiration_is_blocked_by_middleware(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenant = Tenant::create([
            'iduser'      => $owner->id,
            'namabisnis'  => 'Expired Trial Clinic',
            'slug'        => 'expired-trial-clinic',
            'jenisbisnis' => 'Clinic',
            'alamat'      => 'Jl. Sehat 4',
            'nomorhp'     => '0844444444',
        ]);

        $plan = Plan::create([
            'namapaket'    => 'pro',
            'hargabulanan' => 499000,
            'maxlayanan'   => 0,
            'maxbooking'   => 0,
            'isunlimited'  => true,
        ]);

        // Trial whose end date is in the past, even if status still says 'trial'
        Subscription::create([
            'idtenant'       => $tenant->id,
            'idplan'         => $plan->id,
            'status'         => 'trial',
            'trial_berakhir' => Carbon::now()->subHour(),
        ]);

        $this->actingAs($owner);
        session(['current_tenant_id' => $tenant->id]);

        $response = $this->get('/owner/analytics');
        $response->assertRedirect(route('owner.subscription'));
        $response->assertSessionHas('error', 'Langganan Anda telah habis, mohon perpanjang.');
    }

    public function test_tenant_isolation_on_subscription_checkout_payment(): void
    {
        $ownerA = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenantA = Tenant::create([
            'iduser'      => $ownerA->id,
            'namabisnis'  => 'Tenant A',
            'slug'        => 'tenant-a',
            'jenisbisnis' => 'Business A',
            'alamat'      => 'Jl. A',
            'nomorhp'     => '0855555555',
        ]);

        $ownerB = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenantB = Tenant::create([
            'iduser'      => $ownerB->id,
            'namabisnis'  => 'Tenant B',
            'slug'        => 'tenant-b',
            'jenisbisnis' => 'Business B',
            'alamat'      => 'Jl. B',
            'nomorhp'     => '0866666666',
        ]);

        $plan = Plan::create([
            'namapaket'    => 'pro',
            'hargabulanan' => 499000,
            'maxlayanan'   => 0,
            'maxbooking'   => 0,
            'isunlimited'  => true,
        ]);

        $paymentB = Payment::create([
            'idtenant'   => $tenantB->id,
            'idplan'     => $plan->id,
            'tipe'       => 'subscription',
            'jumlah'     => 499000,
            'status'     => 'pending',
            'metode'     => 'midtrans',
            'order_id'   => 'BQ-20260926-0002',
            'expired_at' => now()->addHour(),
        ]);

        // Owner A tries to access Owner B's payment
        $response = $this->actingAs($ownerA)
            ->withSession(['current_tenant_id' => $tenantA->id])
            ->get(route('owner.checkout.payment', $paymentB));

        $response->assertStatus(403);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionRulesAndMechanismsTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $planSmall;
    protected Plan $planMedium;
    protected Plan $planPro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planSmall = Plan::firstOrCreate(
            ['namapaket' => 'small'],
            [
                'hargabulanan' => 149000,
                'maxlayanan'   => 5,
                'maxbooking'   => 300,
                'isunlimited'  => false,
            ]
        );

        $this->planMedium = Plan::firstOrCreate(
            ['namapaket' => 'medium'],
            [
                'hargabulanan' => 299000,
                'maxlayanan'   => 0,
                'maxbooking'   => 500,
                'isunlimited'  => false,
            ]
        );

        $this->planPro = Plan::firstOrCreate(
            ['namapaket' => 'pro'],
            [
                'hargabulanan' => 499000,
                'maxlayanan'   => 0,
                'maxbooking'   => 0,
                'isunlimited'  => true,
            ]
        );
    }

    /**
     * 1. Pro plan tenant has unlimited booking and should NEVER encounter daily booking limit error.
     */
    public function test_pro_plan_has_no_daily_or_monthly_booking_limit(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenant = Tenant::create([
            'iduser'      => $owner->id,
            'namabisnis'  => 'Pro Studio',
            'jenisbisnis' => 'Photography',
            'slug'        => 'pro-studio',
            'alamat'      => 'Jl. Pro No. 1',
            'nomorhp'     => '08123456789',
        ]);

        Subscription::create([
            'idtenant' => $tenant->id,
            'idplan'   => $this->planPro->id,
            'status'   => 'active',
        ]);

        $service = Service::create([
            'idtenant'    => $tenant->id,
            'namalayanan' => 'Pro Portrait',
            'harga'       => 150000,
            'durasi'      => 60,
            'is_active'   => true,
        ]);

        $bookingDate = Carbon::tomorrow()->toDateString();

        // Create an available schedule slot
        $schedule = Schedule::create([
            'idtenant'    => $tenant->id,
            'idlayanan'   => $service->id,
            'tanggal'     => $bookingDate,
            'jam_mulai'   => '10:00:00',
            'jam_selesai' => '11:00:00',
            'status'      => 'tersedia',
        ]);

        // Customer selects date
        $response = $this->withSession([
            'booking' => [
                'tenant_id'  => $tenant->id,
                'service_id' => $service->id,
            ],
        ])->post('/' . $tenant->slug . '/booking/select-date', [
            'tanggal' => $bookingDate,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('customer.booking.time', $tenant->slug));
    }

    /**
     * 2. Small plan is limited to 300 bookings/month and blocks when quota is exceeded.
     */
    public function test_small_plan_enforces_monthly_booking_quota(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenant = Tenant::create([
            'iduser'      => $owner->id,
            'namabisnis'  => 'Small Barber',
            'jenisbisnis' => 'Barbershop',
            'slug'        => 'small-barber',
            'alamat'      => 'Jl. Small No. 2',
            'nomorhp'     => '08234567890',
        ]);

        Subscription::create([
            'idtenant' => $tenant->id,
            'idplan'   => $this->planSmall->id,
            'status'   => 'active',
        ]);

        $service = Service::create([
            'idtenant'    => $tenant->id,
            'namalayanan' => 'Haircut',
            'harga'       => 50000,
            'durasi'      => 30,
            'is_active'   => true,
        ]);

        $bookingDate = Carbon::tomorrow()->toDateString();

        Schedule::create([
            'idtenant'    => $tenant->id,
            'idlayanan'   => $service->id,
            'tanggal'     => $bookingDate,
            'jam_mulai'   => '10:00:00',
            'jam_selesai' => '10:30:00',
            'status'      => 'tersedia',
        ]);

        // Simulate 300 existing bookings in this month for this tenant
        Booking::factory()->count(300)->create([
            'idtenant'       => $tenant->id,
            'idlayanan'      => $service->id,
            'tanggalbooking' => $bookingDate,
            'status'         => 'paid',
        ]);

        $response = $this->withSession([
            'booking' => [
                'tenant_id'  => $tenant->id,
                'service_id' => $service->id,
            ],
        ])->post('/' . $tenant->slug . '/booking/select-date', [
            'tanggal' => $bookingDate,
        ]);

        $response->assertSessionHasErrors('tanggal');
        $response->assertRedirect(route('customer.booking.date', $tenant->slug));
    }

    /**
     * 3. Small plan enforces staff limit of 2.
     */
    public function test_small_plan_enforces_staff_limit_of_two(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenant = Tenant::create([
            'iduser'      => $owner->id,
            'namabisnis'  => 'Salon Small',
            'jenisbisnis' => 'Salon',
            'slug'        => 'salon-small',
            'alamat'      => 'Jl. Salon 1',
            'nomorhp'     => '08345678901',
        ]);

        Subscription::create([
            'idtenant' => $tenant->id,
            'idplan'   => $this->planSmall->id,
            'status'   => 'active',
        ]);

        $this->actingAs($owner);
        session(['current_tenant_id' => $tenant->id]);

        // Staff 1
        $res1 = $this->post('/owner/staff', [
            'name' => 'Staff 1',
            'role' => 'Stylist',
        ]);
        $res1->assertRedirect(route('owner.staff-resources', ['tab' => 'staff']));
        $this->assertDatabaseHas('staff', ['name' => 'Staff 1', 'idtenant' => $tenant->id]);

        // Staff 2
        $res2 = $this->post('/owner/staff', [
            'name' => 'Staff 2',
            'role' => 'Colorist',
        ]);
        $res2->assertRedirect(route('owner.staff-resources', ['tab' => 'staff']));
        $this->assertDatabaseHas('staff', ['name' => 'Staff 2', 'idtenant' => $tenant->id]);

        // Staff 3 (should be blocked)
        $res3 = $this->post('/owner/staff', [
            'name' => 'Staff 3',
            'role' => 'Assistant',
        ]);
        $res3->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('staff', ['name' => 'Staff 3', 'idtenant' => $tenant->id]);
    }

    /**
     * 4. Medium plan allows up to 15 staff, unlimited services, and access to Analytics & Export.
     */
    public function test_medium_plan_features_and_limits(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenant = Tenant::create([
            'iduser'      => $owner->id,
            'namabisnis'  => 'Medium Clinic',
            'jenisbisnis' => 'Klinik',
            'slug'        => 'medium-clinic',
            'alamat'      => 'Jl. Medium 15',
            'nomorhp'     => '08456789012',
        ]);

        Subscription::create([
            'idtenant' => $tenant->id,
            'idplan'   => $this->planMedium->id,
            'status'   => 'active',
        ]);

        $this->actingAs($owner);
        session(['current_tenant_id' => $tenant->id]);

        // Access Analytics and CSV export (allowed for Medium)
        $resAnalytics = $this->get('/owner/analytics');
        $resAnalytics->assertStatus(200);

        $resExport = $this->get('/owner/analytics/export');
        $resExport->assertStatus(200);

        // Access Landing page (Pro only, Medium should be redirected)
        $resLanding = $this->get('/owner/landing-page');
        $resLanding->assertRedirect(route('owner.subscription'));
        $resLanding->assertSessionHas('error');

        // Create 6 services (more than Small's 5)
        $category = Category::create(['idtenant' => $tenant->id, 'name' => 'Care']);
        app(\App\Support\TenantContext::class)->setTenantId($tenant->id);
        for ($i = 1; $i <= 6; $i++) {
            $resService = $this->post('/owner/programs', [
                'namalayanan' => "Service $i",
                'harga'       => 100000,
                'durasi'      => 45,
                'idcategory'  => $category->id,
            ]);
            $resService->assertSessionHasNoErrors();
            $resService->assertRedirect(route('owner.programs'));
        }
        $this->assertEquals(6, Service::withoutGlobalScopes()->where('idtenant', $tenant->id)->count());

        // Fill 15 staff
        for ($s = 1; $s <= 15; $s++) {
            Staff::create([
                'idtenant' => $tenant->id,
                'name'     => "Staff $s",
                'role'     => 'Specialist',
            ]);
        }

        // 16th staff attempt should fail
        $res16 = $this->post('/owner/staff', [
            'name' => 'Staff 16',
            'role' => 'Nurse',
        ]);
        $res16->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('staff', ['name' => 'Staff 16', 'idtenant' => $tenant->id]);
    }

    /**
     * 5. Small plan owner cannot access Analytics.
     */
    public function test_small_plan_cannot_access_analytics(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $tenant = Tenant::create([
            'iduser'      => $owner->id,
            'namabisnis'  => 'Small Cafe',
            'jenisbisnis' => 'F&B',
            'slug'        => 'small-cafe',
            'alamat'      => 'Jl. Cafe 1',
            'nomorhp'     => '08567890123',
        ]);

        Subscription::create([
            'idtenant' => $tenant->id,
            'idplan'   => $this->planSmall->id,
            'status'   => 'active',
        ]);

        $this->actingAs($owner);
        session(['current_tenant_id' => $tenant->id]);

        $response = $this->get('/owner/analytics');
        $response->assertRedirect(route('owner.subscription'));
        $response->assertSessionHas('error');
    }
}

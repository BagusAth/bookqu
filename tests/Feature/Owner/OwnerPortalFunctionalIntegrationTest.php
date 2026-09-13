<?php

namespace Tests\Feature\Owner;

use App\Models\AdditionalItem;
use App\Models\Asset;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Resource;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerPortalFunctionalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerA;
    protected Tenant $tenantA;
    protected Plan $freePlan;
    protected Plan $proPlan;

    protected User $ownerB;
    protected Tenant $tenantB;

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->freePlan = Plan::firstOrCreate(
            ['namapaket' => 'small'],
            [
                'hargabulanan' => 0,
                'maxlayanan'   => 2,
                'maxbooking'   => 50,
                'isunlimited'  => false,
            ]
        );

        $this->proPlan = Plan::firstOrCreate(
            ['namapaket' => 'pro'],
            [
                'hargabulanan' => 100000,
                'maxlayanan'   => 100,
                'maxbooking'   => 1000,
                'isunlimited'  => true,
            ]
        );

        // Owner A (Active Pro Subscription)
        $this->ownerA = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenantA = Tenant::create([
            'iduser'      => $this->ownerA->id,
            'namabisnis'  => 'Salon A',
            'jenisbisnis' => 'Salon',
            'slug'        => 'salon-a',
            'alamat'      => 'Jl. Anggrek No 1',
            'nomorhp'     => '0811111111',
        ]);
        Subscription::create([
            'idtenant'       => $this->tenantA->id,
            'idplan'         => $this->proPlan->id,
            'status'         => 'active',
            'trial_berakhir' => now()->addDays(30),
        ]);

        // Owner B (Free Plan, for isolation testing)
        $this->ownerB = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenantB = Tenant::create([
            'iduser'      => $this->ownerB->id,
            'namabisnis'  => 'Barber B',
            'jenisbisnis' => 'Barbershop',
            'slug'        => 'barber-b',
            'alamat'      => 'Jl. Mawar No 2',
            'nomorhp'     => '0822222222',
        ]);
        Subscription::create([
            'idtenant' => $this->tenantB->id,
            'idplan'   => $this->freePlan->id,
            'status'   => 'active',
        ]);
    }

    /**
     * 1. Functional Data Flow:
     * Category -> Service -> Staff & Resource -> Additional Items
     */
    public function test_business_domain_bidirectional_relationships(): void
    {
        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);
        app(TenantContext::class)->setTenantId($this->tenantA->id);

        // Create Category
        $category = Category::create([
            'idtenant' => $this->tenantA->id,
            'name'     => 'Hair Treatments',
        ]);

        // Create Staff
        $staff = Staff::create([
            'idtenant'  => $this->tenantA->id,
            'name'      => 'Jessica Stylist',
            'role'      => 'Senior Hairdresser',
            'is_active' => true,
        ]);

        // Create Resource
        $resource = Resource::create([
            'idtenant'  => $this->tenantA->id,
            'name'      => 'Styling Chair 1',
            'type'      => 'Chair',
            'is_active' => true,
        ]);

        // Create Additional Item
        $item = AdditionalItem::create([
            'idtenant'  => $this->tenantA->id,
            'name'      => 'Organic Hair Serum',
            'price'     => 50000,
            'stock'     => 10,
            'is_active' => true,
        ]);

        // Create Service and assign all related business entities
        $service = Service::create([
            'idtenant'    => $this->tenantA->id,
            'idcategory'  => $category->id,
            'namalayanan' => 'Hair Styling Deluxe',
            'harga'       => 150000,
            'durasi'      => 60,
            'is_active'   => true,
        ]);
        $service->staff()->attach($staff->id);
        $service->resources()->attach($resource->id);
        $service->additionalItems()->attach($item->id);

        // Verify relationships
        $loadedService = Service::with(['category', 'staff', 'resources', 'additionalItems'])->find($service->id);
        $this->assertEquals('Hair Treatments', $loadedService->category->name);
        $this->assertCount(1, $loadedService->staff);
        $this->assertEquals('Jessica Stylist', $loadedService->staff->first()->name);
        $this->assertCount(1, $loadedService->resources);
        $this->assertEquals('Styling Chair 1', $loadedService->resources->first()->name);
        $this->assertCount(1, $loadedService->additionalItems);
        $this->assertEquals('Organic Hair Serum', $loadedService->additionalItems->first()->name);
    }

    /**
     * 2. Feature Gating:
     * Non-Pro is redirected from Pro features with upgrade warning.
     * Pro can access Pro features.
     */
    public function test_subscription_feature_gating_backend_and_ui(): void
    {
        // Owner B is on Free plan
        $this->actingAs($this->ownerB);
        session(['current_tenant_id' => $this->tenantB->id]);

        // Accessing Landing Page (PRO feature) redirects to subscription
        $responseNonPro = $this->get('/owner/landing-page');
        $responseNonPro->assertRedirect(route('owner.subscription'));
        $responseNonPro->assertSessionHas('error');

        // Accessing Analytics (PRO feature) redirects to subscription
        $responseAnalyticsNonPro = $this->get('/owner/analytics');
        $responseAnalyticsNonPro->assertRedirect(route('owner.subscription'));
        $responseAnalyticsNonPro->assertSessionHas('error');

        // Owner A is on Pro plan -> both succeed
        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        $responsePro = $this->get('/owner/landing-page');
        $responsePro->assertStatus(200);

        $responseAnalyticsPro = $this->get('/owner/analytics');
        $responseAnalyticsPro->assertStatus(200);
    }

    /**
     * 3. End-to-End Flow:
     * Booking created -> Payment confirmed -> Calendar updated -> Dashboard real metrics updated
     */
    public function test_e2e_booking_to_dashboard_and_calendar_integration(): void
    {
        app(TenantContext::class)->setTenantId($this->tenantA->id);
        $service = Service::create([
            'idtenant'    => $this->tenantA->id,
            'namalayanan' => 'Facial Treatment',
            'harga'       => 200000,
            'durasi'      => 60,
            'is_active'   => true,
        ]);

        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $schedule = Schedule::create([
            'idtenant'    => $this->tenantA->id,
            'idlayanan'   => $service->id,
            'tanggal'     => $tomorrow,
            'jam_mulai'   => '14:00:00',
            'jam_selesai' => '14:59:00',
            'status'      => 'tersedia',
        ]);

        $payment = Payment::create([
            'idtenant'       => $this->tenantA->id,
            'tipe'           => 'booking',
            'jumlah'         => 200000,
            'status'         => 'sukses',
            'metode'         => 'qris',
            'order_id'       => 'BKG-E2E-TEST-1',
            'nama_pembayar'  => 'Bella Customer',
            'email_pembayar' => 'bella@example.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $booking = Booking::create([
            'idtenant'       => $this->tenantA->id,
            'idlayanan'      => $service->id,
            'idschedule'     => $schedule->id,
            'idpayment'      => $payment->id,
            'namapelanggan'  => 'Bella Customer',
            'email'          => 'bella@example.com',
            'nomorhp'        => '08123456789',
            'tanggalbooking' => $tomorrow,
            'jam'            => '14:00:00',
            'status'         => 'paid',
        ]);

        $this->actingAs($this->ownerA);
        session(['current_tenant_id' => $this->tenantA->id]);

        // 1. Calendar displays the booking
        $calendarRes = $this->get(route('owner.calendar', ['view' => 'day', 'date' => $tomorrow]));
        $calendarRes->assertStatus(200);
        $calendarRes->assertSee('Bella Customer');
        $calendarRes->assertSee('Facial Treatment');
        $calendarRes->assertSee('Confirmed');

        // 2. Customer CRM displays Bella with total spending Rp 200.000
        $customerRes = $this->get(route('owner.customers'));
        $customerRes->assertStatus(200);
        $customerRes->assertSee('Bella Customer');
        $customerRes->assertSee('200.000');

        // 3. Dashboard displays revenue Rp 200.000 and total booking 1
        $dashboardRes = $this->get(route('owner.dashboard'));
        $dashboardRes->assertStatus(200);
        $dashboardRes->assertSee('200.000');
    }
}

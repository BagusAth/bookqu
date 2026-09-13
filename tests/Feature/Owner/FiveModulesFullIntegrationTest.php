<?php

namespace Tests\Feature\Owner;

use App\Models\AdditionalItem;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Resource;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiveModulesFullIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Tenant $tenant;
    protected Service $service;
    protected Schedule $schedule;

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::firstOrCreate(
            ['namapaket' => 'pro'],
            [
                'hargabulanan' => 100000,
                'maxlayanan'   => 50,
                'maxbooking'   => 500,
                'isunlimited'  => true,
            ]
        );

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->tenant = Tenant::create([
            'iduser'      => $this->owner->id,
            'namabisnis'  => 'Barbershop Eksklusif',
            'jenisbisnis' => 'Barbershop',
            'slug'        => 'barbershop-eksklusif',
            'alamat'      => 'Jl. Sudirman No. 10',
            'nomorhp'     => '081234567890',
        ]);

        Subscription::create([
            'idtenant'       => $this->tenant->id,
            'idplan'         => $plan->id,
            'status'         => 'active',
            'trial_berakhir' => now()->addDays(30),
        ]);

        $this->service = Service::create([
            'idtenant'    => $this->tenant->id,
            'namalayanan' => 'Gentleman Haircut & Wash',
            'harga'       => 75000,
            'durasi'      => 45,
            'is_active'   => true,
        ]);

        // Create schedule today so it is always present across all period filters
        $this->schedule = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => Carbon::today()->toDateString(),
            'jam_mulai'   => '10:00:00',
            'jam_selesai' => '10:45:00',
            'status'      => 'tersedia',
        ]);

        app(TenantContext::class)->setTenantId($this->tenant->id);
    }

    // =========================================================================
    // 1. CALENDAR MODULE TESTS
    // =========================================================================

    public function test_calendar_views_and_filter_render_successfully(): void
    {
        $this->actingAs($this->owner);
        session(['current_tenant_id' => $this->tenant->id]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $today = Carbon::today()->toDateString();

        // Day View
        $resDay = $this->get(route('owner.calendar', ['view' => 'day', 'date' => $today]));
        $resDay->assertStatus(200);
        $resDay->assertSee('Gentleman Haircut');

        // Week View
        $resWeek = $this->get(route('owner.calendar', ['view' => 'week', 'date' => $today]));
        $resWeek->assertStatus(200);

        // Month View
        $resMonth = $this->get(route('owner.calendar', ['view' => 'month', 'date' => $today]));
        $resMonth->assertStatus(200);
    }

    public function test_calendar_quick_walkin_booking_creation(): void
    {
        $this->actingAs($this->owner);
        session(['current_tenant_id' => $this->tenant->id]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $response = $this->post(route('owner.bookings.walkin'), [
            'idschedule'     => $this->schedule->id,
            'namapelanggan'  => 'Walkin Guest John',
            'nomorhp'        => '081299998888',
            'email'          => 'walkin@example.com',
            'metode'         => 'cash',
            'catatan'        => 'Tamu walk-in langsung di kasir',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('bookings', [
            'idtenant'       => $this->tenant->id,
            'idschedule'     => $this->schedule->id,
            'namapelanggan'  => 'Walkin Guest John',
            'nomorhp'        => '081299998888',
            'status'         => 'paid',
        ]);

        $booking = Booking::withoutGlobalScopes()->where('idschedule', $this->schedule->id)->first();
        $this->assertNotNull($booking);
        $this->assertNotNull($booking->idpayment);

        $this->assertDatabaseHas('payments', [
            'id'             => $booking->idpayment,
            'idtenant'       => $this->tenant->id,
            'metode'         => 'cash',
            'status'         => 'sukses',
            'jumlah'         => 75000,
        ]);
    }

    public function test_calendar_quick_status_transition(): void
    {
        $this->actingAs($this->owner);
        session(['current_tenant_id' => $this->tenant->id]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $schedule2 = Schedule::create([
            'idtenant'    => $this->tenant->id,
            'idlayanan'   => $this->service->id,
            'tanggal'     => Carbon::today()->toDateString(),
            'jam_mulai'   => '11:00:00',
            'jam_selesai' => '11:45:00',
            'status'      => 'tersedia',
        ]);

        // Booking 1: Test transition from paid to completed
        $booking1 = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $this->service->id,
            'idschedule'     => $this->schedule->id,
            'namapelanggan'  => 'Status Test Guest 1',
            'nomorhp'        => '081200001111',
            'email'          => 'status1@example.com',
            'tanggalbooking' => Carbon::today()->toDateString(),
            'jam'            => '10:00:00',
            'status'         => 'paid',
        ]);

        $resComplete = $this->patch(route('owner.bookings.status', $booking1->id), [
            'status' => 'completed',
        ]);
        $resComplete->assertRedirect();
        $this->assertDatabaseHas('bookings', ['id' => $booking1->id, 'status' => 'completed']);

        // Booking 2: Test transition from paid to cancelled
        $booking2 = Booking::create([
            'idtenant'       => $this->tenant->id,
            'idlayanan'      => $this->service->id,
            'idschedule'     => $schedule2->id,
            'namapelanggan'  => 'Status Test Guest 2',
            'nomorhp'        => '081200002222',
            'email'          => 'status2@example.com',
            'tanggalbooking' => Carbon::today()->toDateString(),
            'jam'            => '11:00:00',
            'status'         => 'paid',
        ]);

        $resCancel = $this->patch(route('owner.bookings.status', $booking2->id), [
            'status' => 'cancelled',
        ]);
        $resCancel->assertRedirect();
        $this->assertDatabaseHas('bookings', ['id' => $booking2->id, 'status' => 'cancelled']);
    }

    // =========================================================================
    // 2. SCHEDULE REPORT MODULE TESTS
    // =========================================================================

    public function test_schedule_report_renders_with_period_filters(): void
    {
        $this->actingAs($this->owner);
        session(['current_tenant_id' => $this->tenant->id]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $periods = ['all', 'today', 'this_week', 'this_month', 'last_30_days'];

        foreach ($periods as $period) {
            $response = $this->get(route('owner.schedule-report', ['period' => $period]));
            $response->assertStatus(200);
            $response->assertSee('Schedule Report');
            $response->assertSee('Utilization Rate');
        }
    }

    public function test_schedule_report_export_csv(): void
    {
        $this->actingAs($this->owner);
        session(['current_tenant_id' => $this->tenant->id]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $response = $this->get(route('owner.schedule-report.export', ['period' => 'all']));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('schedule-report-', $response->headers->get('Content-Disposition'));

        // Validate content from stream
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Barbershop Eksklusif', $content);
        $this->assertStringContainsString('Gentleman Haircut', $content);
    }

    // =========================================================================
    // 3. CATEGORIES MODULE TESTS
    // =========================================================================

    public function test_categories_filtering_sorting_and_slug_sync(): void
    {
        $this->actingAs($this->owner);
        session(['current_tenant_id' => $this->tenant->id]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $catA = Category::create([
            'idtenant'  => $this->tenant->id,
            'name'      => 'Alpha Category',
            'is_active' => true,
        ]);

        $catB = Category::create([
            'idtenant'  => $this->tenant->id,
            'name'      => 'Beta Category',
            'is_active' => false,
        ]);

        // Filter active
        $resActive = $this->get(route('owner.categories', ['status' => 'active']));
        $resActive->assertStatus(200);
        $resActive->assertSee('Alpha Category');
        $resActive->assertDontSee('Beta Category');

        // Filter inactive
        $resInactive = $this->get(route('owner.categories', ['status' => 'inactive']));
        $resInactive->assertStatus(200);
        $resInactive->assertSee('Beta Category');
        $resInactive->assertDontSee('Alpha Category');

        // Sorting
        $resSort = $this->get(route('owner.categories', ['sort' => 'name_desc']));
        $resSort->assertStatus(200);
        $resSort->assertSee('Beta Category');

        // Update name and assert slug sync
        $resUpdate = $this->put(route('owner.categories.update', $catA->id), [
            'name'        => 'Alpha Category Premium',
            'description' => 'Updated desc',
            'is_active'   => 1,
        ]);
        $resUpdate->assertRedirect(route('owner.categories'));

        $this->assertDatabaseHas('categories', [
            'id'   => $catA->id,
            'name' => 'Alpha Category Premium',
            'slug' => 'alpha-category-premium',
        ]);
    }

    // =========================================================================
    // 4. STAFF & RESOURCES MODULE TESTS
    // =========================================================================

    public function test_staff_and_resources_crud_and_pivot_relationships(): void
    {
        $this->actingAs($this->owner);
        session(['current_tenant_id' => $this->tenant->id]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        // Create Staff with preset working hours
        $resStaff = $this->post(route('owner.staff.store'), [
            'name'                  => 'Rian Barber Pro',
            'role'                  => 'Senior Barber',
            'phone'                 => '08555444333',
            'availability_schedule' => 'Senin - Jumat (09:00 - 17:00)',
            'service_ids'           => [$this->service->id],
        ]);
        $resStaff->assertRedirect();

        $this->assertDatabaseHas('staff', [
            'idtenant'              => $this->tenant->id,
            'name'                  => 'Rian Barber Pro',
            'availability_schedule' => 'Senin - Jumat (09:00 - 17:00)',
        ]);

        $staff = Staff::withoutGlobalScopes()->where('name', 'Rian Barber Pro')->first();
        $this->assertNotNull($staff);
        $this->assertDatabaseHas('service_staff', [
            'idstaff'   => $staff->id,
            'idservice' => $this->service->id,
        ]);

        // Create Resource
        $resResource = $this->post(route('owner.resources.store'), [
            'name'        => 'Kursi Barber Hidrolik #1',
            'type'        => 'Barber Chair',
            'capacity'    => 1,
            'service_ids' => [$this->service->id],
        ]);
        $resResource->assertRedirect();

        $this->assertDatabaseHas('resources', [
            'idtenant' => $this->tenant->id,
            'name'     => 'Kursi Barber Hidrolik #1',
        ]);

        $resource = Resource::withoutGlobalScopes()->where('name', 'Kursi Barber Hidrolik #1')->first();
        $this->assertNotNull($resource);
        $this->assertDatabaseHas('service_resource', [
            'idresource' => $resource->id,
            'idservice'  => $this->service->id,
        ]);

        // Toggle staff active state
        $resToggle = $this->post(route('owner.staff.toggle', $staff->id));
        $resToggle->assertRedirect();
        $this->assertDatabaseHas('staff', ['id' => $staff->id, 'is_active' => false]);
    }

    // =========================================================================
    // 5. ADDITIONAL ITEMS & PUBLIC CHECKOUT INTEGRATION TESTS
    // =========================================================================

    public function test_additional_items_linked_to_service_and_public_checkout(): void
    {
        // 1. Owner creates Additional Item and assigns it to service
        $this->actingAs($this->owner);
        session(['current_tenant_id' => $this->tenant->id]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $resAddon = $this->post(route('owner.additional-items.store'), [
            'name'         => 'Pomade Premium Matte',
            'description'  => 'Pomade wangi maskulin natural',
            'price'        => 35000,
            'stock'        => 5,
            'is_unlimited' => 0,
            'service_ids'  => [$this->service->id],
        ]);
        $resAddon->assertRedirect(route('owner.additional-items'));

        $this->assertDatabaseHas('additional_items', [
            'idtenant' => $this->tenant->id,
            'name'     => 'Pomade Premium Matte',
        ]);

        $addon = AdditionalItem::withoutGlobalScopes()->where('name', 'Pomade Premium Matte')->first();
        $this->assertNotNull($addon);
        $this->assertDatabaseHas('additional_item_service', [
            'idadditional_item' => $addon->id,
            'idservice'         => $this->service->id,
        ]);

        // Also create a staff for checkout testing
        $staff = Staff::create([
            'idtenant'              => $this->tenant->id,
            'name'                  => 'Bima Stylist',
            'role'                  => 'Expert Barber',
            'availability_schedule' => 'Shift Pagi (08:00 - 15:00)',
            'is_active'             => true,
        ]);
        $this->service->staff()->attach($staff->id);

        // 2. Customer visits checkout page
        $sessionData = [
            'booking' => [
                'tenant_id'   => $this->tenant->id,
                'service_id'  => $this->service->id,
                'tanggal'     => $this->schedule->tanggal,
                'jam'         => '10:00',
                'schedule_id' => $this->schedule->id,
            ],
        ];

        $checkoutPageRes = $this->withSession($sessionData)
            ->get(route('customer.booking.checkout', $this->tenant->slug));

        $checkoutPageRes->assertStatus(200);
        $checkoutPageRes->assertSee('Pomade Premium Matte');
        $checkoutPageRes->assertSee('Bima Stylist');

        // 3. Customer submits checkout with addon & preferred staff
        \Mockery::mock('alias:Midtrans\Snap')->shouldReceive('getSnapToken')->andReturn('mocked-snap-token-addon');

        $postData = [
            'namapelanggan'   => 'Customer Adit',
            'email'           => 'adit@example.com',
            'nomorhp'         => '081234560000',
            'catatan'         => 'Mohon jangan terlalu pendek sampingnya.',
            'staff_id'        => $staff->id,
            'selected_addons' => [$addon->id],
        ];

        $processRes = $this->withSession($sessionData)
            ->post(route('customer.booking.process-checkout', $this->tenant->slug), $postData);

        // Expected total: 75000 (service) + 35000 (addon) = 110000
        $processRes->assertRedirect();

        // Verify stock was decremented from 5 to 4
        $addon->refresh();
        $this->assertEquals(4, $addon->stock);

        // Verify booking created
        $booking = Booking::withoutGlobalScopes()->where('email', 'adit@example.com')->first();
        $this->assertNotNull($booking);
        $this->assertEquals('pending', $booking->status);
        $this->assertStringContainsString('Bima Stylist', $booking->catatan);
        $this->assertStringContainsString('Pomade Premium Matte', $booking->catatan);

        // Verify payment total
        $payment = Payment::withoutGlobalScopes()->find($booking->idpayment);
        $this->assertNotNull($payment);
        $this->assertEquals(110000, (float) $payment->jumlah);
    }
}

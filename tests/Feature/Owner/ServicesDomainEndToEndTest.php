<?php

namespace Tests\Feature\Owner;

use App\Models\AdditionalItem;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Resource;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServicesDomainEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerA;
    protected Tenant $tenantA;
    protected User $ownerB;
    protected Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        // Owner A
        $this->ownerA = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);
        $this->tenantA = Tenant::create([
            'iduser' => $this->ownerA->id,
            'namabisnis' => 'Yoga Sanctuary',
            'jenisbisnis' => 'Kebugaran',
            'slug' => 'yoga-sanctuary',
            'alamat' => 'Jl. Senopati No. 12',
            'nomorhp' => '081234567890',
        ]);

        // Owner B
        $this->ownerB = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);
        $this->tenantB = Tenant::create([
            'iduser' => $this->ownerB->id,
            'namabisnis' => 'Barbershop B',
            'jenisbisnis' => 'Barbershop',
            'slug' => 'barbershop-b',
            'alamat' => 'Jl. Wijaya No. 5',
            'nomorhp' => '089876543210',
        ]);
    }

    /**
     * Test Category CRUD, unique validation per tenant, and safe unlinking.
     */
    public function test_category_crud_and_unique_validation_per_tenant(): void
    {
        // 1. Create Category for Tenant A
        $res = $this->actingAs($this->ownerA)->post('/owner/categories', [
            'name' => 'Private Yoga',
            'description' => 'Sesi personal bersama instruktur bersertifikasi',
            'color' => 'indigo',
            'is_active' => '1',
        ]);
        $res->assertRedirect(route('owner.categories'));
        $this->assertDatabaseHas('categories', [
            'idtenant' => $this->tenantA->id,
            'name' => 'Private Yoga',
        ]);

        // 2. Duplicate name in Tenant A must fail validation
        $resDup = $this->actingAs($this->ownerA)->post('/owner/categories', [
            'name' => 'Private Yoga',
        ]);
        $resDup->assertSessionHasErrors('name');

        // 3. Same name in Tenant B must succeed (tenant-scoped uniqueness)
        $resB = $this->actingAs($this->ownerB)->post('/owner/categories', [
            'name' => 'Private Yoga',
            'description' => 'Tenant B category',
        ]);
        $resB->assertRedirect(route('owner.categories'));
        $this->assertDatabaseHas('categories', [
            'idtenant' => $this->tenantB->id,
            'name' => 'Private Yoga',
        ]);

        $catA = DB::table('categories')->where('idtenant', $this->tenantA->id)->where('name', 'Private Yoga')->first();

        // 4. Update category
        $resUpdate = $this->actingAs($this->ownerA)->put("/owner/categories/{$catA->id}", [
            'name' => 'Vinyasa Flow',
            'description' => 'Dynamic private yoga sessions',
            'color' => 'violet',
            'is_active' => '1',
        ]);
        $resUpdate->assertRedirect(route('owner.categories'));
        $this->assertDatabaseHas('categories', [
            'id' => $catA->id,
            'name' => 'Vinyasa Flow',
        ]);

        // 5. Safe unlinking when category is deleted
        $service = Service::create([
            'idtenant' => $this->tenantA->id,
            'namalayanan' => 'Sunrise Yoga',
            'harga' => 150000,
            'durasi' => 60,
            'idcategory' => $catA->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->ownerA)->delete("/owner/categories/{$catA->id}");
        $this->assertDatabaseMissing('categories', ['id' => $catA->id]);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'idcategory' => null, // Safely unlinked, NOT deleted
        ]);
    }

    /**
     * Test Service CRUD with Category, Staff, Resource, and Additional Item assignment.
     */
    public function test_service_crud_with_bidirectional_relationships(): void
    {
        $cat = Category::create([
            'idtenant' => $this->tenantA->id,
            'name' => 'Sound Healing',
            'is_active' => true,
        ]);

        $staff = Staff::create([
            'idtenant' => $this->tenantA->id,
            'name' => 'Maya Pratama',
            'role' => 'Lead Practitioner',
            'is_active' => true,
        ]);

        $resource = Resource::create([
            'idtenant' => $this->tenantA->id,
            'name' => 'Studio Lotus',
            'type' => 'Private Studio',
            'capacity' => 4,
            'is_active' => true,
        ]);

        $addon = AdditionalItem::create([
            'idtenant' => $this->tenantA->id,
            'name' => 'Essential Oil Aromatherapy',
            'price' => 50000,
            'is_active' => true,
        ]);

        // 1. Create Service with assignments
        $res = $this->actingAs($this->ownerA)->post('/owner/programs', [
            'namalayanan' => 'Tibetan Singing Bowl Therapy',
            'harga' => 250000,
            'durasi' => 60,
            'idcategory' => $cat->id,
            'deskripsi' => 'Deep vibrational relaxation session',
            'staff_ids' => [$staff->id],
            'resource_ids' => [$resource->id],
            'additional_item_ids' => [$addon->id],
        ]);
        $res->assertRedirect(route('owner.programs'));

        $service = DB::table('services')->where('idtenant', $this->tenantA->id)->where('namalayanan', 'Tibetan Singing Bowl Therapy')->first();
        $this->assertNotNull($service);

        // Verify pivot tables
        $this->assertDatabaseHas('service_staff', [
            'idservice' => $service->id,
            'idstaff' => $staff->id,
        ]);
        $this->assertDatabaseHas('service_resource', [
            'idservice' => $service->id,
            'idresource' => $resource->id,
        ]);
        $this->assertDatabaseHas('additional_item_service', [
            'idservice' => $service->id,
            'idadditional_item' => $addon->id,
        ]);

        // Verify Eloquent relationships
        app(TenantContext::class)->setTenantId($this->tenantA->id);
        $serviceModel = Service::find($service->id);
        $this->assertEquals($cat->name, $serviceModel->category->name);
        $this->assertEquals(1, $serviceModel->staff->count());
        $this->assertEquals('Maya Pratama', $serviceModel->staff->first()->name);
        $this->assertEquals(1, $serviceModel->resources->count());
        $this->assertEquals('Studio Lotus', $serviceModel->resources->first()->name);
        $this->assertEquals(1, $serviceModel->additionalItems->count());
        $this->assertEquals('Essential Oil Aromatherapy', $serviceModel->additionalItems->first()->name);
        app(TenantContext::class)->clear();

        // 2. Update Service assignments
        $this->actingAs($this->ownerA)->put("/owner/programs/{$service->id}", [
            'namalayanan' => 'Tibetan Singing Bowl Therapy (Advanced)',
            'harga' => 300000,
            'durasi' => 90,
            'idcategory' => $cat->id,
            'is_active' => 1,
            'staff_ids' => [$staff->id],
            'resource_ids' => [], // Unassign resource
            'additional_item_ids' => [$addon->id],
        ]);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'durasi' => 90,
        ]);
        $this->assertDatabaseMissing('service_resource', [
            'idservice' => $service->id,
            'idresource' => $resource->id,
        ]);

        // 3. Delete Service - asserts pivot records are detached
        $this->actingAs($this->ownerA)->delete("/owner/programs/{$service->id}");
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
        $this->assertDatabaseMissing('service_staff', ['idservice' => $service->id]);
        $this->assertDatabaseMissing('additional_item_service', ['idservice' => $service->id]);
    }

    /**
     * Test Schedule slot generation: interval strictly equals Service::durasi,
     * slot duration is jam_mulai to jam_mulai + durasi - 1 min,
     * and duplicate/conflicting slots are prevented.
     */
    public function test_schedule_generation_synchronizes_duration_strictly_with_service(): void
    {
        $service = Service::create([
            'idtenant' => $this->tenantA->id,
            'namalayanan' => 'Pilates Reformer',
            'harga' => 200000,
            'durasi' => 60, // 60 minutes
            'is_active' => true,
        ]);

        $testDate = Carbon::tomorrow()->format('Y-m-d');

        // 1. Attempt with invalid intervalslot (e.g. 45 min instead of 60 min) must fail
        $resInvalid = $this->actingAs($this->ownerA)->post('/owner/schedule/bulk-slots', [
            'jenisslot' => 'harian',
            'idlayanan' => $service->id,
            'tanggal' => $testDate,
            'jammulai' => '09:00',
            'jamselesai' => '12:00',
            'intervalslot' => 45,
        ]);
        $resInvalid->assertSessionHasErrors('intervalslot');

        // 2. Valid generation: intervalslot = 60
        $resValid = $this->actingAs($this->ownerA)->post('/owner/schedule/bulk-slots', [
            'jenisslot' => 'harian',
            'idlayanan' => $service->id,
            'tanggal' => $testDate,
            'jammulai' => '09:00',
            'jamselesai' => '12:00',
            'intervalslot' => 60,
        ]);
        $resValid->assertRedirect('/owner/schedule');

        // Slots should be:
        // Slot 1: 09:00:00 - 09:59:00
        // Slot 2: 10:00:00 - 10:59:00
        // Slot 3: 11:00:00 - 11:59:00
        $slot1 = DB::table('schedules')->where('idtenant', $this->tenantA->id)->where('idlayanan', $service->id)->whereDate('tanggal', $testDate)->where('jam_mulai', '09:00:00')->first();
        $this->assertNotNull($slot1);
        $this->assertEquals('09:59:00', $slot1->jam_selesai);

        $slot2 = DB::table('schedules')->where('idtenant', $this->tenantA->id)->where('idlayanan', $service->id)->whereDate('tanggal', $testDate)->where('jam_mulai', '10:00:00')->first();
        $this->assertNotNull($slot2);
        $this->assertEquals('10:59:00', $slot2->jam_selesai);

        $slot3 = DB::table('schedules')->where('idtenant', $this->tenantA->id)->where('idlayanan', $service->id)->whereDate('tanggal', $testDate)->where('jam_mulai', '11:00:00')->first();
        $this->assertNotNull($slot3);
        $this->assertEquals('11:59:00', $slot3->jam_selesai);

        $initialCount = DB::table('schedules')
            ->where('idtenant', $this->tenantA->id)
            ->where('idlayanan', $service->id)
            ->whereDate('tanggal', $testDate)
            ->count();
        $this->assertEquals(3, $initialCount);

        // 3. Repeating same generation must NOT create duplicate / conflicting slots
        $this->actingAs($this->ownerA)->post('/owner/schedule/bulk-slots', [
            'jenisslot' => 'harian',
            'idlayanan' => $service->id,
            'tanggal' => $testDate,
            'jammulai' => '09:00',
            'jamselesai' => '12:00',
            'intervalslot' => 60,
        ]);

        $afterCount = DB::table('schedules')
            ->where('idtenant', $this->tenantA->id)
            ->where('idlayanan', $service->id)
            ->whereDate('tanggal', $testDate)
            ->count();
        $this->assertEquals(3, $afterCount); // No duplicate slots created!
    }

    /**
     * Test Full End-to-End Pipeline:
     * Category -> Service -> Staff & Resource -> Additional Item -> Schedule -> Customer Booking
     */
    public function test_full_pipeline_from_owner_catalog_to_customer_booking(): void
    {
        // 1. Owner creates Category
        $cat = Category::create([
            'idtenant' => $this->tenantA->id,
            'name' => 'Holistic Wellness',
            'is_active' => true,
        ]);

        // 2. Owner creates Service
        $service = Service::create([
            'idtenant' => $this->tenantA->id,
            'namalayanan' => 'Aromatherapy Reflexology',
            'harga' => 175000,
            'durasi' => 60,
            'idcategory' => $cat->id,
            'is_active' => true,
        ]);

        // 3. Owner creates & assigns Staff & Resource
        $staff = Staff::create([
            'idtenant' => $this->tenantA->id,
            'name' => 'Dewi Sartika',
            'role' => 'Senior Reflexologist',
            'is_active' => true,
        ]);
        $service->staff()->attach($staff->id);

        $resource = Resource::create([
            'idtenant' => $this->tenantA->id,
            'name' => 'Reflexology Pod 1',
            'type' => 'Pod',
            'is_active' => true,
        ]);
        $service->resources()->attach($resource->id);

        // 4. Owner creates & assigns Additional Item
        $addon = AdditionalItem::create([
            'idtenant' => $this->tenantA->id,
            'name' => 'Hot Herbal Foot Compress',
            'price' => 35000,
            'is_active' => true,
        ]);
        $service->additionalItems()->attach($addon->id);

        // 5. Owner creates Schedule
        $bookingDate = Carbon::tomorrow()->format('Y-m-d');
        $schedule = Schedule::create([
            'idtenant' => $this->tenantA->id,
            'idlayanan' => $service->id,
            'tanggal' => $bookingDate,
            'jam_mulai' => '14:00:00',
            'jam_selesai' => '14:59:00',
            'status' => 'tersedia',
        ]);

        // ── CUSTOMER FLOW ──

        // Step A: Customer visits public service catalog
        $resCatalog = $this->get('/' . $this->tenantA->slug);
        $resCatalog->assertStatus(200);
        $resCatalog->assertSee('Aromatherapy Reflexology');
        $resCatalog->assertSee('Holistic Wellness'); // Category badge rendered

        // Step B: Customer selects program
        $resSelect = $this->post('/' . $this->tenantA->slug . '/booking/select-program', [
            'service_id' => $service->id,
        ]);
        $resSelect->assertRedirect(route('customer.booking.date', $this->tenantA->slug));

        // Step C: Customer selects date
        $resDate = $this->withSession([
            'booking' => [
                'tenant_id' => $this->tenantA->id,
                'service_id' => $service->id,
            ],
        ])->post('/' . $this->tenantA->slug . '/booking/select-date', [
            'tanggal' => $bookingDate,
        ]);
        $resDate->assertRedirect(route('customer.booking.time', $this->tenantA->slug));

        // Step D: Customer selects time slot
        $resTime = $this->withSession([
            'booking' => [
                'tenant_id' => $this->tenantA->id,
                'service_id' => $service->id,
                'tanggal' => $bookingDate,
            ],
        ])->post('/' . $this->tenantA->slug . '/booking/select-time', [
            'jam' => '14:00',
            'schedule_id' => $schedule->id,
        ]);
        $resTime->assertRedirect(route('customer.booking.checkout', $this->tenantA->slug));

        // Step E: Customer completes checkout
        $resCheckout = $this->withSession([
            'booking' => [
                'tenant_id' => $this->tenantA->id,
                'service_id' => $service->id,
                'tanggal' => $bookingDate,
                'jam' => '14:00',
            ],
        ])->post('/' . $this->tenantA->slug . '/booking/checkout', [
            'namapelanggan' => 'Adinda Permata',
            'email' => 'adinda@example.com',
            'nomorhp' => '08123456789',
            'catatan' => 'Mohon sediakan aromatherapy lavender',
        ]);

        // Asserts customer booking was successfully recorded and ties everything together
        $booking = DB::table('bookings')->where('idschedule', $schedule->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals($this->tenantA->id, $booking->idtenant);
        $this->assertEquals($service->id, $booking->idlayanan);
        $this->assertEquals('Adinda Permata', $booking->namapelanggan);
        $this->assertEquals('adinda@example.com', $booking->email);
        $this->assertEquals('14:00:00', $booking->jam);
    }
}

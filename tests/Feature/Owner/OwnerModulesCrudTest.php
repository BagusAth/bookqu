<?php

namespace Tests\Feature\Owner;

use App\Models\AdditionalItem;
use App\Models\Asset;
use App\Models\Booking;
use App\Models\Category;
use App\Models\CustomerNote;
use App\Models\Resource;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerModulesCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;
    protected Tenant $tenantA;
    protected User $userB;
    protected Tenant $tenantB;
    protected Service $serviceA;

    protected function setUp(): void
    {
        parent::setUp();

        // Owner A
        $this->userA = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);
        $this->tenantA = Tenant::create([
            'iduser' => $this->userA->id,
            'namabisnis' => 'Studio Foto A',
            'jenisbisnis' => 'Fotografi',
            'slug' => 'studio-a',
            'alamat' => 'Jalan A',
            'nomorhp' => '0811111111',
            'saldo_platform' => 500000,
        ]);
        $this->serviceA = Service::create([
            'idtenant' => $this->tenantA->id,
            'namalayanan' => 'Foto Wisuda A',
            'harga' => 200000,
            'durasi' => 60,
            'is_active' => true,
        ]);

        // Owner B (for tenant isolation tests)
        $this->userB = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);
        $this->tenantB = Tenant::create([
            'iduser' => $this->userB->id,
            'namabisnis' => 'Barbershop B',
            'jenisbisnis' => 'Barbershop',
            'slug' => 'barber-b',
            'alamat' => 'Jalan B',
            'nomorhp' => '0822222222',
            'saldo_platform' => 100000,
        ]);
    }

    // ──────────────────────────────────────────
    // 1. CATEGORIES TEST
    // ──────────────────────────────────────────
    public function test_owner_can_crud_categories(): void
    {
        // View page
        $res = $this->actingAs($this->userA)->get(route('owner.categories'));
        $res->assertStatus(200);

        // Create
        $res = $this->actingAs($this->userA)->post(route('owner.categories.store'), [
            'name' => 'Graduation',
            'description' => 'Foto kelulusan dan wisuda',
            'color' => 'indigo',
            'is_active' => 1,
        ]);
        $res->assertRedirect(route('owner.categories'));
        $this->assertDatabaseHas('categories', [
            'idtenant' => $this->tenantA->id,
            'name' => 'Graduation',
        ]);

        $category = Category::withoutGlobalScopes()->where('idtenant', $this->tenantA->id)->first();
        $this->assertNotNull($category);

        // Toggle Status
        $res = $this->actingAs($this->userA)->post(route('owner.categories.toggle', $category->id));
        $res->assertRedirect(route('owner.categories'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);

        // Update
        $res = $this->actingAs($this->userA)->put(route('owner.categories.update', $category->id), [
            'name' => 'Graduation VIP',
            'description' => 'Updated deskripsi',
            'color' => 'emerald',
            'is_active' => 1,
        ]);
        $res->assertRedirect(route('owner.categories'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Graduation VIP',
            'is_active' => true,
        ]);

        // Delete
        $res = $this->actingAs($this->userA)->delete(route('owner.categories.destroy', $category->id));
        $res->assertRedirect(route('owner.categories'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_tenant_isolation(): void
    {
        $categoryB = Category::create([
            'idtenant' => $this->tenantB->id,
            'name' => 'Haircut B',
            'is_active' => true,
        ]);

        // Owner A cannot update Owner B's category
        $res = $this->actingAs($this->userA)->put(route('owner.categories.update', $categoryB->id), [
            'name' => 'Hacked Name',
            'is_active' => 1,
        ]);
        $res->assertStatus(404);

        // Owner A cannot delete Owner B's category
        $res = $this->actingAs($this->userA)->delete(route('owner.categories.destroy', $categoryB->id));
        $res->assertStatus(404);

        $this->assertDatabaseHas('categories', ['id' => $categoryB->id, 'name' => 'Haircut B']);
    }

    // ──────────────────────────────────────────
    // 2. STAFF & RESOURCES TEST
    // ──────────────────────────────────────────
    public function test_owner_can_crud_staff_and_resources(): void
    {
        // View page
        $res = $this->actingAs($this->userA)->get(route('owner.staff-resources'));
        $res->assertStatus(200);

        // Create Staff
        $res = $this->actingAs($this->userA)->post(route('owner.staff.store'), [
            'name' => 'Andi Fotografer',
            'role' => 'Lead Photographer',
            'phone' => '081299998888',
            'service_ids' => [$this->serviceA->id],
        ]);
        $res->assertRedirect();
        $this->assertDatabaseHas('staff', [
            'idtenant' => $this->tenantA->id,
            'name' => 'Andi Fotografer',
        ]);
        
        $staff = Staff::withoutGlobalScopes()->where('idtenant', $this->tenantA->id)->first();
        $this->assertNotNull($staff);
        $this->assertDatabaseHas('service_staff', [
            'idstaff' => $staff->id,
            'idservice' => $this->serviceA->id,
        ]);

        // Create Resource
        $res = $this->actingAs($this->userA)->post(route('owner.resources.store'), [
            'name' => 'Studio Room 1',
            'type' => 'Studio Ruangan',
            'capacity' => 10,
            'service_ids' => [$this->serviceA->id],
        ]);
        $res->assertRedirect();
        $this->assertDatabaseHas('resources', [
            'idtenant' => $this->tenantA->id,
            'name' => 'Studio Room 1',
        ]);
        
        $resource = Resource::withoutGlobalScopes()->where('idtenant', $this->tenantA->id)->first();
        $this->assertNotNull($resource);
        $this->assertDatabaseHas('service_resource', [
            'idresource' => $resource->id,
            'idservice' => $this->serviceA->id,
        ]);

        // Delete Staff & Resource
        $this->actingAs($this->userA)->delete(route('owner.staff.destroy', $staff->id));
        $this->assertDatabaseMissing('staff', ['id' => $staff->id]);

        $this->actingAs($this->userA)->delete(route('owner.resources.destroy', $resource->id));
        $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
    }

    public function test_staff_and_resource_tenant_isolation(): void
    {
        $staffB = Staff::create([
            'idtenant' => $this->tenantB->id,
            'name' => 'Barber Budi',
            'role' => 'Barber',
        ]);

        // Owner A cannot update or delete Owner B's staff
        $res = $this->actingAs($this->userA)->put(route('owner.staff.update', $staffB->id), [
            'name' => 'Hacked Staff',
            'role' => 'Hacked',
            'is_active' => 1,
        ]);
        $res->assertStatus(404);

        $res = $this->actingAs($this->userA)->delete(route('owner.staff.destroy', $staffB->id));
        $res->assertStatus(404);
    }

    // ──────────────────────────────────────────
    // 3. ADDITIONAL ITEMS TEST
    // ──────────────────────────────────────────
    public function test_owner_can_crud_additional_items(): void
    {
        $res = $this->actingAs($this->userA)->get(route('owner.additional-items'));
        $res->assertStatus(200);

        $res = $this->actingAs($this->userA)->post(route('owner.additional-items.store'), [
            'name' => 'Cetak Foto 10R',
            'price' => 35000,
            'stock' => 50,
            'service_ids' => [$this->serviceA->id],
        ]);
        $res->assertRedirect(route('owner.additional-items'));
        $this->assertDatabaseHas('additional_items', [
            'idtenant' => $this->tenantA->id,
            'name' => 'Cetak Foto 10R',
            'price' => 35000,
        ]);

        $item = AdditionalItem::withoutGlobalScopes()->where('idtenant', $this->tenantA->id)->first();
        $this->assertNotNull($item);
        $this->assertDatabaseHas('additional_item_service', [
            'idadditional_item' => $item->id,
            'idservice' => $this->serviceA->id,
        ]);

        // Delete
        $res = $this->actingAs($this->userA)->delete(route('owner.additional-items.destroy', $item->id));
        $res->assertRedirect(route('owner.additional-items'));
        $this->assertDatabaseMissing('additional_items', ['id' => $item->id]);
    }

    // ──────────────────────────────────────────
    // 4. VOUCHERS TEST
    // ──────────────────────────────────────────
    public function test_owner_can_crud_vouchers(): void
    {
        $res = $this->actingAs($this->userA)->get(route('owner.vouchers'));
        $res->assertStatus(200);

        $res = $this->actingAs($this->userA)->post(route('owner.vouchers.store'), [
            'code' => 'PROMO50',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'min_order_amount' => 100000,
            'usage_limit' => 20,
        ]);
        $res->assertRedirect(route('owner.vouchers'));
        $this->assertDatabaseHas('vouchers', [
            'idtenant' => $this->tenantA->id,
            'code' => 'PROMO50',
            'discount_value' => 50,
        ]);

        $voucher = Voucher::withoutGlobalScopes()->where('idtenant', $this->tenantA->id)->first();
        $this->assertNotNull($voucher);

        // Toggle Status
        $res = $this->actingAs($this->userA)->post(route('owner.vouchers.toggle', $voucher->id));
        $res->assertRedirect(route('owner.vouchers'));
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'is_active' => false,
        ]);

        // Delete
        $res = $this->actingAs($this->userA)->delete(route('owner.vouchers.destroy', $voucher->id));
        $res->assertRedirect(route('owner.vouchers'));
        $this->assertDatabaseMissing('vouchers', ['id' => $voucher->id]);
    }

    public function test_voucher_tenant_isolation(): void
    {
        $voucherB = Voucher::create([
            'idtenant' => $this->tenantB->id,
            'code' => 'BARBER10',
            'discount_type' => 'fixed',
            'discount_value' => 10000,
        ]);

        $res = $this->actingAs($this->userA)->delete(route('owner.vouchers.destroy', $voucherB->id));
        $res->assertStatus(404);
        $this->assertDatabaseHas('vouchers', ['id' => $voucherB->id]);
    }

    // ──────────────────────────────────────────
    // 5. REVIEWS & CUSTOMER NOTES TEST
    // ──────────────────────────────────────────
    public function test_owner_can_manage_reviews_and_reply(): void
    {
        $schedule = Schedule::factory()->create([
            'idtenant' => $this->tenantA->id,
            'idlayanan' => $this->serviceA->id,
        ]);

        $booking = Booking::create([
            'idtenant' => $this->tenantA->id,
            'idlayanan' => $this->serviceA->id,
            'idschedule' => $schedule->id,
            'namapelanggan' => 'Siti',
            'email' => 'siti@example.com',
            'nomorhp' => '0812345678',
            'tanggalbooking' => now()->toDateString(),
            'jam' => '10:00',
            'status' => 'completed',
        ]);

        $review = Review::create([
            'idtenant' => $this->tenantA->id,
            'idbooking' => $booking->id,
            'rating' => 5,
            'review' => 'Pelayanan luar biasa memuaskan!',
        ]);

        $res = $this->actingAs($this->userA)->get(route('owner.reviews'));
        $res->assertStatus(200);

        // Reply
        $res = $this->actingAs($this->userA)->post(route('owner.reviews.reply', $review->id), [
            'balasan' => 'Terima kasih banyak atas kunjungannya!',
        ]);
        $res->assertRedirect(route('owner.reviews'));
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'balasan' => 'Terima kasih banyak atas kunjungannya!',
        ]);

        // Toggle Visibility
        $res = $this->actingAs($this->userA)->post(route('owner.reviews.toggle', $review->id));
        $res->assertRedirect(route('owner.reviews'));
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_hidden' => true,
        ]);
    }

    public function test_owner_can_save_customer_notes(): void
    {
        // Create a booking so the IDOR guard passes (customer_identifier must exist in this tenant's bookings)
        $schedule = Schedule::factory()->create([
            'idtenant'  => $this->tenantA->id,
            'idlayanan' => $this->serviceA->id,
        ]);
        Booking::factory()->create([
            'idtenant'   => $this->tenantA->id,
            'idlayanan'  => $this->serviceA->id,
            'idschedule' => $schedule->id,
            'nomorhp'    => '081234567890',
            'email'      => '',
            'status'     => 'paid',
        ]);

        $res = $this->actingAs($this->userA)->postJson(route('owner.customers.note'), [
            'customer_identifier' => '081234567890',
            'notes'               => 'Customer VIP, minta lighting hangat.',
        ]);
        $res->assertOk()->assertJsonFragment(['success' => true]);
        $this->assertDatabaseHas('customer_notes', [
            'idtenant'            => $this->tenantA->id,
            'customer_identifier' => '081234567890',
            'notes'               => 'Customer VIP, minta lighting hangat.',
        ]);
    }


    // ──────────────────────────────────────────
    // 6. ASSETS & APPEARANCE TEST
    // ──────────────────────────────────────────
    public function test_owner_can_upload_and_delete_assets(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('banner.jpg', 100, 'image/jpeg');

        $res = $this->actingAs($this->userA)->post(route('owner.settings.assets.store'), [
            'file' => $file,
            'title' => 'Banner Promosi',
            'category' => 'cover',
        ]);
        $res->assertRedirect(route('owner.settings.assets'));

        $asset = Asset::withoutGlobalScopes()->where('idtenant', $this->tenantA->id)->first();
        $this->assertNotNull($asset);
        $this->assertEquals('Banner Promosi', $asset->title);
        $this->assertEquals('cover', $asset->category);
        Storage::disk('public')->assertExists($asset->file_path);

        // Delete
        $res = $this->actingAs($this->userA)->delete(route('owner.settings.assets.destroy', $asset->id));
        $res->assertRedirect(route('owner.settings.assets'));
        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
        Storage::disk('public')->assertMissing($asset->file_path);
    }

    public function test_owner_can_update_appearance_settings(): void
    {
        $res = $this->actingAs($this->userA)->post(route('owner.settings.appearance.update'), [
            'theme_color' => '#10B981',
            'button_style' => 'pill',
            'font_family' => 'Outfit',
            'card_style' => 'bordered',
            'deskripsi' => 'Studio foto modern profesional.',
        ]);

        $res->assertRedirect(route('owner.settings.appearance'));
        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenantA->id,
            'theme_color' => '#10B981',
            'button_style' => 'pill',
            'font_family' => 'Outfit',
            'card_style' => 'bordered',
        ]);
    }
}

<?php

namespace Tests\Feature\Owner;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\AdditionalItem;
use App\Models\Asset;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;
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
        app(CreateTrialSubscription::class)->execute($this->tenantA)->forceFill(['created_at' => now()->subMinute()])->save();

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
    public function test_retired_staff_and_resource_routes_are_gone(): void
    {
        $this->actingAs($this->userA)->get('/owner/resources')->assertNotFound();
        $this->delete('/owner/resources/1')->assertNotFound();
        $this->get('/owner/staff-resources')->assertRedirect(route('owner.settings', ['tab' => 'tim']));
        foreach (['payments', 'payment-setting'] as $oldPage) {
            $this->get('/owner/settings/'.$oldPage)->assertStatus(301)->assertRedirect(route('owner.payments'));
            $this->get('/owner/settings/'.$oldPage.'?tab=saldo')
                ->assertStatus(301)->assertRedirect(route('owner.payments', ['tab' => 'saldo']));
        }
        $this->post('/owner/staff', ['name' => 'Nama lama'])->assertNotFound();
        $this->delete('/owner/staff/1')->assertNotFound();
    }

    // ──────────────────────────────────────────
    // 3. ADDITIONAL ITEMS TEST
    // ──────────────────────────────────────────
    public function test_owner_can_crud_additional_items(): void
    {
        // Staged module under test is released explicitly (ADR-0011)
        config(['modules.additional-items' => 'released']);

        $res = $this->actingAs($this->userA)->get(route('owner.services', ['tab' => 'add-ons']));
        $res->assertStatus(200);

        $res = $this->actingAs($this->userA)->post(route('owner.services.add-ons.store'), [
            'name' => 'Cetak Foto 10R',
            'price' => 35000,
            'stock' => 50,
            'service_ids' => [$this->serviceA->id],
        ]);
        $res->assertRedirect(route('owner.services', ['tab' => 'add-ons']));
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
        $res = $this->actingAs($this->userA)->delete(route('owner.services.add-ons.destroy', $item->id));
        $res->assertRedirect(route('owner.services', ['tab' => 'add-ons']));
        $this->assertDatabaseMissing('additional_items', ['id' => $item->id]);
    }

    public function test_add_on_sold_count_uses_only_paid_booking_quantities_for_the_current_tenant(): void
    {
        $item = AdditionalItem::create([
            'idtenant' => $this->tenantA->id, 'name' => 'Cetak Foto', 'price' => 35000,
            'is_unlimited' => true, 'is_active' => true,
        ]);
        foreach ([[$this->tenantA, 'sukses', 3], [$this->tenantA, 'pending', 2], [$this->tenantB, 'sukses', 4]] as [$tenant, $status, $quantity]) {
            Payment::factory()->create([
                'idtenant' => $tenant->id, 'tipe' => 'booking', 'status' => $status,
                'price_breakdown' => ['add_ons' => [['id' => $item->id, 'quantity' => $quantity, 'unlimited' => true]]],
            ]);
        }
        $this->actingAs($this->userA)->get(route('owner.services', ['tab' => 'add-ons']))
            ->assertOk()->assertSeeText('Terjual 3');
    }

    // ──────────────────────────────────────────
    // 4. VOUCHERS TEST
    // ──────────────────────────────────────────
    public function test_owner_can_crud_vouchers(): void
    {
        // Staged module under test is released explicitly (ADR-0011)
        config(['modules.vouchers' => 'released']);

        $res = $this->actingAs($this->userA)->get(route('owner.promos'));
        $res->assertStatus(200);

        $res = $this->actingAs($this->userA)->post(route('owner.promos.store'), [
            'code' => 'PROMO50',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'min_order_amount' => 100000,
            'usage_limit' => 20,
        ]);
        $res->assertRedirect(route('owner.promos'));
        $this->assertDatabaseHas('vouchers', [
            'idtenant' => $this->tenantA->id,
            'code' => 'PROMO50',
            'discount_value' => 50,
        ]);

        $voucher = Voucher::withoutGlobalScopes()->where('idtenant', $this->tenantA->id)->first();
        $this->assertNotNull($voucher);

        // Toggle Status
        $res = $this->actingAs($this->userA)->post(route('owner.promos.toggle', $voucher->id));
        $res->assertRedirect(route('owner.promos'));
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'is_active' => false,
        ]);

        // Delete
        $res = $this->actingAs($this->userA)->delete(route('owner.promos.destroy', $voucher->id));
        $res->assertRedirect(route('owner.promos'));
        $this->assertDatabaseMissing('vouchers', ['id' => $voucher->id]);
    }

    public function test_promo_update_with_legacy_form_fields_keeps_discount_cap_and_services(): void
    {
        $otherService = Service::factory()->create(['idtenant' => $this->tenantA->id]);
        $serviceIds = $this->serviceA->id.','.$otherService->id;
        $voucher = Voucher::create([
            'idtenant' => $this->tenantA->id,
            'code' => 'REBATE',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'max_discount' => 20000,
            'applicable_services' => $serviceIds,
        ]);

        $this->actingAs($this->userA)->put(route('owner.promos.update', $voucher), [
            'code' => 'REBATE',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => 1,
        ])->assertRedirect(route('owner.promos'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'discount_value' => 20,
            'max_discount' => 20000,
            'applicable_services' => $serviceIds,
        ]);
    }

    public function test_promo_forms_save_and_clear_discount_cap_and_service_selection(): void
    {
        $payload = [
            'code' => 'REBATE',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'max_discount_amount' => 20000,
            'applicable_services' => [$this->serviceA->id],
            'is_active' => 1,
        ];

        $this->actingAs($this->userA)->post(route('owner.promos.store'), $payload)
            ->assertRedirect(route('owner.promos'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vouchers', [
            'code' => 'REBATE',
            'max_discount' => 20000,
            'applicable_services' => (string) $this->serviceA->id,
        ]);

        $voucher = Voucher::withoutGlobalScopes()->where('idtenant', $this->tenantA->id)->where('code', 'REBATE')->firstOrFail();
        $this->put(route('owner.promos.update', $voucher), array_replace($payload, [
            'max_discount_amount' => '',
            'applicable_services' => '',
        ]))->assertRedirect(route('owner.promos'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'max_discount' => null,
            'applicable_services' => 'all',
        ]);
    }

    public function test_promo_rejects_another_tenants_service_and_percentage_above_one_hundred(): void
    {
        $otherService = Service::factory()->create(['idtenant' => $this->tenantB->id]);
        $this->actingAs($this->userA)->post(route('owner.promos.store'), [
            'code' => 'REBATE',
            'discount_type' => 'percentage',
            'discount_value' => 101,
            'applicable_services' => [$otherService->id],
        ])->assertSessionHasErrors(['discount_value', 'applicable_services.0']);

        $this->assertDatabaseMissing('vouchers', ['code' => 'REBATE']);
    }

    public function test_promo_delete_confirmation_escapes_a_legacy_code_with_a_quote(): void
    {
        $voucher = Voucher::create([
            'idtenant' => $this->tenantA->id,
            'code' => "REBATE'S",
            'discount_type' => 'percentage',
            'discount_value' => 15,
        ]);

        $this->actingAs($this->userA)->get(route('owner.promos'))
            ->assertOk()
            ->assertSee("Yakin ingin menghapus promo ' + ".Js::from($voucher->code), false);
    }

    public function test_voucher_tenant_isolation(): void
    {
        $voucherB = Voucher::create([
            'idtenant' => $this->tenantB->id,
            'code' => 'BARBER10',
            'discount_type' => 'fixed',
            'discount_value' => 10000,
        ]);

        $res = $this->actingAs($this->userA)->delete(route('owner.promos.destroy', $voucherB->id));
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
            'idtenant' => $this->tenantA->id,
            'idlayanan' => $this->serviceA->id,
        ]);
        Booking::factory()->create([
            'idtenant' => $this->tenantA->id,
            'idlayanan' => $this->serviceA->id,
            'idschedule' => $schedule->id,
            'nomorhp' => '081234567890',
            'email' => '',
            'status' => 'paid',
        ]);

        $res = $this->actingAs($this->userA)->postJson(route('owner.customers.note'), [
            'customer_identifier' => '081234567890',
            'notes' => 'Customer VIP, minta lighting hangat.',
        ]);
        $res->assertOk()->assertJsonFragment(['success' => true]);
        $this->assertDatabaseHas('customer_notes', [
            'idtenant' => $this->tenantA->id,
            'customer_identifier' => '081234567890',
            'notes' => 'Customer VIP, minta lighting hangat.',
        ]);
    }

    // ──────────────────────────────────────────
    // 6. ASSETS & APPEARANCE TEST
    // ──────────────────────────────────────────
    public function test_owner_can_upload_and_delete_assets(): void
    {
        // Staged module under test is released explicitly (ADR-0011)
        // BQ-142: the gallery lives in Halaman Booking (owner-portal.md §8.9).
        config(['modules.booking-page' => 'released']);

        Storage::fake('public');

        $file = UploadedFile::fake()->create('banner.jpg', 100, 'image/jpeg');

        $res = $this->actingAs($this->userA)->post(route('owner.booking-page.media.store'), [
            'file' => $file,
            'title' => 'Banner Promosi',
            'category' => 'cover',
        ]);
        $res->assertRedirect(route('owner.booking-page', ['section' => 'galeri']));

        $asset = Asset::withoutGlobalScopes()->where('idtenant', $this->tenantA->id)->first();
        $this->assertNotNull($asset);
        $this->assertEquals('Banner Promosi', $asset->title);
        $this->assertEquals('cover', $asset->category);
        Storage::disk('public')->assertExists($asset->file_path);

        // Delete
        $res = $this->actingAs($this->userA)->delete(route('owner.booking-page.media.destroy', $asset->id));
        $res->assertRedirect(route('owner.booking-page', ['section' => 'galeri']));
        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
        Storage::disk('public')->assertMissing($asset->file_path);
    }

    public function test_owner_can_update_appearance_settings(): void
    {
        // Staged module under test is released explicitly (ADR-0011)
        // BQ-142: Tampilan lives in Halaman Booking; card styles are elevated, outline and soft.
        config(['modules.booking-page' => 'released']);

        $res = $this->actingAs($this->userA)->post(route('owner.booking-page.appearance'), [
            'theme_color' => '#10B981',
            'button_style' => 'pill',
            'font_family' => 'Outfit',
            'card_style' => 'outline',
            'deskripsi' => 'Studio foto modern profesional.',
        ]);

        $res->assertRedirect(route('owner.booking-page', ['section' => 'tampilan']));
        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenantA->id,
            'theme_color' => '#10B981',
            'button_style' => 'pill',
            'font_family' => 'Outfit',
            'card_style' => 'outline',
        ]);
    }
}

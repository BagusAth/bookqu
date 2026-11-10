<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Resource;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * BQ-132 (owner-portal.md §8.6): the Layanan page with tabs Layanan and Kategori.
 */
class LayananPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-09 09:00:00', 'Asia/Jakarta'));
        Storage::fake('public');

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id, 'slug' => 'studio-layanan']);
        $this->plan('small', 5);
    }

    private function plan(string $name, int $maxServices): void
    {
        Subscription::query()->delete();
        $plan = Plan::updateOrCreate(['namapaket' => $name], ['hargabulanan' => 100000, 'maxlayanan' => $maxServices, 'maxbooking' => 300, 'isunlimited' => false]);
        Subscription::create(['idtenant' => $this->tenant->id, 'idplan' => $plan->id, 'status' => 'active', 'langganan_mulai' => now()->subDay(), 'langganan_berakhir' => now()->addMonth()]);
    }

    private function service(array $attributes = []): Service
    {
        return Service::factory()->create(array_merge(['idtenant' => $this->tenant->id, 'harga' => 100000, 'durasi' => 60, 'is_active' => true], $attributes));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'namalayanan' => 'Foto Keluarga',
            'harga' => 250000,
            'satuan_harga' => 'sesi',
            'durasi' => 90,
            'satuan_durasi' => 'menit',
            'deskripsi' => 'Sesi foto keluarga di studio.',
            'is_popular' => 1,
            'is_active' => 1,
        ], $overrides);
    }

    public function test_page_has_the_layanan_and_kategori_tabs_and_a_usage_meter(): void
    {
        $this->service(['namalayanan' => 'Pas Foto']);

        $this->actingAs($this->owner)->get(route('owner.services'))
            ->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('tab=kategori', false)
            ->assertSee('1 dari 5 layanan')
            ->assertSee('Pas Foto')
            ->assertSee('id="service-form-dialog"', false);
    }

    public function test_new_service_fields_are_saved(): void
    {
        $category = Category::create(['idtenant' => $this->tenant->id, 'name' => 'Keluarga']);

        $this->actingAs($this->owner)->post(route('owner.services.store'), $this->payload(['idcategory' => $category->id, 'satuan_harga' => 'orang', 'satuan_durasi' => 'jam']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('owner.services'));

        $this->assertDatabaseHas('services', [
            'idtenant' => $this->tenant->id, 'namalayanan' => 'Foto Keluarga', 'idcategory' => $category->id,
            'satuan_harga' => 'orang', 'satuan_durasi' => 'jam', 'is_popular' => 1, 'is_active' => 1,
        ]);
    }

    public function test_ac_lyn_01_a_service_above_the_plan_limit_is_refused_and_the_upgrade_prompt_shows(): void
    {
        $this->plan('small', 2);
        $this->service();
        $this->service();

        $this->actingAs($this->owner)->post(route('owner.services.store'), $this->payload())
            ->assertSessionHasErrors('namalayanan');
        $this->assertSame(2, Service::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->count());

        $this->get(route('owner.services'))
            ->assertOk()
            ->assertSee('2 dari 2 layanan')
            ->assertSee('Lihat paket')
            ->assertDontSee('id="service-create-button"', false);
    }

    public function test_ac_lyn_02_a_service_with_any_booking_can_only_be_deactivated(): void
    {
        $used = $this->service(['namalayanan' => 'Pernah Dipesan']);
        $slot = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $used->id]);
        Booking::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $used->id, 'idschedule' => $slot->id, 'status' => 'cancelled']);
        $unused = $this->service(['namalayanan' => 'Belum Dipesan']);

        $html = (string) $this->actingAs($this->owner)->get(route('owner.services'))->getContent();
        $this->assertStringContainsString('data-service-delete="'.$unused->id.'"', $html);
        $this->assertStringNotContainsString('data-service-delete="'.$used->id.'"', $html);
        $this->assertStringContainsString('data-service-deactivate="'.$used->id.'"', $html);

        $this->delete(route('owner.services.destroy', $used->id))->assertSessionHasErrors('error');
        $this->assertDatabaseHas('services', ['id' => $used->id]);

        $this->delete(route('owner.services.destroy', $unused->id))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('services', ['id' => $unused->id]);
    }

    public function test_ac_lyn_03_changing_price_or_duration_leaves_existing_bookings_unchanged(): void
    {
        $service = $this->service(['harga' => 100000, 'durasi' => 60]);
        $slot = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $service->id, 'tanggal' => '2026-11-12', 'jam_mulai' => '10:00:00', 'jam_selesai' => '10:59:00']);
        $payment = Payment::factory()->create(['idtenant' => $this->tenant->id, 'jumlah' => 100000, 'status' => 'sukses']);
        $booking = Booking::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $service->id, 'idschedule' => $slot->id, 'idpayment' => $payment->id, 'tanggalbooking' => '2026-11-12', 'jam' => '10:00:00', 'status' => 'paid']);

        $this->actingAs($this->owner)->put(route('owner.services.update', $service->id), $this->payload(['namalayanan' => $service->namalayanan, 'harga' => 175000, 'durasi' => 90]))
            ->assertSessionHasNoErrors();

        $this->assertSame('175000.00', (string) $service->fresh()->harga);
        $this->assertSame('100000.00', (string) $payment->fresh()->jumlah);
        $this->assertSame('10:00:00', $booking->fresh()->jam);
        $this->assertSame('10:59:00', $slot->fresh()->jam_selesai);
        $this->get(route('owner.services'))->assertSee('hanya berlaku untuk booking baru');
    }

    public function test_the_service_form_sends_back_the_unit_links_it_does_not_show(): void
    {
        $service = $this->service();
        $resource = Resource::create(['idtenant' => $this->tenant->id, 'name' => 'Studio A', 'type' => 'Ruang', 'is_active' => true]);
        $service->resources()->attach($resource->id);

        // Units are edited on the Unit page; a save without `resource_ids` detaches them (BQ-045),
        // so the Layanan form carries the current links as hidden fields.
        $this->actingAs($this->owner)->get(route('owner.services'))
            ->assertSee('name="resource_ids[]"', false)
            ->assertSee('\u0022resource_ids\u0022:['.$resource->id.']', false);

        $this->put(route('owner.services.update', $service->id), $this->payload(['namalayanan' => 'Nama Baru', 'resource_ids' => [$resource->id]]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('service_resource', ['idservice' => $service->id, 'idresource' => $resource->id]);
    }

    public function test_old_programs_page_redirects_permanently_to_layanan(): void
    {
        $this->actingAs($this->owner)->get('/owner/programs?katakunci=foto')
            ->assertStatus(301)
            ->assertRedirect(route('owner.services', ['katakunci' => 'foto']));
    }

    public function test_kategori_tab_lists_categories_and_links_to_their_services(): void
    {
        $category = Category::create(['idtenant' => $this->tenant->id, 'name' => 'Wisuda', 'color' => 'emerald']);
        $this->service(['namalayanan' => 'Foto Wisuda', 'idcategory' => $category->id]);
        $this->service(['namalayanan' => 'Foto Lain']);

        $this->actingAs($this->owner)->get(route('owner.categories'))
            ->assertOk()
            ->assertSee('Wisuda')
            ->assertSee('1 layanan')
            ->assertSee(route('owner.services', ['tab' => 'layanan', 'category' => $category->id]));

        $this->get(route('owner.services', ['tab' => 'layanan', 'category' => $category->id]))
            ->assertOk()
            ->assertSee('Foto Wisuda')
            ->assertDontSee('Foto Lain');
    }

    public function test_category_image_and_display_order_are_saved(): void
    {
        $this->actingAs($this->owner)->post(route('owner.categories.store'), [
            'name' => 'Prewedding',
            'description' => 'Foto sebelum menikah',
            'color' => 'rose',
            'sort_order' => 3,
            'image' => UploadedFile::fake()->image('prewed.jpg', 400, 400),
        ])->assertSessionHasNoErrors();

        $category = Category::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->where('name', 'Prewedding')->firstOrFail();
        $this->assertSame(3, (int) $category->sort_order);
        $this->assertNotNull($category->image_url);
        Storage::disk('public')->assertExists($category->image_url);
    }

    public function test_a_category_with_services_cannot_be_deleted(): void
    {
        $category = Category::create(['idtenant' => $this->tenant->id, 'name' => 'Terpakai']);
        $this->service(['idcategory' => $category->id]);
        $empty = Category::create(['idtenant' => $this->tenant->id, 'name' => 'Kosong']);

        $this->actingAs($this->owner)->delete(route('owner.categories.destroy', $category->id))->assertSessionHasErrors('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);

        $this->delete(route('owner.categories.destroy', $empty->id))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('categories', ['id' => $empty->id]);
    }

    public function test_the_storefront_shows_categories_in_their_display_order(): void
    {
        $this->plan('pro', 0);
        $later = Category::create(['idtenant' => $this->tenant->id, 'name' => 'Zeta Muncul Pertama', 'sort_order' => 1]);
        $first = Category::create(['idtenant' => $this->tenant->id, 'name' => 'Alpha Muncul Kedua', 'sort_order' => 2]);
        $this->service(['namalayanan' => 'Layanan A', 'idcategory' => $first->id]);
        $this->service(['namalayanan' => 'Layanan Z', 'idcategory' => $later->id]);

        $html = (string) $this->get('/'.$this->tenant->slug)->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Alpha Muncul Kedua'), strpos($html, 'Zeta Muncul Pertama'));
    }

    public function test_kasir_cannot_open_the_layanan_page(): void
    {
        $kasir = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $kasir->id, 'role' => 'kasir', 'accepted_at' => now()]);

        $this->actingAs($kasir)->get(route('owner.services'))->assertForbidden();
    }
}

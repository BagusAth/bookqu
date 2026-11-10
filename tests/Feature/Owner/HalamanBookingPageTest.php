<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Actions\Tenant\GetStorefrontTenant;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use App\Support\CustomerBookingRoutes;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * BQ-142 (owner-portal.md §8.9): Halaman Booking with Link & QR, Materi promosi, Tampilan and
 * Galeri media. The page is behind the hidden `booking-page` module until the Tech Lead releases it
 * (ADR-0021), so these tests release it explicitly.
 */
class HalamanBookingPageTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Jakarta'));
        config(['modules.booking-page' => 'released']);
        Storage::fake('public');

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id, 'slug' => 'studio-cahaya', 'namabisnis' => 'Studio Cahaya']);
        app(CreateTrialSubscription::class)->execute($this->tenant);
        Service::factory()->create(['idtenant' => $this->tenant->id, 'is_active' => true]);
    }

    private function asset(?Tenant $tenant = null, string $category = 'logo'): Asset
    {
        $tenant ??= $this->tenant;
        $path = "tenants/{$tenant->id}/assets/".$category.'-'.uniqid().'.png';
        Storage::disk('public')->put($path, 'img');

        return Asset::withoutGlobalScopes()->create([
            'idtenant' => $tenant->id, 'title' => ucfirst($category), 'category' => $category, 'file_path' => $path, 'mime_type' => 'image/png', 'file_size' => 3,
        ]);
    }

    private function link(): string
    {
        return CustomerBookingRoutes::url('customer.booking.program', $this->tenant->fresh()->slug);
    }

    private function member(string $role): User
    {
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $user->id, 'role' => $role, 'accepted_at' => now()]);

        return $user;
    }

    public function test_page_has_link_and_qr_promotion_materials_appearance_and_gallery(): void
    {
        $this->actingAs($this->owner)->get(route('owner.booking-page'))
            ->assertOk()
            ->assertSee('Link &amp; QR', false)
            ->assertSee('Materi promosi')
            ->assertSee('Tampilan')
            ->assertSee('Galeri media')
            ->assertSee($this->link())
            ->assertSee('https://wa.me/?text=', false);

        $this->get(route('owner.booking-page', ['section' => 'promosi']))
            ->assertSee('Salin teks bio Instagram')
            ->assertSee('Salin pesan WhatsApp')
            ->assertSee($this->link());

        $this->actingAs($this->member('manajer'))->get(route('owner.booking-page'))->assertOk();
        $this->actingAs($this->member('kasir'))->get(route('owner.booking-page'))->assertForbidden();
    }

    public function test_the_page_stays_hidden_until_the_module_is_released(): void
    {
        config(['modules.booking-page' => 'hidden']);

        $this->actingAs($this->owner)->get(route('owner.booking-page'))->assertNotFound();
    }

    public function test_ac_hbk_02_the_qr_code_and_poster_encode_the_current_booking_link(): void
    {
        $expected = (new Writer(new ImageRenderer(new RendererStyle(320, 2), new SvgImageBackEnd)))->writeString($this->link());

        $this->actingAs($this->owner)->get(route('owner.booking-page.qr'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertDownload('qr-studio-cahaya.svg');
        $this->assertSame($expected, $this->get(route('owner.booking-page.qr'))->streamedContent());

        $this->tenant->update(['slug' => 'studio-cahaya-baru']);
        $renamed = (new Writer(new ImageRenderer(new RendererStyle(320, 2), new SvgImageBackEnd)))->writeString($this->link());
        $this->assertSame($renamed, $this->get(route('owner.booking-page.qr'))->streamedContent());

        $this->get(route('owner.booking-page.poster'))
            ->assertOk()
            ->assertSee('Studio Cahaya')
            ->assertSee($this->link())
            ->assertSee($renamed, false);
    }

    public function test_ac_hbk_01_a_change_saved_in_tampilan_appears_on_the_public_booking_page(): void
    {
        $logo = $this->asset(category: 'logo');
        $cover = $this->asset(category: 'cover');

        $this->actingAs($this->owner)->post(route('owner.booking-page.appearance'), [
            'theme_color' => '#0F766E',
            'button_style' => 'pill',
            'card_style' => 'outline',
            'font_family' => 'Poppins',
            'deskripsi' => 'Studio foto keluarga di Bandung.',
            'logo_asset_id' => $logo->id,
            'cover_asset_id' => $cover->id,
        ])->assertRedirect(route('owner.booking-page', ['section' => 'tampilan']))->assertSessionHasNoErrors();

        auth()->logout();
        $this->get('/studio-cahaya')
            ->assertOk()
            ->assertSee('--bq-primary: #0F766E', false)
            ->assertSee('family=Poppins', false)
            ->assertSee('9999px', false)
            ->assertSee(Storage::url($logo->file_path), false)
            ->assertSee(Storage::url($cover->file_path), false)
            ->assertSee('Studio foto keluarga di Bandung.');
    }

    public function test_appearance_values_are_validated_so_nothing_is_injected_into_the_storefront(): void
    {
        $this->actingAs($this->owner)->post(route('owner.booking-page.appearance'), [
            'theme_color' => 'red;} body{display:none',
            'button_style' => 'blob',
            'card_style' => 'glass',
            'font_family' => 'Comic"; } *{',
        ])->assertSessionHasErrors(['theme_color', 'button_style', 'card_style', 'font_family']);

        $this->tenant->forceFill(['theme_color' => 'red;}body{x', 'font_family' => 'Bad"}', 'button_style' => 'rounded-full', 'card_style' => 'bordered'])->save();
        app(GetStorefrontTenant::class)->forget((int) $this->tenant->id);
        auth()->logout();
        $html = (string) $this->get('/studio-cahaya')->assertOk()->getContent();
        $this->assertStringNotContainsString('red;}body{x', $html);
        $this->assertStringNotContainsString('Bad"}', $html);
        $this->assertStringContainsString('9999px', $html);
    }

    public function test_ac_hbk_05_images_are_stored_per_business_and_no_business_picks_another_businesss_image(): void
    {
        $this->actingAs($this->owner)->post(route('owner.booking-page.media.store'), [
            'file' => UploadedFile::fake()->image('logo.png', 200, 200),
            'title' => 'Logo baru',
            'category' => 'logo',
        ])->assertSessionHasNoErrors();
        $mine = Asset::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->firstOrFail();
        $this->assertStringStartsWith("tenants/{$this->tenant->id}/assets/", $mine->file_path);
        Storage::disk('public')->assertExists($mine->file_path);

        $other = Tenant::factory()->create();
        $foreign = $this->asset($other, 'logo');

        $this->post(route('owner.booking-page.appearance'), ['logo_asset_id' => $foreign->id])->assertSessionHasErrors('logo_asset_id');
        $this->assertNull($this->tenant->fresh()->logo_path);
        $this->delete(route('owner.booking-page.media.destroy', $foreign->id))->assertNotFound();
        $this->post(route('owner.services.store'), [
            'namalayanan' => 'Foto', 'harga' => 1000, 'durasi' => 60, 'image_asset_id' => $foreign->id,
        ])->assertSessionHasErrors('image_asset_id');

        $this->get(route('owner.booking-page', ['section' => 'galeri']))->assertSee('Logo baru')->assertDontSee($foreign->file_path);
    }

    public function test_gallery_upload_is_validated(): void
    {
        $this->actingAs($this->owner)->post(route('owner.booking-page.media.store'), [
            'file' => UploadedFile::fake()->create('besar.png', 10241, 'image/png'),
            'category' => 'banner',
        ])->assertSessionHasErrors(['file', 'category']);

        $this->post(route('owner.booking-page.media.store'), [
            'file' => UploadedFile::fake()->create('script.svg', 2, 'image/svg+xml'),
        ])->assertSessionHasErrors('file');
        $this->assertSame(0, Asset::withoutGlobalScopes()->count());
    }

    public function test_deleting_an_image_in_use_asks_for_confirmation_and_clears_the_references(): void
    {
        $logo = $this->asset(category: 'logo');
        $service = Service::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->firstOrFail();
        $service->update(['image_url' => $logo->file_path]);
        $this->tenant->update(['logo_path' => $logo->file_path]);

        $this->actingAs($this->owner)->delete(route('owner.booking-page.media.destroy', $logo->id))
            ->assertSessionHasErrors('asset');
        $this->assertDatabaseHas('assets', ['id' => $logo->id]);

        $this->delete(route('owner.booking-page.media.destroy', $logo->id), ['confirm' => '1'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('owner.booking-page', ['section' => 'galeri']));
        $this->assertDatabaseMissing('assets', ['id' => $logo->id]);
        $this->assertNull($this->tenant->fresh()->logo_path);
        $this->assertNull($service->fresh()->image_url);
        Storage::disk('public')->assertMissing($logo->file_path);
    }

    public function test_layanan_picks_from_and_uploads_into_the_gallery(): void
    {
        $picked = $this->asset(category: 'service');
        $category = Category::create(['idtenant' => $this->tenant->id, 'name' => 'Umum']);

        $this->actingAs($this->owner)->post(route('owner.services.store'), [
            'namalayanan' => 'Dari galeri', 'harga' => 1000, 'durasi' => 60, 'idcategory' => $category->id, 'image_asset_id' => $picked->id,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', ['namalayanan' => 'Dari galeri', 'image_url' => $picked->file_path]);

        $this->post(route('owner.services.store'), [
            'namalayanan' => 'Unggah baru', 'harga' => 1000, 'durasi' => 60, 'cover_image' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertSessionHasNoErrors();
        $uploaded = Service::withoutGlobalScopes()->where('namalayanan', 'Unggah baru')->firstOrFail();
        $this->assertDatabaseHas('assets', ['idtenant' => $this->tenant->id, 'category' => 'service', 'file_path' => $uploaded->image_url]);

        $this->get(route('owner.services'))->assertSee('name="image_asset_id"', false);
    }

    public function test_old_appearance_and_assets_paths_redirect(): void
    {
        $this->actingAs($this->owner)->get('/owner/settings/appearance')->assertStatus(301)->assertRedirect(route('owner.booking-page', ['section' => 'tampilan']));
        $this->get('/owner/settings/assets')->assertStatus(301)->assertRedirect(route('owner.booking-page', ['section' => 'galeri']));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Support\StatusBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * BQ-129: the shared owner blocks of owner-portal.md §9.
 */
class SharedBuildingBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_badges_use_one_label_and_tone_per_state(): void
    {
        $this->assertSame(['Menunggu bayar', 'warning'], StatusBadge::for('booking', 'pending'));
        $this->assertSame(['Terkonfirmasi', 'info'], StatusBadge::for('booking', 'paid'));
        $this->assertSame(['Selesai', 'success'], StatusBadge::for('booking', 'completed'));
        $this->assertSame(['Dibatalkan', 'neutral'], StatusBadge::for('booking', 'cancelled'));
        $this->assertSame(['Tidak datang', 'danger'], StatusBadge::for('booking', 'no_show'));
        $this->assertSame(['Berhasil', 'success'], StatusBadge::for('payment', 'sukses'));
        $this->assertSame(['Gagal / Dibatalkan', 'danger'], StatusBadge::for('payment', 'gagal'));
        $this->assertSame(['Pending', 'warning'], StatusBadge::for('payout', 'approved'));
        $this->assertSame(['Menunggu keputusan', 'warning'], StatusBadge::for('refund_request', 'open'));
        $this->assertSame(['Diterima 50%', 'success'], StatusBadge::for('refund_request', 'accepted_half'));
        $this->assertSame(['Sudah ditransfer', 'success'], StatusBadge::for('refund', 'processed'));
        $this->assertSame(['expired', 'neutral'], StatusBadge::for('payment', 'expired'));

        $this->blade('<x-owner.status-badge type="booking" status="paid" />')
            ->assertSee('Terkonfirmasi')
            ->assertSee('data-tone="info"', false);
    }

    public function test_page_header_shows_title_purpose_and_one_primary_action(): void
    {
        $this->blade('<x-owner.page-header title="Booking" purpose="Semua reservasi bisnis Anda."><x-slot:action><a href="#">+ Booking</a></x-slot:action></x-owner.page-header>')
            ->assertSee('<h1', false)
            ->assertSee('Booking')
            ->assertSee('Semua reservasi bisnis Anda.')
            ->assertSee('+ Booking');

        // The former prop names keep working for pages not yet migrated.
        $this->blade('<x-owner.page-header judul="Layanan" subjudul="Atur layanan." />')
            ->assertSee('Layanan')
            ->assertSee('Atur layanan.');
    }

    public function test_tabs_keep_the_active_tab_in_the_url(): void
    {
        $this->get('/?tab=harga&week=2');

        $view = $this->blade('<x-owner.tabs :tabs="$tabs" />', ['tabs' => ['jam' => 'Jam Buka', 'harga' => 'Harga', 'libur' => 'Libur & Tutup']]);

        $view->assertSee('role="tablist"', false)
            ->assertSee('aria-selected="true"', false)
            ->assertSee('tab=libur', false)
            ->assertSee('week=2', false);
        $this->assertMatchesRegularExpression('/aria-selected="true"[^>]*>\s*Harga/', (string) $view);
    }

    public function test_tabs_fall_back_to_the_first_tab_for_an_unknown_value(): void
    {
        $this->get('/?tab=bukan-tab');

        $view = (string) $this->blade('<x-owner.tabs :tabs="$tabs" />', ['tabs' => ['jam' => 'Jam Buka', 'harga' => 'Harga']]);

        $this->assertMatchesRegularExpression('/aria-selected="true"[^>]*>\s*Jam Buka/', $view);
    }

    public function test_empty_state_says_what_it_is_how_to_start_and_offers_one_button(): void
    {
        $this->blade('<x-owner.empty-state title="Belum ada layanan" description="Tambahkan layanan pertama Anda." action-label="Tambah layanan" action-href="/owner/programs" />')
            ->assertSee('Belum ada layanan')
            ->assertSee('Tambahkan layanan pertama Anda.')
            ->assertSee('href="/owner/programs"', false)
            ->assertSee('Tambah layanan');
    }

    public function test_upgrade_prompt_points_to_the_plan_page(): void
    {
        $this->blade('<x-owner.upgrade-prompt feature="Promo" plan="Medium" />')
            ->assertSee('Promo')
            ->assertSee('Medium')
            ->assertSee(route('owner.subscription'), false);
    }

    public function test_dialogs_and_drawers_are_accessible(): void
    {
        $dialog = (string) $this->blade('<x-owner.dialog id="tes-dialog" title="Judul Dialog">Isi</x-owner.dialog>');
        $this->assertStringContainsString('role="dialog"', $dialog);
        $this->assertStringContainsString('aria-modal="true"', $dialog);
        $this->assertStringContainsString('aria-labelledby="tes-dialog-title"', $dialog);
        $this->assertStringContainsString('id="tes-dialog-title"', $dialog);
        $this->assertStringContainsString("ownerDialog('tes-dialog')", $dialog);
        $this->assertStringContainsString('aria-label="Tutup"', $dialog);

        $drawer = (string) $this->blade('<x-owner.drawer id="tes-drawer" title="Panel">Isi</x-owner.drawer>');
        $this->assertStringContainsString('role="dialog"', $drawer);
        $this->assertStringContainsString('aria-labelledby="tes-drawer-title"', $drawer);
    }

    public function test_walk_in_form_posts_the_supported_fields_to_the_one_walk_in_action(): void
    {
        $tenant = Tenant::factory()->create();
        $service = Service::factory()->create(['idtenant' => $tenant->id]);
        $slot = Schedule::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id]);

        $html = (string) $this->blade('<x-owner.walkin-form :slots="$slots" />', ['slots' => [['id' => $slot->id, 'label' => 'Sel, 12 Nov · 10:00 – 11:00']]]);

        $this->assertStringContainsString('action="'.route('owner.bookings.walkin').'"', $html);
        foreach (['idschedule', 'namapelanggan', 'nomorhp', 'email', 'catatan', 'metode'] as $field) {
            $this->assertStringContainsString('name="'.$field.'"', $html);
        }
        $this->assertStringContainsString('maxlength="150"', $html);
        $this->assertStringContainsString('maxlength="30"', $html);
        $this->assertStringContainsString('maxlength="500"', $html);
        foreach (['cash', 'transfer', 'manual', 'qris'] as $method) {
            $this->assertStringContainsString('value="'.$method.'"', $html);
        }
        // No controls without a backend (owner-portal.md §3 rule 6).
        $this->assertStringNotContainsString('name="participants"', $html);
        $this->assertStringNotContainsString('name="add_ons[]"', $html);
    }

    public function test_gallery_picker_selects_into_a_named_input(): void
    {
        $html = (string) $this->blade('<x-owner.gallery-picker name="image_asset_id" :images="$images" />', [
            'images' => [['id' => 7, 'url' => '/storage/a.jpg', 'alt' => 'Studio <depan>']],
        ]);

        $this->assertStringContainsString('name="image_asset_id"', $html);
        $this->assertStringContainsString('/storage/a.jpg', $html);
        $this->assertStringContainsString('Studio &lt;depan&gt;', $html);
    }

    public function test_one_booking_panel_and_one_customer_panel_serve_every_owner_page(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        Tenant::factory()->create(['iduser' => $owner->id]);

        $html = (string) $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk()->getContent();

        // AC-PNL-06: a single panel instance, opened by one event from any entry point.
        $this->assertSame(1, substr_count($html, 'id="owner-booking-panel"'));
        $this->assertSame(1, substr_count($html, 'id="owner-customer-panel"'));
        $this->assertStringContainsString('x-on:open-booking-panel.window', $html);
        $this->assertStringContainsString('x-on:open-customer-panel.window', $html);
        $this->assertStringContainsString('js/owner-ui.js', $html);
    }

    public function test_flash_messages_show_once_as_a_toast(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        Tenant::factory()->create(['iduser' => $owner->id]);

        $html = (string) $this->actingAs($owner)->withSession(['pesan' => 'Profil belum lengkap'])
            ->get(route('owner.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertSame(1, substr_count($html, 'Profil belum lengkap'));
    }

    public function test_permission_denied_page_does_not_reveal_the_record(): void
    {
        Route::middleware('web')->get('/_test/forbidden', fn () => abort(403, 'Booking #42 milik tenant lain'));

        $this->get('/_test/forbidden')
            ->assertForbidden()
            ->assertSee('Tidak punya akses')
            ->assertDontSee('Booking #42');
    }
}

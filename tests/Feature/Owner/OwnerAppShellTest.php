<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use App\Support\CustomerBookingRoutes;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BQ-128 (FR-SHELL-001, FR-SHELL-002, ADR-0021): the owner app shell.
 */
class OwnerAppShellTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-09 09:00:00', 'Asia/Jakarta'));

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now(), 'namalengkap' => 'Bagus Pemilik']);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id, 'namabisnis' => 'Studio Bagus', 'slug' => 'studio-bagus']);
        $this->subscribe($this->tenant, 'pro');
    }

    private function subscribe(Tenant $tenant, string $planName, string $status = 'active', ?Carbon $ends = null): Subscription
    {
        $plan = Plan::firstOrCreate(['namapaket' => $planName], ['hargabulanan' => 100000, 'maxlayanan' => 0, 'maxbooking' => 0, 'isunlimited' => true]);

        return Subscription::create([
            'idtenant' => $tenant->id,
            'idplan' => $plan->id,
            'status' => $status,
            'trial_berakhir' => $status === 'trial' ? ($ends ?? now()->addDays(5)) : null,
            'langganan_mulai' => $status === 'active' ? now()->subDays(3) : null,
            'langganan_berakhir' => $status === 'active' ? ($ends ?? now()->addDays(27)) : null,
        ]);
    }

    private function member(string $role): User
    {
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now(), 'namalengkap' => 'Siska '.$role]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $user->id, 'role' => $role, 'accepted_at' => now()]);

        return $user;
    }

    /**
     * @return list<string>
     */
    private function sidebarMenus(string $html): array
    {
        $start = strpos($html, 'id="owner-sidebar"');
        $this->assertNotFalse($start, 'sidebar rendered');
        $end = strpos($html, '</aside>', (int) $start);
        preg_match_all('/data-nav-id="([a-z-]+)"/', substr($html, (int) $start, (int) $end - (int) $start), $matches);

        return $matches[1];
    }

    public function test_pemilik_sees_every_menu_whose_page_and_module_exist_in_sidebar_order(): void
    {
        $html = (string) $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk()->getContent();

        $this->assertSame(
            ['dashboard', 'calendar', 'bookings', 'customers', 'services', 'schedule', 'reviews', 'payments', 'settings', 'subscription'],
            $this->sidebarMenus($html)
        );
        foreach (['Bisnis saya', 'Pemasaran', 'Keuangan', 'Lainnya'] as $group) {
            $this->assertStringContainsString($group, $html);
        }
        $this->assertStringNotContainsString('Admin Portal', $html);
    }

    public function test_kasir_and_manajer_see_only_the_menus_their_role_may_open(): void
    {
        $kasir = (string) $this->actingAs($this->member('kasir'))->get(route('owner.dashboard'))->assertOk()->getContent();
        $this->assertSame(['dashboard', 'calendar', 'bookings', 'customers', 'settings'], $this->sidebarMenus($kasir));
        $this->assertStringContainsString('Kasir', $kasir);

        $manajer = (string) $this->actingAs($this->member('manajer'))->get(route('owner.dashboard'))->assertOk()->getContent();
        $this->assertSame(['dashboard', 'calendar', 'bookings', 'customers', 'services', 'schedule', 'reviews', 'settings'], $this->sidebarMenus($manajer));
    }

    public function test_a_menu_above_the_plan_shows_a_lock_and_opens_the_upgrade_prompt(): void
    {
        config(['modules.reports' => 'released']);
        Subscription::query()->delete();
        $this->subscribe($this->tenant, 'small');

        $html = (string) $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk()->getContent();

        $this->assertContains('reports', $this->sidebarMenus($html));
        $this->assertMatchesRegularExpression('/data-nav-id="reports"[^>]*data-locked="true"/', $html);
        $this->assertStringContainsString("open-dialog', { id: 'owner-upgrade-dialog'", $html);
        $this->assertStringContainsString('id="owner-upgrade-dialog"', $html);
    }

    public function test_only_the_current_page_is_marked_active(): void
    {
        $html = (string) $this->actingAs($this->owner)->get(route('owner.schedule'))->assertOk()->getContent();
        $sidebar = substr($html, (int) strpos($html, 'id="owner-sidebar"'));
        $sidebar = substr($sidebar, 0, (int) strpos($sidebar, '</aside>'));

        $this->assertSame(1, substr_count($sidebar, 'aria-current="page"'));
        $this->assertMatchesRegularExpression('/data-nav-id="schedule"[^>]*aria-current="page"/', $sidebar);
    }

    public function test_top_bar_offers_bell_new_booking_search_and_account_menu(): void
    {
        $html = (string) $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="owner-notification-bell"', $html);
        $this->assertStringContainsString('id="owner-new-booking"', $html);
        $this->assertStringContainsString('id="owner-search"', $html);
        $this->assertStringContainsString(str_replace('/', '\/', route('owner.search')), $html);
        $this->assertStringContainsString('Bagus Pemilik', $html);
        $this->assertStringContainsString('Pemilik', $html);
        $this->assertStringContainsString('action="'.route('logout').'"', $html);
        $this->assertStringContainsString(CustomerBookingRoutes::url('customer.booking.program', $this->tenant->slug), $html);
        $this->assertStringContainsString('id="owner-walkin-dialog"', $html);
        $this->assertStringContainsString(str_replace('/', '\/', route('owner.bookings.walkin-slots')), $html);
    }

    public function test_new_booking_is_hidden_when_the_subscription_has_ended(): void
    {
        Subscription::query()->delete();
        $this->subscribe($this->tenant, 'pro', 'active', now()->subDay());

        $html = (string) $this->actingAs($this->owner)->get(route('owner.subscription'))->assertOk()->getContent();

        $this->assertStringNotContainsString('id="owner-new-booking"', $html);
        $this->assertStringNotContainsString('id="owner-walkin-dialog"', $html);
    }

    public function test_phones_get_a_bottom_bar_and_a_more_sheet(): void
    {
        $html = (string) $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="owner-bottom-nav"', $html);
        $bar = substr($html, (int) strpos($html, 'id="owner-bottom-nav"'));
        $bar = substr($bar, 0, (int) strpos($bar, '</nav>'));
        $this->assertMatchesRegularExpression('/Beranda.*Kalender.*Booking.*Booking.*Lainnya/s', strip_tags($bar));
        $this->assertStringContainsString('id="owner-more-sheet"', $html);
        $sheet = substr($html, (int) strpos($html, 'id="owner-more-sheet"'));
        foreach (['Pelanggan', 'Layanan', 'Jam Buka &amp; Harga', 'Pengaturan'] as $menu) {
            $this->assertStringContainsString($menu, $sheet);
        }
    }

    public function test_search_finds_own_bookings_and_customers_only(): void
    {
        $own = $this->booking($this->tenant, 'BKQ-OWN-1234', 'Rani Puspita', '081234500001');
        $other = Tenant::factory()->create();
        $this->booking($other, 'BKQ-FOREIGN-9', 'Rani Asing', '081234599999');

        $byCode = $this->actingAs($this->owner)->getJson(route('owner.search', ['q' => 'BKQ-OWN']))->assertOk();
        $byCode->assertJsonPath('bookings.0.code', 'BKQ-OWN-1234');
        $this->assertStringContainsString('BKQ-OWN-1234', (string) $byCode->json('bookings.0.href'));

        $this->getJson(route('owner.search', ['q' => 'BKQ-FOREIGN-9']))->assertOk()->assertJsonCount(0, 'bookings')->assertJsonCount(0, 'customers');

        $byName = $this->getJson(route('owner.search', ['q' => 'Rani']))->assertOk();
        $this->assertSame(['Rani Puspita'], array_column($byName->json('customers'), 'name'));
        $this->assertSame(['BKQ-OWN-1234'], array_column($byName->json('bookings'), 'code'));

        $this->getJson(route('owner.search', ['q' => '0812345000']))->assertOk()->assertJsonPath('customers.0.name', 'Rani Puspita');
        $this->getJson(route('owner.search', ['q' => 'R']))->assertOk()->assertJsonCount(0, 'bookings');

        $this->actingAs($this->member('kasir'))->getJson(route('owner.search', ['q' => 'Rani']))->assertOk()->assertJsonPath('customers.0.name', 'Rani Puspita');
        $this->assertNotNull($own->id);
    }

    public function test_walk_in_slots_list_only_free_slots_of_the_current_business(): void
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'namalayanan' => 'Foto Keluarga', 'is_active' => true]);
        $date = Carbon::tomorrow('Asia/Jakarta')->toDateString();
        $free = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $service->id, 'tanggal' => $date, 'jam_mulai' => '10:00:00', 'jam_selesai' => '10:59:00', 'status' => 'tersedia']);
        $taken = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $service->id, 'tanggal' => $date, 'jam_mulai' => '11:00:00', 'jam_selesai' => '11:59:00', 'status' => 'tersedia']);
        Booking::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $service->id, 'idschedule' => $taken->id, 'tanggalbooking' => $date, 'jam' => '11:00:00', 'status' => 'paid']);
        $other = Tenant::factory()->create();
        $foreignService = Service::factory()->create(['idtenant' => $other->id, 'is_active' => true]);
        Schedule::factory()->create(['idtenant' => $other->id, 'idlayanan' => $foreignService->id, 'tanggal' => $date, 'jam_mulai' => '12:00:00', 'jam_selesai' => '12:59:00', 'status' => 'tersedia']);

        $slots = $this->actingAs($this->owner)->getJson(route('owner.bookings.walkin-slots', ['date' => $date]))->assertOk()->json('slots');

        $this->assertSame([$free->id], array_column($slots, 'id'));
        $this->assertSame('Foto Keluarga · 10:00 – 11:00', $slots[0]['label']);
        $this->getJson(route('owner.bookings.walkin-slots', ['date' => 'besok']))->assertUnprocessable();
    }

    public function test_shell_does_no_hover_prefetch_and_loads_charts_only_where_drawn(): void
    {
        $settings = (string) $this->actingAs($this->owner)->get(route('owner.settings'))->assertOk()->getContent();
        $this->assertStringNotContainsString('prefetch', $settings);
        $this->assertStringNotContainsString('chart.umd', $settings);

        $dashboard = (string) $this->get(route('owner.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('chart.umd', $dashboard);
    }

    public function test_plan_summary_shows_the_real_trial_days_left(): void
    {
        Subscription::query()->delete();
        $this->subscribe($this->tenant, 'pro', 'trial', now()->addDays(5));

        $html = (string) $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-nav-id="subscription"[\s\S]{0,1500}Sisa 5 hari/', $html);
    }

    private function booking(Tenant $tenant, string $code, string $name, string $phone): Booking
    {
        $service = Service::factory()->create(['idtenant' => $tenant->id]);
        $slot = Schedule::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id]);
        $payment = Payment::factory()->create(['idtenant' => $tenant->id, 'status' => 'sukses']);

        return Booking::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'idschedule' => $slot->id, 'idpayment' => $payment->id,
            'booking_code' => $code, 'namapelanggan' => $name, 'nomorhp' => $phone, 'status' => 'paid',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Infrastructure\Dns\DnsLookup;
use App\Jobs\CheckCustomDomainJob;
use App\Models\Asset;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantLandingPage;
use App\Models\TenantMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * BQ-154 (owner-portal.md §8.9, FR-PUBLIC-007, D-005): the Landing page and Domain sendiri sections
 * of Halaman Booking, for Pro.
 */
class LandingPageAndDomainTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    /** @var array{cname: array<string, list<string>>, a: array<string, list<string>>, txt: array<string, list<string>>} */
    private array $dns = ['cname' => [], 'a' => [], 'txt' => []];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-12 09:00:00'));
        config([
            'modules.booking-page' => 'released',
            'custom-domains.cname_target' => 'cname.bookqu-edge.net',
            'custom-domains.a_records' => ['203.0.113.10'],
        ]);

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id, 'slug' => 'studio-rani', 'namabisnis' => 'Studio Rani', 'deskripsi' => 'Studio foto keluarga.']);
        Service::factory()->create(['idtenant' => $this->tenant->id, 'namalayanan' => 'Foto Keluarga', 'is_active' => true]);
        $this->subscribe('pro');

        $test = $this;
        $this->app->instance(DnsLookup::class, new class($test) extends DnsLookup
        {
            public function __construct(private readonly LandingPageAndDomainTest $test) {}

            public function cnameTargets(string $host): array
            {
                return $this->test->dnsRecords('cname', $host);
            }

            public function ipv4(string $host): array
            {
                return $this->test->dnsRecords('a', $host);
            }

            public function txt(string $host): array
            {
                return $this->test->dnsRecords('txt', $host);
            }
        });
    }

    /**
     * @return list<string>
     */
    public function dnsRecords(string $type, string $host): array
    {
        return $this->dns[$type][$host] ?? [];
    }

    private function subscribe(string $planName): void
    {
        Subscription::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->delete();
        $plan = Plan::updateOrCreate(['namapaket' => $planName], ['hargabulanan' => 100000, 'maxlayanan' => 0, 'maxbooking' => 0, 'isunlimited' => true]);
        Subscription::create([
            'idtenant' => $this->tenant->id, 'idplan' => $plan->id, 'status' => 'active',
            'langganan_mulai' => now()->subMonth(), 'langganan_berakhir' => now()->addMonth(),
        ]);
    }

    private function member(string $role): User
    {
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $user->id, 'role' => $role, 'accepted_at' => now()]);

        return $user;
    }

    private function asset(?Tenant $tenant = null): Asset
    {
        return Asset::create(['idtenant' => ($tenant ?? $this->tenant)->id, 'title' => 'Banner studio', 'category' => 'banner', 'file_path' => 'assets/banner-'.uniqid().'.jpg']);
    }

    private function tenantRow(): Tenant
    {
        return Tenant::withoutGlobalScopes()->findOrFail($this->tenant->id);
    }

    /** The owner publishes the ownership TXT record shown on the Domain sendiri section. */
    private function proveOwnership(string $domain): void
    {
        $this->dns['txt']['_bookqu.'.$domain] = ['bookqu-verify='.$this->tenantRow()->custom_domain_token];
    }

    public function test_ac_hbk_03_landing_page_and_domain_are_refused_below_pro(): void
    {
        $this->subscribe('medium');
        $this->actingAs($this->owner);

        $this->get(route('owner.booking-page', ['section' => 'landing']))->assertOk()->assertSee('Landing page tersedia di paket Pro')->assertDontSee('name="headline"', false);
        $this->get(route('owner.booking-page', ['section' => 'domain']))->assertOk()->assertSee('Domain sendiri tersedia di paket Pro')->assertDontSee('name="custom_domain"', false);

        $this->post(route('owner.booking-page.landing'), ['template' => 'modern', 'headline' => 'Halo', 'action' => 'publish'])->assertRedirect(route('owner.subscription'));
        $this->get(route('owner.booking-page.landing.preview'))->assertRedirect(route('owner.subscription'));
        $this->post(route('owner.booking-page.domain'), ['custom_domain' => 'studio.example.com'])->assertRedirect(route('owner.subscription'));
        $this->post(route('owner.booking-page.domain.check'))->assertRedirect(route('owner.subscription'));

        $this->assertSame(0, TenantLandingPage::withoutGlobalScopes()->count());
        $this->assertNull($this->tenantRow()->custom_domain);

        $this->subscribe('pro');
        $this->get(route('owner.booking-page', ['section' => 'landing']))->assertOk()->assertSee('name="headline"', false);
        $this->get(route('owner.booking-page', ['section' => 'domain']))->assertOk()->assertSee('name="custom_domain"', false)->assertSee('cname.bookqu-edge.net');

        $kasir = $this->member('kasir');
        $this->actingAs($kasir)->get(route('owner.booking-page', ['section' => 'landing']))->assertForbidden();
        $this->actingAs($kasir)->post(route('owner.booking-page.landing'), ['template' => 'modern', 'action' => 'save'])->assertForbidden();
        $this->actingAs($kasir)->post(route('owner.booking-page.domain'), ['custom_domain' => 'a.example.com'])->assertForbidden();
    }

    public function test_the_landing_page_is_saved_previewed_and_published_on_the_storefront_home(): void
    {
        $banner = $this->asset();
        $this->actingAs($this->owner);

        $this->post(route('owner.booking-page.landing'), [
            'template' => 'creative', 'headline' => '<b>Foto keluarga</b> terbaik', 'sections' => ['tentang', 'layanan'],
            'banner_asset_id' => $banner->id, 'action' => 'save',
        ])->assertRedirect(route('owner.booking-page', ['section' => 'landing']));

        $landing = TenantLandingPage::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->firstOrFail();
        $this->assertSame('creative', $landing->template);
        $this->assertSame(['tentang', 'layanan'], $landing->sections);
        $this->assertNull($landing->published_at);
        $this->get('/studio-rani')->assertOk()->assertDontSee('terbaik');

        $this->get(route('owner.booking-page.landing.preview'))->assertOk()
            ->assertSee('&lt;b&gt;Foto keluarga&lt;/b&gt; terbaik', false)
            ->assertSee('Studio foto keluarga.')
            ->assertSee($banner->url);

        $this->post(route('owner.booking-page.landing'), [
            'template' => 'creative', 'headline' => '<b>Foto keluarga</b> terbaik', 'sections' => ['tentang', 'layanan'],
            'banner_asset_id' => $banner->id, 'action' => 'publish',
        ])->assertRedirect();
        $this->get('/studio-rani')->assertOk()
            ->assertSee('&lt;b&gt;Foto keluarga&lt;/b&gt; terbaik', false)
            ->assertDontSee('<b>Foto keluarga</b>', false)
            ->assertSee('data-landing-template="creative"', false)
            ->assertSee($banner->url)
            ->assertSee('Pilih Layanan');

        $this->post(route('owner.booking-page.landing'), ['template' => 'creative', 'headline' => 'Foto', 'action' => 'unpublish'])->assertRedirect();
        $this->get('/studio-rani')->assertOk()->assertDontSee('data-landing-template', false);
    }

    public function test_the_landing_page_rejects_other_businesses_images_and_unknown_choices(): void
    {
        $foreign = $this->asset(Tenant::factory()->create());
        $this->actingAs($this->owner);

        $this->post(route('owner.booking-page.landing'), ['template' => 'modern', 'banner_asset_id' => $foreign->id, 'action' => 'save'])->assertSessionHasErrors('banner_asset_id');
        $this->post(route('owner.booking-page.landing'), ['template' => 'neon', 'action' => 'save'])->assertSessionHasErrors('template');
        $this->post(route('owner.booking-page.landing'), ['template' => 'modern', 'sections' => ['ulasan'], 'action' => 'save'])->assertSessionHasErrors('sections.0');
        $this->assertSame(0, TenantLandingPage::withoutGlobalScopes()->count());
    }

    public function test_landing_preview_renders_unsaved_fields_without_persisting_them(): void
    {
        $this->actingAs($this->owner)->post('/owner/booking-page/landing/preview', [
            'template' => 'modern', 'headline' => 'Judul belum disimpan', 'sections' => ['layanan'], 'action' => 'save',
        ])->assertOk()->assertSeeText('Judul belum disimpan');
        $this->assertDatabaseMissing('tenant_landing_pages', ['idtenant' => $this->tenant->id, 'headline' => 'Judul belum disimpan']);
    }

    public function test_a_published_landing_page_disappears_when_the_plan_drops_below_pro_and_its_banner_follows_the_gallery(): void
    {
        $banner = $this->asset();
        $this->actingAs($this->owner);
        $this->post(route('owner.booking-page.landing'), ['template' => 'clinic', 'headline' => 'Klinik ramah keluarga', 'banner_asset_id' => $banner->id, 'action' => 'publish']);
        $this->get('/studio-rani')->assertSee('Klinik ramah keluarga');

        $this->delete(route('owner.booking-page.media.destroy', $banner->id))->assertSessionHasErrors('asset');
        $this->delete(route('owner.booking-page.media.destroy', $banner->id), ['confirm' => 1])->assertSessionHasNoErrors();
        $this->assertNull(TenantLandingPage::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->value('banner_asset_id'));

        $this->subscribe('medium');
        $this->get('/studio-rani')->assertOk()->assertDontSee('Klinik ramah keluarga');
    }

    public function test_ac_hbk_04_a_domain_is_accepted_only_once_and_stored_normalised(): void
    {
        Tenant::factory()->create(['custom_domain' => 'booking.example.com']);
        Queue::fake();
        $this->actingAs($this->owner);

        $this->post(route('owner.booking-page.domain'), ['custom_domain' => 'Booking.Example.com'])->assertSessionHasErrors('custom_domain');
        foreach (['localhost', '127.0.0.1', 'tanpa-titik', 'bookqu.test', 'studio.bookqu.test', 'a b.example.com', 'http://'] as $invalid) {
            $this->post(route('owner.booking-page.domain'), ['custom_domain' => $invalid])->assertSessionHasErrors('custom_domain');
        }
        $this->assertNull($this->tenantRow()->custom_domain);

        $this->post(route('owner.booking-page.domain'), ['custom_domain' => ' https://Studio.Example.com./harga '])->assertSessionHasNoErrors();

        $tenant = $this->tenantRow();
        $this->assertSame('studio.example.com', $tenant->custom_domain);
        $this->assertSame('pending', $tenant->custom_domain_status);
        $this->assertNull($tenant->custom_domain_verified_at);
        Queue::assertPushed(CheckCustomDomainJob::class, fn (CheckCustomDomainJob $job): bool => $job->tenantId === (int) $this->tenant->id);

        $this->post(route('owner.booking-page.domain'), ['custom_domain' => ''])->assertSessionHasNoErrors();
        $this->assertNull($this->tenantRow()->custom_domain);
        $this->assertNull($this->tenantRow()->custom_domain_status);
    }

    public function test_ac_hbk_04_the_check_reports_the_real_dns_state_and_only_a_connected_domain_resolves(): void
    {
        Queue::fake();
        $this->actingAs($this->owner);
        $this->post(route('owner.booking-page.domain'), ['custom_domain' => 'studio.example.com']);

        $this->get('http://studio.example.com/')->assertNotFound();

        $this->dns['cname']['studio.example.com'] = ['other-host.example.net'];
        $this->post(route('owner.booking-page.domain.check'))->assertSessionHas('error');
        $this->assertSame('not_connected', $this->tenantRow()->custom_domain_status);
        $this->get(route('owner.booking-page', ['section' => 'domain']))->assertSee('Belum terhubung')->assertSee('other-host.example.net');
        $this->get('http://studio.example.com/')->assertNotFound();

        $this->dns['cname']['studio.example.com'] = ['CNAME.bookqu-edge.net.'];
        $this->proveOwnership('studio.example.com');
        $this->post(route('owner.booking-page.domain.check'))->assertSessionHas('sukses');
        $tenant = $this->tenantRow();
        $this->assertSame('connected', $tenant->custom_domain_status);
        $this->assertNotNull($tenant->custom_domain_verified_at);
        $this->assertNotNull($tenant->custom_domain_checked_at);
        $this->get(route('owner.booking-page', ['section' => 'domain']))->assertSee('Terhubung');
        $this->get('http://studio.example.com/')->assertOk()->assertSee('Studio Rani');

        // The hourly check notices a domain that moved away and stops serving it.
        $this->dns['cname']['studio.example.com'] = [];
        $this->artisan('domains:check')->assertSuccessful();
        $this->assertSame('not_connected', $this->tenantRow()->custom_domain_status);
        $this->get('http://studio.example.com/')->assertNotFound();
    }

    public function test_an_apex_domain_connects_through_the_configured_a_record(): void
    {
        Queue::fake();
        $this->actingAs($this->owner);
        $this->post(route('owner.booking-page.domain'), ['custom_domain' => 'studiorani.id']);

        $this->dns['a']['studiorani.id'] = ['198.51.100.7'];
        $this->post(route('owner.booking-page.domain.check'));
        $this->assertSame('not_connected', $this->tenantRow()->custom_domain_status);

        $this->dns['a']['studiorani.id'] = ['198.51.100.7', '203.0.113.10'];
        $this->proveOwnership('studiorani.id');
        $this->post(route('owner.booking-page.domain.check'));
        $this->assertSame('connected', $this->tenantRow()->custom_domain_status);
        $this->get('http://studiorani.id/')->assertOk()->assertSee('Studio Rani');
    }

    public function test_without_configured_targets_no_domain_is_marked_connected(): void
    {
        config(['custom-domains.cname_target' => '', 'custom-domains.a_records' => []]);
        Queue::fake();
        $this->actingAs($this->owner);
        $this->post(route('owner.booking-page.domain'), ['custom_domain' => 'studio.example.com']);
        $this->dns['cname']['studio.example.com'] = ['cname.bookqu-edge.net'];

        $this->post(route('owner.booking-page.domain.check'))->assertSessionHas('error');
        $this->assertSame('not_connected', $this->tenantRow()->custom_domain_status);
    }

    public function test_a_domain_pointing_here_does_not_connect_without_this_businesss_ownership_record(): void
    {
        Queue::fake();
        $this->actingAs($this->owner);
        $this->post(route('owner.booking-page.domain'), ['custom_domain' => 'studio.example.com']);
        $this->dns['cname']['studio.example.com'] = ['cname.bookqu-edge.net'];
        $token = (string) $this->tenantRow()->custom_domain_token;
        $this->assertSame(32, strlen($token));
        $this->get(route('owner.booking-page', ['section' => 'domain']))
            ->assertSee('_bookqu.studio.example.com')->assertSee('bookqu-verify='.$token);

        // Pointing the domain here is not proof of ownership.
        $this->post(route('owner.booking-page.domain.check'))->assertSessionHas('error');
        $this->assertSame('not_connected', $this->tenantRow()->custom_domain_status);

        // Another business's token does not prove it either.
        $this->dns['txt']['_bookqu.studio.example.com'] = ['bookqu-verify='.str_repeat('a', 32)];
        $this->post(route('owner.booking-page.domain.check'));
        $this->assertSame('not_connected', $this->tenantRow()->custom_domain_status);
        $this->get('http://studio.example.com/')->assertNotFound();

        $this->proveOwnership('studio.example.com');
        $this->post(route('owner.booking-page.domain.check'))->assertSessionHas('sukses');
        $this->get('http://studio.example.com/')->assertOk();

        // A new domain needs a new record.
        $this->post(route('owner.booking-page.domain'), ['custom_domain' => 'booking.example.com']);
        $this->assertNotSame($token, $this->tenantRow()->custom_domain_token);
    }

    public function test_the_hourly_check_stops_a_connected_domain_once_the_business_drops_below_pro(): void
    {
        Queue::fake();
        $this->actingAs($this->owner);
        $this->post(route('owner.booking-page.domain'), ['custom_domain' => 'studio.example.com']);
        $this->dns['cname']['studio.example.com'] = ['cname.bookqu-edge.net'];
        $this->proveOwnership('studio.example.com');
        $this->post(route('owner.booking-page.domain.check'));
        $this->assertSame('connected', $this->tenantRow()->custom_domain_status);

        $this->subscribe('small');
        $this->artisan('domains:check')->assertSuccessful();

        $this->assertSame('not_connected', $this->tenantRow()->custom_domain_status);
        $this->assertSame('studio.example.com', $this->tenantRow()->custom_domain);
        $this->get('http://studio.example.com/')->assertNotFound();
    }

    public function test_the_former_landing_page_path_redirects_to_its_section(): void
    {
        $this->actingAs($this->owner)->get('/owner/landing-page')
            ->assertStatus(301)
            ->assertRedirect(route('owner.booking-page', ['section' => 'landing']));
    }
}

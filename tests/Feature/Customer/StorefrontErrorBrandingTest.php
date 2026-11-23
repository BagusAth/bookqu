<?php

namespace Tests\Feature\Customer;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Domain\Tenant\CustomDomain;
use App\Models\Tenant;
use App\Support\StorefrontBranding;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as RenderedView;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StorefrontErrorBrandingTest extends TestCase
{
    use RefreshDatabase;

    private array $brandData = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost', 'app.debug' => false]);

        $error = static function (): Response {
            $status = (int) request()->route('status');

            // The real storefront CSRF handler redirects. Exercise the shared 419 view explicitly.
            if ($status === 419) {
                return response()->view('errors.419', [], 419);
            }

            abort($status, 'Internal diagnostic for test');
        };

        Route::middleware('tenant')->get('/{slug_usaha}/error-preview/{status}', $error)
            ->name('customer.booking.slug.error-preview');
        Route::domain('{custom_domain}')->middleware('tenant')->get('/error-preview/{status}', $error)
            ->where('custom_domain', '.+')
            ->name('customer.booking.error-preview');

        View::composer('errors.customer', function (RenderedView $view): void {
            $this->brandData = Arr::only($view->getData(), ['branding', 'businessName', 'storefrontUrl']);
        });
    }

    #[TestWith([403])]
    #[TestWith([404])]
    #[TestWith([419])]
    #[TestWith([500])]
    #[TestWith([503])]
    public function test_storefront_errors_use_only_the_resolved_storefront_brand(int $status): void
    {
        $tenant = Tenant::factory()->create(['namabisnis' => 'Studio Angkasa', 'theme_color' => '#104060']);
        $foreign = Tenant::factory()->create(['namabisnis' => 'Studio Bintang', 'theme_color' => '#AB1234']);
        app(CreateTrialSubscription::class)->execute($tenant);
        app(CreateTrialSubscription::class)->execute($foreign);

        $this->get("/{$tenant->slug}/error-preview/{$status}?tenant_id={$foreign->id}")->assertStatus($status)
            ->assertDontSee($foreign->namabisnis)->assertDontSee($foreign->theme_color)
            ->assertDontSee('Internal diagnostic for test');

        $this->assertSame([
            'branding' => StorefrontBranding::for($tenant),
            'businessName' => $tenant->namabisnis,
            'storefrontUrl' => 'http://localhost/'.$tenant->slug,
        ], $this->brandData);
        $this->assertNull(app(TenantContext::class)->getTenantId());
    }

    public function test_connected_custom_domain_uses_its_storefront_brand(): void
    {
        $tenant = Tenant::factory()->create(['custom_domain' => 'studio-angkasa.example', 'theme_color' => '#104060']);
        $tenant->forceFill(['custom_domain_status' => CustomDomain::STATUS_CONNECTED])->save();
        app(CreateTrialSubscription::class)->execute($tenant);

        $this->get('https://studio-angkasa.example/error-preview/404')->assertNotFound();
        $this->assertSame([
            'branding' => StorefrontBranding::for($tenant),
            'businessName' => $tenant->namabisnis,
            'storefrontUrl' => 'https://studio-angkasa.example',
        ], $this->brandData);

        $tenant->forceFill(['custom_domain_status' => CustomDomain::STATUS_PENDING])->save();
        $this->get('https://studio-angkasa.example/error-preview/404')->assertNotFound()
            ->assertDontSee($tenant->namabisnis)->assertDontSee($tenant->theme_color);
        $this->assertBookQuBrand();
    }

    public function test_slug_error_storefront_url_stays_on_the_platform_even_on_another_tenants_domain(): void
    {
        $tenant = Tenant::factory()->create(['namabisnis' => 'Studio Angkasa', 'theme_color' => '#104060']);
        $foreign = Tenant::factory()->create(['custom_domain' => 'studio-bintang.example', 'theme_color' => '#AB1234']);
        $foreign->forceFill(['custom_domain_status' => CustomDomain::STATUS_CONNECTED])->save();
        app(CreateTrialSubscription::class)->execute($tenant);

        $this->get("https://studio-bintang.example/{$tenant->slug}/error-preview/404")->assertNotFound()
            ->assertDontSee($foreign->namabisnis)->assertDontSee($foreign->theme_color);

        $this->assertSame($tenant->namabisnis, $this->brandData['businessName']);
        $this->assertSame('http://localhost/'.$tenant->slug, $this->brandData['storefrontUrl']);
    }

    public function test_inactive_and_expired_tenants_keep_generic_error_branding(): void
    {
        $tenant = Tenant::factory()->create(['namabisnis' => 'Studio Angkasa', 'theme_color' => '#104060']);
        $path = "/{$tenant->slug}/error-preview/404";

        $this->get($path)->assertNotFound()->assertDontSee($tenant->namabisnis)->assertDontSee($tenant->theme_color);
        $this->assertBookQuBrand();

        $subscription = app(CreateTrialSubscription::class)->execute($tenant);
        $subscription->update(['trial_berakhir' => now()->subMinute()]);
        $this->get($path)->assertNotFound()->assertDontSee($tenant->namabisnis)->assertDontSee($tenant->theme_color);
        $this->assertBookQuBrand();
    }

    public function test_errors_without_a_public_storefront_context_keep_bookqu_branding(): void
    {
        $tenant = Tenant::factory()->create(['namabisnis' => 'Studio Angkasa', 'theme_color' => '#104060']);
        app(CreateTrialSubscription::class)->execute($tenant);

        $this->get("/{$tenant->slug}/error-preview/404")->assertNotFound();
        $this->assertSame($tenant->namabisnis, $this->brandData['businessName']);

        foreach (['/unknown-storefront/error-preview/404', '/manage/unknown-booking', "/{$tenant->slug}/booking/payment/unknown-order/invoice"] as $path) {
            $this->get($path)->assertNotFound()->assertSee('BookQu')
                ->assertDontSee($tenant->namabisnis)->assertDontSee($tenant->theme_color);
            $this->assertBookQuBrand();
        }
    }

    private function assertBookQuBrand(): void
    {
        $this->assertSame([
            'branding' => null,
            'businessName' => 'BookQu',
            'storefrontUrl' => 'http://localhost/',
        ], $this->brandData);
    }
}

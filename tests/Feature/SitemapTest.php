<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_ok_with_valid_xml_headers(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'Sitemap content is not valid XML');
        $this->assertSame('urlset', $xml->getName());
    }

    public function test_sitemap_contains_main_landing_page(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());

        $urls = [];
        foreach ($xml->url as $urlElement) {
            $urls[] = (string) $urlElement->loc;
        }

        $this->assertContains(url('/'), $urls);
    }

    public function test_sitemap_contains_active_tenants(): void
    {
        $tenant1 = Tenant::factory()->create(['slug' => 'active-barbershop-test']);
        $tenant2 = Tenant::factory()->create(['slug' => 'active-salon-test']);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());

        $urls = [];
        foreach ($xml->url as $urlElement) {
            $urls[] = (string) $urlElement->loc;
        }

        $this->assertContains(url('/active-barbershop-test'), $urls);
        $this->assertContains(url('/active-salon-test'), $urls);
    }

    public function test_sitemap_excludes_tenants_with_expired_or_cancelled_subscription(): void
    {
        $plan = Plan::create([
            'namapaket' => 'pro',
            'hargabulanan' => 499000,
            'maxlayanan' => 0,
            'maxbooking' => 0,
            'isunlimited' => true,
        ]);

        $activeTenant = Tenant::factory()->create(['slug' => 'active-pro-tenant']);
        Subscription::withoutGlobalScopes()->create([
            'idtenant' => $activeTenant->id,
            'idplan' => $plan->id,
            'status' => 'active',
            'langganan_berakhir' => now()->addMonth(),
        ]);

        $expiredTenant = Tenant::factory()->create(['slug' => 'expired-tenant']);
        Subscription::withoutGlobalScopes()->create([
            'idtenant' => $expiredTenant->id,
            'idplan' => $plan->id,
            'status' => 'expired',
            'langganan_berakhir' => now()->subDay(),
        ]);

        $cancelledTenant = Tenant::factory()->create(['slug' => 'cancelled-tenant']);
        Subscription::withoutGlobalScopes()->create([
            'idtenant' => $cancelledTenant->id,
            'idplan' => $plan->id,
            'status' => 'cancelled',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());

        $urls = [];
        foreach ($xml->url as $urlElement) {
            $urls[] = (string) $urlElement->loc;
        }

        $this->assertContains(url('/active-pro-tenant'), $urls);
        $this->assertNotContains(url('/expired-tenant'), $urls);
        $this->assertNotContains(url('/cancelled-tenant'), $urls);
    }

    public function test_sitemap_route_does_not_get_captured_by_slug_wildcard(): void
    {
        // If sitemap.xml were captured as a tenant slug, it would look for a Tenant with slug 'sitemap.xml'
        // and abort 404 or render program-selection.
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringContainsString('<?xml', $response->getContent());
        $this->assertStringNotContainsString('Bisnis tidak ditemukan', $response->getContent());
    }

    public function test_robots_txt_contains_sitemap_directive(): void
    {
        $robotsPath = public_path('robots.txt');
        $this->assertFileExists($robotsPath);

        $content = file_get_contents($robotsPath);
        $this->assertStringContainsString('Sitemap: https://bookqu.my.id/sitemap.xml', $content);
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow:', $content);
    }
}

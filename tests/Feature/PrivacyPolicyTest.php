<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;
    public function test_privacy_policy_page_returns_ok_status(): void
    {
        $response = $this->get('/kebijakan-privasi');

        $response->assertStatus(200);
        $response->assertSee('Kebijakan Privasi');
        $response->assertSee('BookQu');
        $response->assertSee('Terakhir diperbarui: 24 September 2026');
    }

    public function test_privacy_policy_page_contains_all_policy_sections(): void
    {
        $response = $this->get('/kebijakan-privasi');

        $response->assertStatus(200);
        $response->assertSee('Informasi yang Kami Kumpulkan');
        $response->assertSee('Penggunaan Informasi');
        $response->assertSee('Informasi Pembayaran');
        $response->assertSee('Berbagi Informasi');
        $response->assertSee('Keamanan Data');
        $response->assertSee('Hak Pengguna');
        $response->assertSee('Perubahan Kebijakan Privasi');
        $response->assertSee('Kontak');
        $response->assertSee('bookquind@gmail.com');
        $response->assertSee('https://bookqu.my.id');
    }

    public function test_legacy_privacy_policy_url_redirects_permanently(): void
    {
        $response = $this->get('/privacy-policy');

        $response->assertStatus(301);
        $response->assertRedirect('/kebijakan-privasi');
    }

    public function test_landing_page_footer_links_to_privacy_policy(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('privacy-policy'));
        $response->assertSee('Kebijakan Privasi');
    }

    public function test_sitemap_includes_privacy_policy_url(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'Sitemap is valid XML');

        $urls = [];
        foreach ($xml->url as $urlElement) {
            $urls[] = (string) $urlElement->loc;
        }

        $this->assertContains(url('/kebijakan-privasi'), $urls);
    }
}

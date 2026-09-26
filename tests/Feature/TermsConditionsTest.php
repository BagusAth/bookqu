<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermsConditionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_conditions_page_returns_ok_status(): void
    {
        $response = $this->get('/syarat-ketentuan');

        $response->assertStatus(200);
        $response->assertSee('Syarat &amp; Ketentuan', false);
        $response->assertSee('BookQu');
        $response->assertSee('Terakhir diperbarui: 24 September 2026');
    }

    public function test_terms_conditions_page_contains_all_13_sections(): void
    {
        $response = $this->get('/syarat-ketentuan');

        $response->assertStatus(200);
        $response->assertSee('1. Tentang BookQu');
        $response->assertSee('2. Akun Pengguna');
        $response->assertSee('3. Penggunaan Layanan');
        $response->assertSee('4. Booking dan Transaksi');
        $response->assertSee('5. Pembayaran');
        $response->assertSee('6. Paket dan Biaya Layanan');
        $response->assertSee('7. Konten dan Data Pengguna');
        $response->assertSee('8. Hak Kekayaan Intelektual');
        $response->assertSee('9. Ketersediaan Layanan');
        $response->assertSee('10. Batasan Tanggung Jawab');
        $response->assertSee('11. Penghentian Akun');
        $response->assertSee('12. Perubahan Syarat &amp; Ketentuan', false);
        $response->assertSee('13. Kontak');
        $response->assertSee('bookquind@gmail.com');
        $response->assertSee('https://bookqu.my.id');
    }

    public function test_legacy_terms_conditions_url_redirects_permanently(): void
    {
        $response = $this->get('/terms-conditions');

        $response->assertStatus(301);
        $response->assertRedirect('/syarat-ketentuan');
    }

    public function test_landing_page_footer_links_to_terms_conditions(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('terms-conditions'));
        $response->assertSee('Syarat & Ketentuan', false);
    }

    public function test_sitemap_includes_terms_conditions_url(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'Sitemap is valid XML');

        $urls = [];
        foreach ($xml->url as $urlElement) {
            $urls[] = (string) $urlElement->loc;
        }

        $this->assertContains(url('/syarat-ketentuan'), $urls);
    }
}

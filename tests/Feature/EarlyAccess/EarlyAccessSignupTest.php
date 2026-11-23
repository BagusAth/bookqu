<?php

declare(strict_types=1);

namespace Tests\Feature\EarlyAccess;

use App\Models\EarlyAccessSignup;
use App\Support\ReservedTenantSlugs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Early access page and sign-up form (BQ-175, FR-PUBLIC-009).
 */
class EarlyAccessSignupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Bowo Santoso',
            'business_name' => 'Bowo Barbershop',
            'business_type' => 'Barbershop',
            'email' => 'bowo@example.com',
            'whatsapp_number' => '081234567890',
        ], $overrides);
    }

    public function test_page_renders_every_section_with_seo_markup(): void
    {
        $response = $this->get('/early-access');

        $response->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('early-access').'">', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('<meta property="og:title"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('action="'.route('early-access.store').'"', false)
            ->assertSeeInOrder(['id="hero-title"', 'id="langkah-title"', 'id="benefit-title"', 'id="daftar-title"', 'id="faq-title"', 'id="cta-title"'], false);

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_valid_sign_up_is_stored_normalized_and_confirmed(): void
    {
        $response = $this->post('/early-access', $this->validPayload([
            'full_name' => '  Bowo   Santoso ',
            'email' => ' Bowo@Example.COM ',
            'whatsapp_number' => '+62 812-3456-7890',
        ]));

        $response->assertRedirect(route('early-access').'#daftar')
            ->assertSessionHas('early_access_registered');
        $this->assertDatabaseHas('early_access_signups', [
            'full_name' => 'Bowo Santoso',
            'business_name' => 'Bowo Barbershop',
            'business_type' => 'Barbershop',
            'email' => 'bowo@example.com',
            'whatsapp_number' => '081234567890',
        ]);

        $this->get('/early-access')->assertSee('Pendaftaran early access kamu sudah kami terima');
    }

    public function test_every_field_is_required(): void
    {
        $this->post('/early-access', [])
            ->assertRedirect(route('early-access').'#daftar')
            ->assertSessionHasErrors(['full_name', 'business_name', 'business_type', 'email', 'whatsapp_number']);

        $this->assertDatabaseCount('early_access_signups', 0);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidFields(): array
    {
        return [
            'name too short' => ['full_name', 'Bo'],
            'name with digits' => ['full_name', 'Bowo 123'],
            'name too long' => ['full_name', str_repeat('a', 101)],
            'business name too long' => ['business_name', str_repeat('b', 151)],
            'business type too short' => ['business_type', 'B'],
            'email without domain' => ['email', 'bowo@'],
            'email too long' => ['email', str_repeat('a', 140).'@example.com'],
            'landline number' => ['whatsapp_number', '0241234567'],
            'too few digits' => ['whatsapp_number', '0812345'],
            'too many digits' => ['whatsapp_number', '08123456789012'],
            'letters in number' => ['whatsapp_number', '0812abc45678'],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_input_is_rejected_and_kept_for_correction(string $field, string $value): void
    {
        [$keptField, $keptValue] = $field === 'business_name' ? ['business_type', 'Barbershop'] : ['business_name', 'Bowo Barbershop'];

        $this->post('/early-access', $this->validPayload([$field => $value]))
            ->assertSessionHasErrors($field)
            ->assertSessionHasInput($keptField, $keptValue);

        $this->assertDatabaseCount('early_access_signups', 0);
    }

    public function test_an_email_signs_up_only_once(): void
    {
        EarlyAccessSignup::factory()->create(['email' => 'bowo@example.com']);

        $this->post('/early-access', $this->validPayload(['email' => 'BOWO@example.com']))
            ->assertSessionHasErrors(['email' => 'Email ini sudah terdaftar early access. Kami akan menghubungi kamu segera.']);

        $this->assertDatabaseCount('early_access_signups', 1);
    }

    public function test_filled_honeypot_is_rejected(): void
    {
        $this->post('/early-access', $this->validPayload(['website' => 'https://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertDatabaseCount('early_access_signups', 0);
    }

    public function test_submissions_are_rate_limited_per_visitor(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/early-access', $this->validPayload(['email' => "bowo{$attempt}@example.com"]))->assertRedirect();
        }

        $this->post('/early-access', $this->validPayload(['email' => 'bowo6@example.com']))->assertTooManyRequests();
        $this->assertDatabaseCount('early_access_signups', 5);
    }

    public function test_page_is_listed_in_sitemap_and_reserved_from_tenant_slugs(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>'.route('early-access').'</loc>', false);

        $this->assertTrue(ReservedTenantSlugs::contains('early-access'));
    }
}

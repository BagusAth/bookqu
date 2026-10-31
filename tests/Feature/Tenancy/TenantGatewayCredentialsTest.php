<?php

namespace Tests\Feature\Tenancy;

use App\Actions\Tenant\GetStorefrontTenant;
use App\Actions\Tenant\UpdateAppearanceSettings;
use App\Actions\Tenant\UpdateBusinessProfile;
use App\Actions\Tenant\UpdatePaymentSettings;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;
use App\Models\Payment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Midtrans\Config as MidtransConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TenantGatewayCredentialsTest extends TestCase
{
    use RefreshDatabase;

    private const KEYS = [
        'midtrans_sandbox_client_key' => 'fixture-sandbox-client',
        'midtrans_sandbox_server_key' => 'fixture-sandbox-server',
        'midtrans_prod_client_key' => 'fixture-production-client',
        'midtrans_prod_server_key' => 'fixture-production-server',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
    }

    public function test_all_four_keys_are_encrypted_at_rest_and_hidden_from_model_json(): void
    {
        $tenant = Tenant::factory()->create(self::KEYS);
        $fresh = $tenant->fresh();

        foreach (self::KEYS as $column => $plain) {
            $cipher = $fresh->getRawOriginal($column);
            $this->assertNotSame($plain, $cipher);
            $this->assertSame($plain, Crypt::decryptString($cipher));
            $this->assertSame($plain, $fresh->{$column});
            $this->assertArrayNotHasKey($column, $fresh->toArray());
            $this->assertStringNotContainsString($plain, $fresh->toJson());
            $this->assertStringNotContainsString($cipher, $fresh->toJson());
        }
    }

    public function test_settings_update_encrypts_new_values_and_preserves_masked_server_keys(): void
    {
        $tenant = Tenant::factory()->create(self::KEYS);
        $originalServer = $tenant->getRawOriginal('midtrans_sandbox_server_key');

        app(UpdatePaymentSettings::class)->execute($tenant, [
            'payment_mode' => 'owner', 'midtrans_environment' => 'sandbox',
            'midtrans_sandbox_client_key' => 'replacement-client', 'midtrans_sandbox_server_key' => '********',
        ]);

        $fresh = $tenant->fresh();
        $this->assertSame('replacement-client', $fresh->midtrans_sandbox_client_key);
        $this->assertNotSame('replacement-client', $fresh->getRawOriginal('midtrans_sandbox_client_key'));
        $this->assertSame($originalServer, $fresh->getRawOriginal('midtrans_sandbox_server_key'));
    }

    /** @return array<string, array{string, string}> */
    public static function environments(): array
    {
        return ['sandbox' => ['sandbox', 'sandbox'], 'production' => ['production', 'prod']];
    }

    #[DataProvider('environments')]
    public function test_gateway_reads_decrypted_tenant_server_credentials(string $environment, string $columnPrefix): void
    {
        $tenant = Tenant::factory()->create([
            ...self::KEYS, 'payment_mode' => 'owner', 'midtrans_environment' => $environment, 'midtrans_status' => 'approved',
        ]);
        $payment = Payment::factory()->create(['idtenant' => $tenant->id, 'provider' => 'midtrans']);
        $original = [MidtransConfig::$serverKey, MidtransConfig::$clientKey, MidtransConfig::$isProduction];
        try {
            app(MidtransPaymentGateway::class)->configureForPayment($payment);
            $this->assertSame(self::KEYS['midtrans_'.$columnPrefix.'_server_key'], MidtransConfig::$serverKey);
            $this->assertSame($environment === 'production', MidtransConfig::$isProduction);
        } finally {
            [MidtransConfig::$serverKey, MidtransConfig::$clientKey, MidtransConfig::$isProduction] = $original;
        }
    }

    public function test_storefront_caches_only_public_fields_for_its_verified_tenant(): void
    {
        $tenant = Tenant::factory()->create([...self::KEYS, 'namabisnis' => 'Studio Rahasia Aman']);
        $other = Tenant::factory()->create(['namabisnis' => 'Bisnis Lain']);

        $this->get('/'.$tenant->slug.'?tenant_id='.$other->id)->assertOk()->assertSee($tenant->namabisnis)->assertDontSee($other->namabisnis);

        $cached = Cache::get('tenant:public:v2:'.$tenant->id);
        $this->assertIsArray($cached);
        $this->assertSame($tenant->id, $cached['id']);
        foreach (self::KEYS as $column => $plain) {
            $this->assertArrayNotHasKey($column, $cached);
            $this->assertStringNotContainsString($plain, json_encode($cached));
            $this->assertStringNotContainsString($tenant->getRawOriginal($column), json_encode($cached));
        }
        $this->assertArrayNotHasKey('saldo_platform', $cached);
        $this->assertNull(Cache::get('tenant:slug:'.$tenant->slug));
        $this->get('/'.$other->slug)->assertOk()->assertSee($other->namabisnis);
        $this->assertSame($other->id, Cache::get('tenant:public:v2:'.$other->id)['id']);
    }

    public function test_public_cache_is_invalidated_after_business_and_appearance_updates(): void
    {
        $tenant = Tenant::factory()->create()->refresh();
        app(GetStorefrontTenant::class)->execute($tenant->id);
        app(UpdateAppearanceSettings::class)->execute($tenant, ['theme_color' => '#123456']);
        $this->assertNull(Cache::get('tenant:public:v2:'.$tenant->id));
        $this->assertSame('#123456', app(GetStorefrontTenant::class)->execute($tenant->id)->theme_color);

        app(UpdateBusinessProfile::class)->execute($tenant->user, $tenant, [
            'namabisnis' => 'Updated Studio', 'jenisbisnis' => $tenant->jenisbisnis,
            'alamat' => 'Alamat baru', 'nomorhp' => $tenant->nomorhp,
        ]);
        $this->assertNull(Cache::get('tenant:public:v2:'.$tenant->id));
        $this->assertSame('Updated Studio', app(GetStorefrontTenant::class)->execute($tenant->id)->namabisnis);
    }

    public function test_unregistered_host_cannot_resolve_a_tenant_from_its_slug(): void
    {
        $tenant = Tenant::factory()->create();

        $this->get('http://'.$tenant->slug.'/booking/checkout')->assertNotFound();
        $this->assertNull(Cache::get('tenant:public:v2:'.$tenant->id));
    }

    public function test_previous_app_key_can_decrypt_existing_credentials_after_rotation(): void
    {
        $tenant = Tenant::factory()->create(self::KEYS);
        $originalKey = config('app.key');
        $originalPrevious = config('app.previous_keys');
        try {
            config(['app.key' => 'base64:'.base64_encode(random_bytes(32)), 'app.previous_keys' => [$originalKey]]);
            $this->app->forgetInstance('encrypter');
            Crypt::clearResolvedInstance('encrypter');

            $this->assertSame(self::KEYS['midtrans_prod_server_key'], $tenant->fresh()->midtrans_prod_server_key);
            $tenant->update(['midtrans_prod_server_key' => 'rotated-fixture-key']);
            $this->assertSame('rotated-fixture-key', Crypt::decryptString($tenant->getRawOriginal('midtrans_prod_server_key')));
        } finally {
            config(['app.key' => $originalKey, 'app.previous_keys' => $originalPrevious]);
            $this->app->forgetInstance('encrypter');
            Crypt::clearResolvedInstance('encrypter');
        }
    }
}

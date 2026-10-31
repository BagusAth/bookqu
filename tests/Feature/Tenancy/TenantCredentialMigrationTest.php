<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('mysql')]
class TenantCredentialMigrationTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        $this->beforeApplicationDestroyed(fn () => $this->truncateTablesForAllConnections());
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_01_001033_encrypt_tenant_gateway_credentials.php');
    }

    public function test_backfill_is_restartable_preserves_null_and_empty_and_down_restores_plaintext(): void
    {
        $tenant = Tenant::factory()->create();
        $plain = [
            'midtrans_sandbox_client_key' => 'legacy-client',
            'midtrans_sandbox_server_key' => str_repeat('legacy-server-', 14),
            'midtrans_prod_client_key' => '',
            'midtrans_prod_server_key' => null,
        ];
        DB::table('tenants')->where('id', $tenant->id)->update([
            ...$plain, 'midtrans_sandbox_client_key' => Crypt::encryptString($plain['midtrans_sandbox_client_key']),
        ]);
        $alreadyEncrypted = DB::table('tenants')->where('id', $tenant->id)->value('midtrans_sandbox_client_key');

        $this->migration()->up();
        $first = DB::table('tenants')->where('id', $tenant->id)->first();
        $this->assertSame($alreadyEncrypted, $first->midtrans_sandbox_client_key);
        foreach ($plain as $column => $value) {
            $this->assertSame($value, $value === null ? $first->{$column} : Crypt::decryptString($first->{$column}));
        }
        $this->assertGreaterThan(200, strlen($first->midtrans_sandbox_server_key));
        $this->migration()->up();
        $this->assertSame($first->midtrans_sandbox_server_key, DB::table('tenants')->where('id', $tenant->id)->value('midtrans_sandbox_server_key'));

        $this->migration()->down();
        $restored = DB::table('tenants')->where('id', $tenant->id)->first();
        foreach ($plain as $column => $value) {
            $this->assertSame($value, $restored->{$column});
        }
    }

    public function test_migration_purges_legacy_and_public_cache_without_flushing_unrelated_entries(): void
    {
        $tenant = Tenant::factory()->create();
        Cache::setDefaultDriver('database');
        Cache::put('tenant:slug:retired-slug', ['midtrans_prod_server_key' => 'legacy-secret'], 3600);
        Cache::put('tenant:public:v2:'.$tenant->id, ['namabisnis' => 'stale'], 3600);
        Cache::put('unrelated-fixture', 'keep', 3600);

        $this->migration()->up();

        $this->assertNull(Cache::get('tenant:slug:retired-slug'));
        $this->assertNull(Cache::get('tenant:public:v2:'.$tenant->id));
        $this->assertSame('keep', Cache::get('unrelated-fixture'));
    }

    public function test_wrong_app_key_refuses_decryption_and_does_not_partially_restore_plaintext(): void
    {
        $tenant = Tenant::factory()->create(['midtrans_sandbox_server_key' => 'fixture-server']);
        $cipher = $tenant->getRawOriginal('midtrans_sandbox_server_key');
        $originalKey = config('app.key');
        $originalPrevious = config('app.previous_keys');
        try {
            config(['app.key' => 'base64:'.base64_encode(random_bytes(32)), 'app.previous_keys' => []]);
            $this->app->forgetInstance('encrypter');
            Crypt::clearResolvedInstance('encrypter');
            try {
                $this->migration()->down();
                $this->fail('An unavailable encryption key must stop rollback.');
            } catch (DecryptException) {
                $this->assertSame($cipher, DB::table('tenants')->where('id', $tenant->id)->value('midtrans_sandbox_server_key'));
            }
        } finally {
            config(['app.key' => $originalKey, 'app.previous_keys' => $originalPrevious]);
            $this->app->forgetInstance('encrypter');
            Crypt::clearResolvedInstance('encrypter');
        }
    }
}

<?php

namespace Tests\Feature\Payment;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformPaymentModeMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_mode_tenants_move_to_platform_and_keep_their_keys(): void
    {
        $owner = Tenant::factory()->create(['payment_mode' => 'owner', 'midtrans_sandbox_server_key' => 'SB-Mid-server-x']);
        $platform = Tenant::factory()->create(['payment_mode' => 'platform']);

        (require database_path('migrations/2026_11_01_120000_force_platform_payment_mode.php'))->up();

        $this->assertSame('platform', $owner->fresh()->payment_mode);
        $this->assertSame('SB-Mid-server-x', $owner->fresh()->midtrans_sandbox_server_key);
        $this->assertSame('platform', $platform->fresh()->payment_mode);
    }
}

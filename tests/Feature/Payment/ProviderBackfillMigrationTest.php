<?php

namespace Tests\Feature\Payment;

use App\Domain\Payment\PaymentRules;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProviderBackfillMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_rows_get_the_provider_their_evidence_shows(): void
    {
        $singapayByLink = Payment::factory()->create(['metode' => 'qris', 'payment_url' => 'https://payment-link.singapay.id/b2b/X']);
        $midtrans = Payment::factory()->create(['metode' => 'qris', 'snap_token' => 'snap-1']);
        $singapayByName = Payment::factory()->create(['metode' => 'singapay']);
        $manual = Payment::factory()->create(['metode' => 'gratis']);
        DB::table('payments')->update(['provider' => null]);

        $this->assertSame('singapay', PaymentRules::providerOf($singapayByLink->fresh()));

        (require database_path('migrations/2026_10_31_000001_backfill_payment_provider.php'))->up();

        $this->assertSame('singapay', $singapayByLink->fresh()->provider);
        $this->assertSame('midtrans', $midtrans->fresh()->provider);
        $this->assertSame('singapay', $singapayByName->fresh()->provider);
        $this->assertSame('manual', $manual->fresh()->provider);
    }
}

<?php

namespace Tests\Feature\Database;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class OwnerPayoutProcessorMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_backfill_preserves_valid_processors_and_enforces_set_null_on_delete(): void
    {
        $migration = 'database/migrations/2026_11_21_191503_add_processed_by_foreign_key_to_owner_payouts_table.php';
        $this->assertSame(0, Artisan::call('migrate:rollback', ['--path' => [$migration]]));
        $tenant = Tenant::factory()->create();
        $processor = User::factory()->create(['role' => 'admin']);
        $orphanId = $processor->id + 1000;
        $row = ['idtenant' => $tenant->id, 'jumlah' => 100000];
        $valid = DB::table('owner_payouts')->insertGetId($row + ['processed_by' => $processor->id]);
        $orphan = DB::table('owner_payouts')->insertGetId($row + ['processed_by' => $orphanId]);
        $empty = DB::table('owner_payouts')->insertGetId($row);

        Log::spy();
        $this->assertSame(0, Artisan::call('migrate', ['--path' => [$migration]]));
        Log::shouldHaveReceived('info')->once()->with('Cleared orphaned payout processor references.', ['count' => 1]);

        $this->assertDatabaseHas('owner_payouts', ['id' => $valid, 'processed_by' => $processor->id]);
        $this->assertDatabaseHas('owner_payouts', ['id' => $orphan, 'processed_by' => null]);
        $this->assertDatabaseHas('owner_payouts', ['id' => $empty, 'processed_by' => null]);

        $processor->delete();
        $this->assertDatabaseHas('owner_payouts', ['id' => $valid, 'processed_by' => null]);

        $this->expectException(QueryException::class);
        DB::table('owner_payouts')->where('id', $orphan)->update(['processed_by' => $orphanId]);
    }
}

<?php

namespace Tests\Feature\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyManageTokenRollbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_legacy_manage_token_migration_can_be_rolled_back_on_sqlite(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('SQLite-specific rollback regression.');
        }

        $migration = require database_path('migrations/2026_09_22_000001_add_manage_token_to_payments_table.php');

        $this->assertTrue(Schema::hasIndex('payments', ['manage_token'], 'unique'));
        $migration->down();
        $this->assertFalse(Schema::hasColumn('payments', 'manage_token'));
        $this->assertTrue(Schema::hasColumn('payments', 'order_id'));
    }
}

<?php

namespace Tests\Feature\Actions\Booking;

use App\Actions\Booking\CreateBooking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Proves INV-BOOK-04 on a real InnoDB engine: SQLite ignores lockForUpdate,
 * so these tests run only in the MySQL suite (phpunit.mysql.xml).
 */
#[Group('mysql')]
class CreateBookingLockingTest extends TestCase
{
    use DatabaseTruncation;

    private const string SECOND_CONNECTION = 'mysql_concurrent';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.'.self::SECOND_CONNECTION => config('database.connections.mysql')]);

        $this->beforeApplicationDestroyed(function (): void {
            DB::connection(self::SECOND_CONNECTION)->rollBack();
            DB::purge(self::SECOND_CONNECTION);
            $this->truncateTablesForAllConnections();
        });
    }

    public function test_waits_for_schedule_lock_held_by_another_transaction(): void
    {
        Mail::fake();
        Notification::fake();
        $this->travelTo(now('Asia/Jakarta')->setTime(8, 0));

        $tenant = Tenant::factory()->create();
        $service = Service::factory()->for($tenant, 'tenant')->create(['harga' => 0]);
        $schedule = Schedule::factory()->create([
            'idtenant' => $tenant->id,
            'idlayanan' => $service->id,
            'tanggal' => now('Asia/Jakarta')->addDay()->toDateString(),
        ]);

        $otherTransaction = DB::connection(self::SECOND_CONNECTION);
        $otherTransaction->beginTransaction();
        $otherTransaction->table('schedules')->where('id', $schedule->id)->lockForUpdate()->first();

        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

        try {
            app(CreateBooking::class)->execute(
                $tenant,
                $service,
                $schedule->tanggal->toDateString(),
                ['10:00'],
                [$schedule->id],
                ['namapelanggan' => 'Rani', 'nomorhp' => '081234567890', 'email' => 'rani@example.com', 'catatan' => null],
            );
            $this->fail('CreateBooking claimed a schedule that another transaction holds locked.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Lock wait timeout exceeded', $exception->getMessage());
            $this->assertMatchesRegularExpression('/^select .* from `schedules` .* for update$/', $exception->getSql());
        }
    }
}

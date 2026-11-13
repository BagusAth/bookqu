<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Schedule;
use App\Models\Service;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * BQ-151 (ADR-0017 decision 2): the migration gives every existing booking its seat 0 before
 * the seat index replaces `unique_active_booking_slot`, and the old display-only capacity
 * starts at 1 so no slot accepts more bookings than before.
 */
class BookingSeatsMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_backfill_keeps_every_existing_booking_and_resets_capacity(): void
    {
        Artisan::call('migrate:rollback', ['--step' => 2]);

        $service = Service::factory()->create();
        DB::table('services')->where('id', $service->id)->update(['kapasitas' => 8]);
        $active = Schedule::factory()->create(['idtenant' => $service->idtenant, 'idlayanan' => $service->id]);
        $freed = Schedule::factory()->create(['idtenant' => $service->idtenant, 'idlayanan' => $service->id]);
        $row = ['idtenant' => $service->idtenant, 'idlayanan' => $service->id, 'namapelanggan' => 'Rani', 'nomorhp' => '081234567890', 'email' => 'r@example.com', 'tanggalbooking' => '2026-11-03', 'jam' => '10:00:00', 'created_at' => now(), 'updated_at' => now()];
        $paid = DB::table('bookings')->insertGetId([...$row, 'idschedule' => $active->id, 'status' => 'paid']);
        $cancelled = DB::table('bookings')->insertGetId([...$row, 'idschedule' => $freed->id, 'status' => 'cancelled']);

        Artisan::call('migrate');

        $seats = DB::table('booking_seats')->orderBy('idbooking')->get();
        $this->assertSame([$paid, $cancelled], $seats->pluck('idbooking')->map(fn ($id) => (int) $id)->all());
        $this->assertSame([0, 0], $seats->pluck('seat_no')->map(fn ($s) => (int) $s)->all());
        $this->assertSame([(int) $active->id, null], $seats->pluck('active_idschedule')->map(fn ($id) => $id === null ? null : (int) $id)->all());
        $this->assertSame(1, (int) DB::table('services')->where('id', $service->id)->value('kapasitas'));
        $this->assertSame(1, (int) DB::table('bookings')->where('id', $paid)->value('peserta'));
    }
}

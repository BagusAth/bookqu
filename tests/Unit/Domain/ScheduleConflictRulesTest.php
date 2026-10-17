<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Schedule\ScheduleConflictRules;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleConflictRulesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->tenant = Tenant::factory()->create(['iduser' => $user->id]);
        $this->service = Service::factory()->create([
            'idtenant' => $this->tenant->id,
            'durasi' => 60,
            'harga' => 150000,
        ]);
    }

    public function test_time_overlap_detection(): void
    {
        $this->assertTrue(ScheduleConflictRules::hasTimeOverlap('10:00:00', '11:00:00', '10:30:00', '11:30:00'));
        $this->assertTrue(ScheduleConflictRules::hasTimeOverlap('10:00:00', '11:00:00', '10:00:00', '11:00:00'));
        $this->assertFalse(ScheduleConflictRules::hasTimeOverlap('10:00:00', '10:59:00', '11:00:00', '11:59:00'));
    }

    public function test_has_schedule_conflict_detects_existing_slot(): void
    {
        $date = Carbon::tomorrow()->toDateString();
        $schedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $date,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '10:59:00',
            'status' => 'tersedia',
        ]);

        // Same time interval on same date
        $this->assertTrue(ScheduleConflictRules::hasScheduleConflict(
            $this->tenant->id,
            $this->service->id,
            $date,
            '10:00:00',
            '10:59:00'
        ));

        // When excluding self
        $this->assertFalse(ScheduleConflictRules::hasScheduleConflict(
            $this->tenant->id,
            $this->service->id,
            $date,
            '10:00:00',
            '10:59:00',
            $schedule->id
        ));

        // Different non-overlapping time
        $this->assertFalse(ScheduleConflictRules::hasScheduleConflict(
            $this->tenant->id,
            $this->service->id,
            $date,
            '11:00:00',
            '11:59:00'
        ));
    }

    public function test_can_delete_schedule(): void
    {
        $schedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => Carbon::tomorrow()->toDateString(),
            'jam_mulai' => '14:00:00',
            'jam_selesai' => '14:59:00',
            'status' => 'tersedia',
        ]);

        $this->assertTrue(ScheduleConflictRules::canDeleteSchedule($schedule));

        $booking = Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $schedule->id,
            'namapelanggan' => 'Alice',
            'email' => 'alice@test.com',
            'nomorhp' => '0812345678',
            'tanggalbooking' => $schedule->tanggal,
            'jam' => $schedule->jam_mulai,
            'status' => 'paid',
        ]);

        $this->assertFalse(ScheduleConflictRules::canDeleteSchedule($schedule->fresh()));

        $booking->update(['status' => 'cancelled']);
        $this->assertTrue(ScheduleConflictRules::canDeleteSchedule($schedule->fresh()));
    }

    public function test_calculate_weekend_price(): void
    {
        $this->assertSame(
            180000.0,
            ScheduleConflictRules::calculateWeekendPrice($this->service, 'multiplier', 1.2)
        );

        $this->assertSame(
            200000.0,
            ScheduleConflictRules::calculateWeekendPrice($this->service, 'fixed', 200000.0)
        );

        $this->assertNull(
            ScheduleConflictRules::calculateWeekendPrice($this->service, 'none', null)
        );
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Schedule;

use App\Actions\Schedule\BulkCreateSchedules;
use App\Actions\Schedule\DeleteBlockedDate;
use App\Actions\Schedule\DeleteSchedule;
use App\Actions\Schedule\GetAvailableSchedules;
use App\Actions\Schedule\UpdateScheduleAvailability;
use App\Models\OwnerBlockedDate;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleActionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->tenant = Tenant::factory()->create(['iduser' => $user->id]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $this->service = Service::factory()->create([
            'idtenant' => $this->tenant->id,
            'is_active' => true,
            'durasi' => 60,
            'harga' => 100000,
        ]);
    }

    public function test_bulk_create_schedules_action(): void
    {
        $action = app(BulkCreateSchedules::class);
        $date = Carbon::tomorrow()->toDateString();

        $result = $action->execute($this->tenant, [
            'jenisslot' => 'harian',
            'idlayanan' => $this->service->id,
            'tanggal' => $date,
            'jammulai' => '09:00',
            'jamselesai' => '12:00',
            'intervalslot' => 60,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['created_count']);
        $this->assertSame(1, $result['days_count']);

        $this->assertDatabaseHas('schedules', [
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $date.' 00:00:00',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:59:00',
        ]);
    }

    public function test_delete_schedule_action(): void
    {
        $schedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => Carbon::tomorrow()->toDateString(),
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '10:59:00',
            'status' => 'tersedia',
        ]);

        $action = app(DeleteSchedule::class);
        $result = $action->execute($this->tenant, $schedule);

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_update_schedule_availability_action(): void
    {
        $action = app(UpdateScheduleAvailability::class);
        $blockDate = Carbon::tomorrow()->toDateString();

        $result = $action->execute($this->tenant, [
            'tanggal_block' => $blockDate,
            'alasan' => 'Renovasi studio',
            'weekend_price_type' => 'fixed',
            'weekend_price_value' => 150000,
        ]);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('owner_blocked_dates', [
            'idtenant' => $this->tenant->id,
            'tanggal' => $blockDate.' 00:00:00',
            'alasan' => 'Renovasi studio',
        ]);

        $this->tenant->refresh();
        $this->assertSame('fixed', $this->tenant->weekend_price_type);
        $this->assertEquals(150000, $this->tenant->weekend_price_value);
    }

    public function test_delete_blocked_date_action(): void
    {
        $blocked = OwnerBlockedDate::create([
            'idtenant' => $this->tenant->id,
            'tanggal' => Carbon::tomorrow()->toDateString(),
            'alasan' => 'Tutup sementara',
        ]);

        $action = app(DeleteBlockedDate::class);
        $result = $action->execute($this->tenant, $blocked->id);

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('owner_blocked_dates', ['id' => $blocked->id]);
    }

    public function test_get_available_schedules_action(): void
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

        $action = app(GetAvailableSchedules::class);

        // Date availability
        $dateAvailability = $action->getDateAvailability($this->tenant, $this->service);
        $this->assertIsArray($dateAvailability);
        $tomorrowData = collect($dateAvailability)->firstWhere('date', $date);
        $this->assertNotNull($tomorrowData);
        $this->assertSame(1, $tomorrowData['total_slots']);
        $this->assertSame(1, $tomorrowData['available_slots']);

        // Count on date
        $this->assertSame(1, $action->countAvailableSlotsOnDate($this->tenant, $this->service, $date));

        // Time slots
        $timeSlots = $action->getTimeSlots($this->tenant, $this->service, $date);
        $this->assertIsArray($timeSlots);
        $this->assertCount(1, $timeSlots);
        $this->assertTrue($timeSlots[0]['is_available']);

        // Reschedule slots
        $rescheduleSlots = $action->getSlotsForReschedule($this->tenant, $this->service->id, $date);
        $this->assertCount(1, $rescheduleSlots);
        $this->assertTrue($rescheduleSlots[0]['is_available']);
    }

    public function test_reschedule_slots_that_already_started_today_are_not_offered(): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 12:00:00', 'Asia/Jakarta'));
        $date = '2026-11-02';
        foreach (['08:00:00', '15:00:00'] as $start) {
            Schedule::factory()->create([
                'idtenant' => $this->tenant->id,
                'idlayanan' => $this->service->id,
                'tanggal' => $date,
                'jam_mulai' => $start,
                'jam_selesai' => substr($start, 0, 2).':59:00',
                'status' => 'tersedia',
            ]);
        }

        $slots = app(GetAvailableSchedules::class)->getSlotsForReschedule($this->tenant, $this->service->id, $date);

        $this->assertFalse($slots->firstWhere('jam_mulai', '08:00')['is_available']);
        $this->assertTrue($slots->firstWhere('jam_mulai', '15:00')['is_available']);
    }
}

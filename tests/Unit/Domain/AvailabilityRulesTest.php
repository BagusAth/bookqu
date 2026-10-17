<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Schedule\AvailabilityRules;
use App\Models\Booking;
use App\Models\OwnerBlockedDate;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityRulesTest extends TestCase
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
            'is_active' => true,
            'durasi' => 60,
            'harga' => 100000,
        ]);
    }

    public function test_pending_booking_grace_window(): void
    {
        Carbon::setTestNow('2026-10-01 12:00:00');

        $recent = Carbon::parse('2026-10-01 11:50:00'); // 10 minutes ago
        $expired = Carbon::parse('2026-10-01 11:40:00'); // 20 minutes ago

        $this->assertTrue(AvailabilityRules::isPendingBookingHoldingSlot($recent, 15));
        $this->assertFalse(AvailabilityRules::isPendingBookingHoldingSlot($expired, 15));
        $this->assertFalse(AvailabilityRules::isPendingBookingHoldingSlot(null, 15));

        Carbon::setTestNow();
    }

    public function test_is_slot_past(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'Asia/Jakarta'));

        $this->assertTrue(AvailabilityRules::isSlotPast('2026-10-01', '11:00:00'));
        $this->assertTrue(AvailabilityRules::isSlotPast('2026-10-01', '12:00:00'));
        $this->assertFalse(AvailabilityRules::isSlotPast('2026-10-01', '13:00:00'));
        $this->assertFalse(AvailabilityRules::isSlotPast('2026-10-02', '09:00:00'));

        Carbon::setTestNow();
    }

    public function test_slot_status_unavailable_when_schedule_status_is_tidak_tersedia(): void
    {
        $schedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => Carbon::tomorrow()->toDateString(),
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '10:59:00',
            'status' => 'tidak_tersedia',
        ]);

        $this->assertSame(AvailabilityRules::STATUS_UNAVAILABLE, AvailabilityRules::getSlotAvailabilityStatus($schedule));
        $this->assertFalse(AvailabilityRules::isSlotAvailable($schedule));
    }

    public function test_slot_status_blocked_when_date_is_blocked_by_owner(): void
    {
        $date = Carbon::tomorrow()->toDateString();
        OwnerBlockedDate::create([
            'idtenant' => $this->tenant->id,
            'tanggal' => $date,
            'alasan' => 'Libur Nasional',
        ]);

        $schedule = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $date,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '10:59:00',
            'status' => 'tersedia',
        ]);

        $this->assertSame(AvailabilityRules::STATUS_BLOCKED, AvailabilityRules::getSlotAvailabilityStatus($schedule));
        $this->assertFalse(AvailabilityRules::isSlotAvailable($schedule));
    }

    public function test_slot_status_booked_when_active_booking_exists(): void
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

        $booking = Booking::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'idschedule' => $schedule->id,
            'namapelanggan' => 'John Doe',
            'email' => 'john@example.com',
            'nomorhp' => '08123456789',
            'tanggalbooking' => $date,
            'jam' => '10:00:00',
            'status' => 'paid',
        ]);

        $this->assertSame(AvailabilityRules::STATUS_BOOKED, AvailabilityRules::getSlotAvailabilityStatus($schedule));
        $this->assertFalse(AvailabilityRules::isSlotAvailable($schedule));

        // When excluding this booking ID (for reschedule)
        $this->assertSame(
            AvailabilityRules::STATUS_AVAILABLE,
            AvailabilityRules::getSlotAvailabilityStatus($schedule, null, $booking->id)
        );
        $this->assertTrue(AvailabilityRules::isSlotAvailable($schedule, null, $booking->id));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Schedule\SlotCompatibilityRules;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotCompatibilityRulesTest extends TestCase
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
        ]);
    }

    public function test_validate_contiguous_slots(): void
    {
        $date = '2026-10-01';

        $contiguous = [
            ['jam_mulai' => '09:00:00'],
            ['jam_mulai' => '10:00:00'],
            ['jam_mulai' => '11:00:00'],
        ];

        $withGap = [
            ['jam_mulai' => '09:00:00'],
            ['jam_mulai' => '11:00:00'],
        ];

        $this->assertTrue(SlotCompatibilityRules::validateContiguousSlots($contiguous, 60, $date));
        $this->assertFalse(SlotCompatibilityRules::validateContiguousSlots($withGap, 60, $date));
    }

    public function test_are_slots_compatible(): void
    {
        $date = '2026-10-01';

        $validSlots = [
            ['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => $date],
            ['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => $date],
        ];

        $diffTenant = [
            ['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => $date],
            ['idtenant' => $this->tenant->id + 1, 'idlayanan' => $this->service->id, 'tanggal' => $date],
        ];

        $diffService = [
            ['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => $date],
            ['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id + 1, 'tanggal' => $date],
        ];

        $diffDate = [
            ['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => $date],
            ['idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'tanggal' => '2026-10-02'],
        ];

        $this->assertTrue(SlotCompatibilityRules::areSlotsCompatible($validSlots, $this->tenant->id, $this->service->id, $date));
        $this->assertFalse(SlotCompatibilityRules::areSlotsCompatible($diffTenant, $this->tenant->id, $this->service->id, $date));
        $this->assertFalse(SlotCompatibilityRules::areSlotsCompatible($diffService, $this->tenant->id, $this->service->id, $date));
        $this->assertFalse(SlotCompatibilityRules::areSlotsCompatible($diffDate, $this->tenant->id, $this->service->id, $date));
    }

    public function test_are_all_slots_available(): void
    {
        $date = Carbon::tomorrow()->toDateString();

        $slotA = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $date,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:59:00',
            'status' => 'tersedia',
        ]);

        $slotB = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $date,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '10:59:00',
            'status' => 'tersedia',
        ]);

        $this->assertTrue(SlotCompatibilityRules::areAllSlotsAvailable([$slotA, $slotB]));

        $slotB->update(['status' => 'tidak_tersedia']);
        $this->assertFalse(SlotCompatibilityRules::areAllSlotsAvailable([$slotA, $slotB->fresh()]));
    }
}

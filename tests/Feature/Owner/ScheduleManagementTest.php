<?php

namespace Tests\Feature\Owner;

use App\Models\OwnerBlockedDate;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create owner user
        $this->user = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        // Create tenant
        $this->tenant = Tenant::create([
            'iduser' => $this->user->id,
            'namabisnis' => 'Jadwal Business',
            'jenisbisnis' => 'Layanan',
            'slug' => 'jadwal-business',
            'alamat' => 'Jalan Test',
            'nomorhp' => '08123456789',
            'weekend_price_type' => 'none',
        ]);

        // Create service
        $this->service = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Layanan Default',
            'harga' => 100000,
            'durasi' => 60,
        ]);
    }

    public function test_owner_can_view_schedule_page(): void
    {
        $response = $this->actingAs($this->user)->get('/owner/schedule');
        
        $response->assertStatus(200);
    }

    public function test_owner_can_bulk_store_schedules(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        
        $response = $this->actingAs($this->user)->post('/owner/schedule/bulk-slots', [
            'jenisslot' => 'harian',
            'idlayanan' => $this->service->id,
            'tanggal' => $today,
            'jammulai' => '09:00',
            'jamselesai' => '12:00',
            'intervalslot' => 60,
        ]);

        $response->assertRedirect('/owner/schedule');
        
        // 09:00 - 10:00, 10:00 - 11:00, 11:00 - 12:00 -> 3 slots
        $this->assertDatabaseCount('schedules', 3);
        $this->assertDatabaseHas('schedules', [
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $today . ' 00:00:00', // SQLite date formatting
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00',
            'status' => 'tersedia',
        ]);
    }

    public function test_owner_can_delete_a_schedule_slot(): void
    {
        $slot = Schedule::create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:00:00',
            'status' => 'tersedia',
        ]);

        $response = $this->actingAs($this->user)->delete("/owner/schedule/slots/{$slot->id}");

        $response->assertRedirect('/owner/schedule');
        $this->assertDatabaseMissing('schedules', [
            'id' => $slot->id,
        ]);
    }

    public function test_owner_can_block_a_date_and_update_weekend_price(): void
    {
        $blockDate = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->actingAs($this->user)->post('/owner/schedule/availability', [
            'tanggal_block' => $blockDate,
            'alasan' => 'Libur Nasional',
            'weekend_price_type' => 'multiplier',
            'weekend_price_value' => 1.5,
        ]);

        $response->assertRedirect('/owner/schedule');
        
        $this->assertDatabaseHas('owner_blocked_dates', [
            'idtenant' => $this->tenant->id,
            'tanggal' => $blockDate . ' 00:00:00', // SQLite date formatting
            'alasan' => 'Libur Nasional',
        ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'weekend_price_type' => 'multiplier',
            'weekend_price_value' => 1.5,
        ]);
    }

    public function test_owner_can_delete_blocked_date(): void
    {
        $blocked = OwnerBlockedDate::create([
            'idtenant' => $this->tenant->id,
            'tanggal' => Carbon::tomorrow()->format('Y-m-d'),
            'alasan' => 'Libur',
        ]);

        $response = $this->actingAs($this->user)->delete("/owner/schedule/blocked-dates/{$blocked->id}");

        $response->assertRedirect('/owner/schedule');
        $this->assertDatabaseMissing('owner_blocked_dates', [
            'id' => $blocked->id,
        ]);
    }
}

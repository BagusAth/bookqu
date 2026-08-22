<?php

namespace Tests\Feature\Owner;

use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an owner user
        $this->user = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(), // Need verified to pass middleware
        ]);

        // Create a tenant for the owner
        $this->tenant = Tenant::create([
            'iduser' => $this->user->id,
            'namabisnis' => 'My Booking Business',
            'jenisbisnis' => 'Klinik',
            'slug' => 'my-business',
            'alamat' => 'Jalan Test',
            'nomorhp' => '08123456789',
            'deskripsi' => 'Testing business',
        ]);
    }

    public function test_owner_can_view_programs_page(): void
    {
        $response = $this->actingAs($this->user)->get('/owner/programs');
        
        $response->assertStatus(200);
    }

    public function test_owner_can_create_a_program(): void
    {
        $response = $this->actingAs($this->user)->post('/owner/programs', [
            'namalayanan' => 'Layanan Test 1',
            'harga' => 150000,
            'durasi' => 60,
            'deskripsi' => 'Deskripsi layanan test',
        ]);

        $response->assertRedirect('/owner/programs');
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('services', [
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Layanan Test 1',
            'harga' => 150000,
            'durasi' => 60,
        ]);
    }

    public function test_owner_can_update_a_program(): void
    {
        $service = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'Old Service',
            'harga' => 100000,
            'durasi' => 30,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->put("/owner/programs/{$service->id}", [
            'namalayanan' => 'Updated Service',
            'harga' => 200000,
            'durasi' => 45,
            'deskripsi' => 'Updated deskripsi',
            'is_active' => false, // Turning it off
        ]);

        $response->assertRedirect('/owner/programs');
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'namalayanan' => 'Updated Service',
            'harga' => 200000,
            'durasi' => 45,
            'is_active' => 0,
        ]);
    }

    public function test_owner_can_delete_a_program(): void
    {
        $service = Service::create([
            'idtenant' => $this->tenant->id,
            'namalayanan' => 'To Be Deleted',
            'harga' => 50000,
            'durasi' => 15,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->delete("/owner/programs/{$service->id}");

        $response->assertRedirect('/owner/programs');
        $response->assertSessionHas('sukses');

        $this->assertDatabaseMissing('services', [
            'id' => $service->id,
        ]);
    }
}

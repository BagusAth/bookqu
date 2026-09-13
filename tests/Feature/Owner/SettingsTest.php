<?php

namespace Tests\Feature\Owner;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->tenant = Tenant::factory()->create([
            'iduser' => $this->user->id,
            'namabisnis' => 'Test Business',
            'saldo_platform' => 500000,
        ]);
    }

    public function test_owner_can_view_settings_page(): void
    {
        $response = $this->actingAs($this->user)->get('/owner/settings');
        $response->assertStatus(200);
        $response->assertSee('Business Profile');
    }

    public function test_owner_can_update_business_profile(): void
    {
        $response = $this->actingAs($this->user)->post('/owner/settings/profile', [
            'namabisnis' => 'Updated Business Name',
            'jenisbisnis' => 'Barbershop',
            'alamat' => 'Jl. Baru No. 45',
            'nomorhp' => '08987654321',
            'deskripsi' => 'Deskripsi baru',
        ]);

        $response->assertRedirect('/owner/settings');
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'namabisnis' => 'Updated Business Name',
            'jenisbisnis' => 'Barbershop',
        ]);
    }

    public function test_owner_can_update_payment_settings(): void
    {
        $response = $this->actingAs($this->user)->post('/owner/settings/payment', [
            'payment_mode' => 'owner',
            'midtrans_environment' => 'sandbox',
            'midtrans_sandbox_merchant_id' => 'G123456789',
            'midtrans_sandbox_client_key' => 'SB-Mid-client-XXXX',
            'midtrans_sandbox_server_key' => 'SB-Mid-server-YYYY',
        ]);

        $response->assertRedirect('/owner/settings');
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'payment_mode' => 'owner',
            'midtrans_environment' => 'sandbox',
            'midtrans_sandbox_merchant_id' => 'G123456789',
        ]);
    }

    public function test_owner_can_request_payout(): void
    {
        $response = $this->actingAs($this->user)->post('/owner/payouts', [
            'jumlah' => 200000,
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'John Doe',
        ]);

        $response->assertRedirect('/owner/settings');
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('owner_payouts', [
            'idtenant' => $this->tenant->id,
            'jumlah' => 200000,
            'status' => 'pending',
        ]);

        // Saldo deducted
        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'saldo_platform' => 300000,
        ]);
    }
}

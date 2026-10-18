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

    public function test_owner_cannot_request_payout_above_available_balance(): void
    {
        $response = $this->actingAs($this->user)->post('/owner/payouts', [
            'jumlah' => 600000,
        ]);

        $response->assertSessionHasErrors('jumlah');
        $this->assertDatabaseMissing('owner_payouts', ['idtenant' => $this->tenant->id]);
        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'saldo_platform' => 500000,
        ]);
    }

    public function test_owner_cannot_request_payout_with_more_than_two_decimal_places(): void
    {
        $response = $this->actingAs($this->user)->post('/owner/payouts', [
            'jumlah' => 10000.001,
        ]);

        $response->assertSessionHasErrors('jumlah');
        $this->assertDatabaseMissing('owner_payouts', ['idtenant' => $this->tenant->id]);
        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'saldo_platform' => 500000,
        ]);
    }

    public function test_owner_cannot_delete_account_without_matching_confirmation(): void
    {
        $response = $this->actingAs($this->user)->delete('/owner/settings/account', [
            'confirm_account' => 'wrong-input@example.com',
        ]);

        $response->assertSessionHasErrors('confirm_account');
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
    }

    public function test_owner_can_delete_account_with_matching_email_confirmation(): void
    {
        $userId = $this->user->id;
        $tenantId = $this->tenant->id;

        $response = $this->actingAs($this->user)->delete('/owner/settings/account', [
            'confirm_account' => $this->user->email,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('sukses');
        $this->assertGuest();

        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseMissing('tenants', ['id' => $tenantId]);
    }

    public function test_owner_can_complete_profile_with_normalized_phone(): void
    {
        $newOwner = User::factory()->create([
            'role'              => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($newOwner)->post('/owner/profile/complete', [
            'namabisnis'  => 'Studio Cantik Alami',
            'jenisbisnis' => 'Kecantikan',
            'nomorhp'     => '+62 812 3456 7890',
            'alamat'      => 'Jl. Flamboyan No. 10',
        ]);

        $response->assertRedirect(route('owner.dashboard'));
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('tenants', [
            'iduser'     => $newOwner->id,
            'namabisnis' => 'Studio Cantik Alami',
            'nomorhp'    => '081234567890',
        ]);
    }

    public function test_phone_normalization_handles_various_formats(): void
    {
        $formats = [
            '+62081234567891' => '081234567891',
            '6281234567892'   => '081234567892',
            '81234567893'     => '081234567893',
            '0812-3456-7894'  => '081234567894',
        ];

        foreach ($formats as $inputPhone => $expectedPhone) {
            $user = User::factory()->create([
                'role'              => 'owner',
                'email_verified_at' => now(),
            ]);

            $this->actingAs($user)->post('/owner/profile/complete', [
                'namabisnis'  => 'Bisnis ' . uniqid(),
                'jenisbisnis' => 'Jasa',
                'nomorhp'     => $inputPhone,
                'alamat'      => 'Jl. Contoh No. 1',
            ])->assertRedirect(route('owner.dashboard'));

            $this->assertDatabaseHas('tenants', [
                'iduser'  => $user->id,
                'nomorhp' => $expectedPhone,
            ]);
        }
    }

    public function test_owner_profile_completion_validation_failure_preserves_old_input(): void
    {
        $newOwner = User::factory()->create([
            'role'              => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($newOwner)->from('/owner/dashboard')->post('/owner/profile/complete', [
            'namabisnis'  => '',
            'jenisbisnis' => 'Kecantikan',
            'nomorhp'     => '081234567890',
            'alamat'      => 'Jl. Flamboyan No. 10',
        ]);

        $response->assertRedirect('/owner/dashboard');
        $response->assertSessionHasErrors('namabisnis');
    }
}

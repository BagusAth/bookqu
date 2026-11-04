<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Owner\DeleteOwnerAccount;
use App\Models\OwnerPayout;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeleteAccountWithOpenFundsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'owner',
            'email' => 'owner@example.com',
            'email_verified_at' => now(),
        ]);

        $this->tenant = Tenant::factory()->create([
            'iduser' => $this->user->id,
            'namabisnis' => 'Klinik Sehat',
            'saldo_platform' => 0,
            'payment_mode' => 'platform',
        ]);
    }

    public function test_deletion_rejected_when_tenant_has_positive_balance(): void
    {
        $this->tenant->update(['saldo_platform' => 50000]);

        $response = $this->actingAs($this->user)->delete('/owner/settings/account', [
            'confirm_account' => 'owner@example.com',
            'delete_password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'confirm_account' => 'Tarik saldo Anda terlebih dahulu sebelum menghapus akun.',
        ]);
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
    }

    public function test_deletion_rejected_when_tenant_has_negative_balance(): void
    {
        $this->tenant->update(['saldo_platform' => -25000]);

        $response = $this->actingAs($this->user)->delete('/owner/settings/account', [
            'confirm_account' => 'owner@example.com',
            'delete_password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'confirm_account' => 'Akun tidak dapat dihapus karena saldo minus. Hubungi admin BookQu.',
        ]);
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
    }

    public function test_deletion_rejected_when_tenant_has_pending_payout(): void
    {
        $this->tenant->update(['saldo_platform' => 0]);

        OwnerPayout::create([
            'idtenant' => $this->tenant->id,
            'jumlah' => 100000,
            'status' => 'pending',
            'requested_at' => now(),
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner',
        ]);

        $response = $this->actingAs($this->user)->delete('/owner/settings/account', [
            'confirm_account' => 'owner@example.com',
            'delete_password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'confirm_account' => 'Masih ada pengajuan withdraw yang sedang diproses.',
        ]);
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
    }

    public function test_deletion_rejected_when_tenant_has_approved_payout(): void
    {
        $this->tenant->update(['saldo_platform' => 0]);

        OwnerPayout::create([
            'idtenant' => $this->tenant->id,
            'jumlah' => 100000,
            'status' => 'approved',
            'requested_at' => now(),
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner',
        ]);

        $response = $this->actingAs($this->user)->delete('/owner/settings/account', [
            'confirm_account' => 'owner@example.com',
            'delete_password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'confirm_account' => 'Masih ada pengajuan withdraw yang sedang diproses.',
        ]);
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
    }

    public function test_deletion_allowed_when_balance_is_zero_and_all_payouts_are_final(): void
    {
        $this->tenant->update(['saldo_platform' => 0]);

        foreach (['success', 'failed', 'rejected'] as $status) {
            OwnerPayout::create([
                'idtenant' => $this->tenant->id,
                'jumlah' => 50000,
                'status' => $status,
                'requested_at' => now(),
                'bank_nama' => 'BCA',
                'nomor_rekening' => '1234567890',
                'nama_pemilik_rekening' => 'Owner',
            ]);
        }

        $userId = $this->user->id;
        $tenantId = $this->tenant->id;

        $response = $this->actingAs($this->user)->delete('/owner/settings/account', [
            'confirm_account' => 'owner@example.com',
            'delete_password' => 'password',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('sukses');
        $this->assertGuest();

        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseMissing('tenants', ['id' => $tenantId]);
    }

    public function test_action_directly_throws_validation_exception_on_open_funds(): void
    {
        $action = app(DeleteOwnerAccount::class);

        // Positive balance
        $this->tenant->update(['saldo_platform' => 100000]);
        try {
            $action->execute($this->user, $this->tenant, 'owner@example.com');
            $this->fail('Expected ValidationException for positive balance');
        } catch (ValidationException $e) {
            $this->assertSame('Tarik saldo Anda terlebih dahulu sebelum menghapus akun.', $e->errors()['confirm_account'][0]);
        }

        // Negative balance
        $this->tenant->update(['saldo_platform' => -50000]);
        try {
            $action->execute($this->user, $this->tenant, 'owner@example.com');
            $this->fail('Expected ValidationException for negative balance');
        } catch (ValidationException $e) {
            $this->assertSame('Akun tidak dapat dihapus karena saldo minus. Hubungi admin BookQu.', $e->errors()['confirm_account'][0]);
        }

        // In-flight payout
        $this->tenant->update(['saldo_platform' => 0]);
        OwnerPayout::create([
            'idtenant' => $this->tenant->id,
            'jumlah' => 20000,
            'status' => 'pending',
            'requested_at' => now(),
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner',
        ]);
        try {
            $action->execute($this->user, $this->tenant, 'owner@example.com');
            $this->fail('Expected ValidationException for open payout');
        } catch (ValidationException $e) {
            $this->assertSame('Masih ada pengajuan withdraw yang sedang diproses.', $e->errors()['confirm_account'][0]);
        }
    }
}

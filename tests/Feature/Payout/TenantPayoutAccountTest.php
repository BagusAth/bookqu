<?php

declare(strict_types=1);

namespace Tests\Feature\Payout;

use App\Models\OwnerPayout;
use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use App\Models\User;
use App\Notifications\PayoutAccountChangedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantPayoutAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        Notification::fake();

        config(['modules.balance' => 'released']);

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'email' => 'owner@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('secret123'),
        ]);

        $this->tenant = Tenant::factory()->create([
            'iduser' => $this->owner->id,
            'saldo_platform' => 500000,
            'payment_mode' => 'platform',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    public function test_owner_can_store_payout_account_with_current_password(): void
    {
        $response = $this->actingAs($this->owner)->post('/owner/payout-accounts', [
            'bank_kode' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Pemilik Toko',
            'current_password' => 'secret123',
        ]);

        $response->assertRedirect(route('owner.settings.balance'));
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('tenant_payout_accounts', [
            'idtenant' => $this->tenant->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'Bank Central Asia (BCA)',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Pemilik Toko',
            'is_default' => 1,
        ]);

        Notification::assertSentTo($this->owner, PayoutAccountChangedNotification::class, function ($n) {
            return $n->actionType === 'ditambahkan' && $n->maskedNumber === '••••7890';
        });
    }

    public function test_owner_cannot_store_payout_account_with_wrong_password(): void
    {
        $response = $this->actingAs($this->owner)->post('/owner/payout-accounts', [
            'bank_kode' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Pemilik Toko',
            'current_password' => 'wrongpass',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertDatabaseCount('tenant_payout_accounts', 0);
    }

    public function test_owner_cannot_store_more_than_max_allowed_accounts(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            TenantPayoutAccount::create([
                'idtenant' => $this->tenant->id,
                'bank_kode' => 'BCA',
                'bank_nama' => 'Bank Central Asia (BCA)',
                'nomor_rekening' => '123456789'.$i,
                'nama_pemilik_rekening' => 'Pemilik Toko',
                'is_default' => $i === 1,
                'changed_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->owner)->post('/owner/payout-accounts', [
            'bank_kode' => 'BNI',
            'nomor_rekening' => '9876543210',
            'nama_pemilik_rekening' => 'Pemilik Toko',
            'current_password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('rekening');
        $this->assertDatabaseCount('tenant_payout_accounts', 3);
    }

    public function test_owner_can_update_payout_account(): void
    {
        $account = TenantPayoutAccount::create([
            'idtenant' => $this->tenant->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'Bank Central Asia (BCA)',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Nama Lama',
            'is_default' => true,
            'changed_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($this->owner)->put('/owner/payout-accounts/'.$account->id, [
            'bank_kode' => 'MANDIRI',
            'nomor_rekening' => '555566667777',
            'nama_pemilik_rekening' => 'Nama Baru',
            'current_password' => 'secret123',
        ]);

        $response->assertRedirect(route('owner.settings.balance'));
        $this->assertDatabaseHas('tenant_payout_accounts', [
            'id' => $account->id,
            'bank_kode' => 'MANDIRI',
            'bank_nama' => 'Bank Mandiri',
            'nomor_rekening' => '555566667777',
            'nama_pemilik_rekening' => 'Nama Baru',
        ]);

        $this->assertTrue($account->fresh()->changed_at->isToday());

        Notification::assertSentTo($this->owner, PayoutAccountChangedNotification::class, function ($n) {
            return $n->actionType === 'diubah' && $n->maskedNumber === '••••7777';
        });
    }

    public function test_owner_can_set_account_as_default_without_altering_changed_at(): void
    {
        $time1 = now()->subDays(5);
        $time2 = now()->subDays(2);

        $acc1 = TenantPayoutAccount::create([
            'idtenant' => $this->tenant->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1111111111',
            'nama_pemilik_rekening' => 'Owner',
            'is_default' => true,
            'changed_at' => $time1,
        ]);

        $acc2 = TenantPayoutAccount::create([
            'idtenant' => $this->tenant->id,
            'bank_kode' => 'BRI',
            'bank_nama' => 'BRI',
            'nomor_rekening' => '2222222222',
            'nama_pemilik_rekening' => 'Owner',
            'is_default' => false,
            'changed_at' => $time2,
        ]);

        $response = $this->actingAs($this->owner)->post('/owner/payout-accounts/'.$acc2->id.'/default');
        $response->assertRedirect(route('owner.settings.balance'));

        $this->assertFalse((bool) $acc1->fresh()->is_default);
        $this->assertTrue((bool) $acc2->fresh()->is_default);
        $this->assertEquals($time2->toDateTimeString(), $acc2->fresh()->changed_at->toDateTimeString());
    }

    public function test_cross_tenant_access_returns_404(): void
    {
        $otherOwner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now(), 'password' => Hash::make('secret123')]);
        $otherTenant = Tenant::factory()->create(['iduser' => $otherOwner->id, 'saldo_platform' => 100000]);

        $otherAccount = TenantPayoutAccount::create([
            'idtenant' => $otherTenant->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '9999999999',
            'nama_pemilik_rekening' => 'Other',
            'is_default' => true,
            'changed_at' => now(),
        ]);

        // Attempt update
        $this->actingAs($this->owner)->put('/owner/payout-accounts/'.$otherAccount->id, [
            'bank_kode' => 'BRI',
            'nomor_rekening' => '1111222233',
            'nama_pemilik_rekening' => 'Hacker',
            'current_password' => 'secret123',
        ])->assertNotFound();

        // Attempt delete
        $this->actingAs($this->owner)->delete('/owner/payout-accounts/'.$otherAccount->id, [
            'current_password' => 'secret123',
        ])->assertNotFound();

        // Attempt default
        $this->actingAs($this->owner)->post('/owner/payout-accounts/'.$otherAccount->id.'/default')->assertNotFound();

        // Attempt payout using other tenant's account
        $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 50000,
            'payout_account_id' => $otherAccount->id,
        ])->assertSessionHasErrors('payout_account_id');
    }

    public function test_cannot_delete_payout_account_attached_to_in_flight_payout(): void
    {
        $account = TenantPayoutAccount::create([
            'idtenant' => $this->tenant->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner',
            'is_default' => true,
            'changed_at' => now(),
        ]);

        OwnerPayout::create([
            'idtenant' => $this->tenant->id,
            'request_key' => (string) Str::uuid(),
            'payout_account_id' => $account->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner',
            'jumlah' => 100000,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($this->owner)->delete('/owner/payout-accounts/'.$account->id, [
            'current_password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('rekening');
        $this->assertDatabaseHas('tenant_payout_accounts', ['id' => $account->id, 'deleted_at' => null]);
    }

    public function test_owner_can_delete_payout_account_with_password(): void
    {
        $acc1 = TenantPayoutAccount::create([
            'idtenant' => $this->tenant->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1111111111',
            'nama_pemilik_rekening' => 'Owner',
            'is_default' => true,
            'changed_at' => now()->subDays(2),
        ]);

        $acc2 = TenantPayoutAccount::create([
            'idtenant' => $this->tenant->id,
            'bank_kode' => 'BRI',
            'bank_nama' => 'BRI',
            'nomor_rekening' => '2222222222',
            'nama_pemilik_rekening' => 'Owner',
            'is_default' => false,
            'changed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->owner)->delete('/owner/payout-accounts/'.$acc1->id, [
            'current_password' => 'secret123',
        ]);

        $response->assertRedirect(route('owner.settings.balance'));
        $this->assertSoftDeleted('tenant_payout_accounts', ['id' => $acc1->id]);

        // acc2 promoted to default
        $this->assertTrue((bool) $acc2->fresh()->is_default);

        Notification::assertSentTo($this->owner, PayoutAccountChangedNotification::class, function ($n) {
            return $n->actionType === 'dihapus' && $n->maskedNumber === '••••1111';
        });
    }

    public function test_payout_snapshot_does_not_change_when_account_is_later_updated_or_deleted(): void
    {
        $account = TenantPayoutAccount::create([
            'idtenant' => $this->tenant->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'Bank Central Asia (BCA)',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Nama Asli',
            'is_default' => true,
            'changed_at' => now(),
        ]);

        $response = $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 100000,
            'payout_account_id' => $account->id,
        ]);
        $response->assertRedirect(route('owner.settings.balance'));

        $payout = OwnerPayout::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->firstOrFail();
        $this->assertEquals('1234567890', $payout->nomor_rekening);
        $this->assertEquals('Nama Asli', $payout->nama_pemilik_rekening);
        $this->assertEquals('BCA', $payout->bank_kode);

        // Update the account
        $account->update([
            'nomor_rekening' => '9999999999',
            'nama_pemilik_rekening' => 'Nama Berubah',
        ]);

        // Historical payout snapshot remains completely unchanged
        $freshPayout = $payout->fresh();
        $this->assertEquals('1234567890', $freshPayout->nomor_rekening);
        $this->assertEquals('Nama Asli', $freshPayout->nama_pemilik_rekening);
    }

    public function test_admin_displays_new_account_badge_when_changed_recently(): void
    {
        $recentAccount = TenantPayoutAccount::create([
            'idtenant' => $this->tenant->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'Bank Central Asia (BCA)',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner',
            'is_default' => true,
            'changed_at' => now()->subHours(2),
        ]);

        OwnerPayout::create([
            'idtenant' => $this->tenant->id,
            'request_key' => (string) Str::uuid(),
            'payout_account_id' => $recentAccount->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'Bank Central Asia (BCA)',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner',
            'jumlah' => 100000,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/payouts');
        $response->assertOk();
        $response->assertSee('Rekening baru (&lt; 24 jam)', false);
    }
}

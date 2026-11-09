<?php

declare(strict_types=1);

namespace Tests\Feature\Payout;

use App\Actions\Owner\RequestOwnerPayout;
use App\Domain\Payout\PayoutEligibility;
use App\Models\OwnerPayout;
use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayoutEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private TenantPayoutAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 10:00:00', 'Asia/Jakarta'));

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->tenant = Tenant::factory()->create([
            'iduser' => $this->owner->id,
            'namabisnis' => 'Barber Pro',
            'slug' => 'barber-pro',
            'jenisbisnis' => 'Barbershop',
            'nomorhp' => '08123456789',
            'payment_mode' => 'platform',
            'saldo_platform' => 500000,
        ]);

        $this->account = TenantPayoutAccount::create([
            'idtenant' => $this->tenant->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'Bank Central Asia (BCA)',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner Name',
            'is_default' => true,
            'changed_at' => now(),
        ]);
    }

    public function test_unverified_email_is_first_rejection_reason(): void
    {
        $this->owner->forceFill(['email_verified_at' => null])->save();

        $reasons = PayoutEligibility::reasons($this->tenant, $this->owner);
        $this->assertNotEmpty($reasons);
        $this->assertSame('Verifikasi e-mail Anda terlebih dahulu.', $reasons[0]);

        // Action throws ValidationException directly
        try {
            app(RequestOwnerPayout::class)->execute(
                $this->tenant,
                50000,
                $this->account,
                (string) Str::uuid(),
                $this->owner
            );
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertSame('Verifikasi e-mail Anda terlebih dahulu.', $e->errors()['jumlah'][0]);
        }

        // HTTP request is blocked by verified middleware
        $response = $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 50000,
            'payout_account_id' => $this->account->id,
        ]);
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_incomplete_profile_is_rejection_reason(): void
    {
        $this->tenant->update(['nomorhp' => '']);

        $reasons = PayoutEligibility::reasons($this->tenant, $this->owner);
        $this->assertContains('Lengkapi profil bisnis terlebih dahulu.', $reasons);

        $response = $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 50000,
            'payout_account_id' => $this->account->id,
        ]);

        $response->assertSessionHasErrors(['jumlah' => 'Lengkapi profil bisnis terlebih dahulu.']);
    }

    public function test_owner_payment_mode_is_rejection_reason(): void
    {
        $this->tenant->update(['payment_mode' => 'owner']);

        $reasons = PayoutEligibility::reasons($this->tenant, $this->owner);
        $this->assertContains('Withdraw hanya tersedia untuk mode pembayaran platform.', $reasons);

        $response = $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 50000,
            'payout_account_id' => $this->account->id,
        ]);

        $response->assertSessionHasErrors(['jumlah' => 'Withdraw hanya tersedia untuk mode pembayaran platform.']);
    }

    public function test_zero_or_negative_balance_is_rejection_reason(): void
    {
        $this->tenant->update(['saldo_platform' => 0]);

        $reasons = PayoutEligibility::reasons($this->tenant, $this->owner);
        $this->assertContains('Saldo belum tersedia untuk ditarik.', $reasons);

        $response = $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 50000,
            'payout_account_id' => $this->account->id,
        ]);

        $response->assertSessionHasErrors(['jumlah' => 'Saldo belum tersedia untuk ditarik.']);
    }

    public function test_open_in_flight_payout_blocks_subsequent_request(): void
    {
        config(['payouts.max_open' => 1]);

        OwnerPayout::create([
            'idtenant' => $this->tenant->id,
            'request_key' => (string) Str::uuid(),
            'payout_account_id' => $this->account->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner Name',
            'jumlah' => 50000,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $reasons = PayoutEligibility::reasons($this->tenant, $this->owner);
        $this->assertContains('Masih ada pengajuan withdraw yang sedang diproses. Tunggu sampai selesai sebelum mengajukan lagi.', $reasons);

        $response = $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 50000,
            'payout_account_id' => $this->account->id,
        ]);

        $response->assertSessionHasErrors(['jumlah' => 'Masih ada pengajuan withdraw yang sedang diproses. Tunggu sampai selesai sebelum mengajukan lagi.']);
    }

    public function test_no_saved_payout_accounts_blocks_request(): void
    {
        $this->account->delete();

        $reasons = PayoutEligibility::reasons($this->tenant, $this->owner);
        $this->assertContains('Tambahkan rekening penarikan terlebih dahulu.', $reasons);
    }

    public function test_fully_eligible_tenant_returns_empty_reasons(): void
    {
        $this->assertTrue(PayoutEligibility::isEligible($this->tenant, $this->owner));
        $this->assertSame([], PayoutEligibility::reasons($this->tenant, $this->owner));
    }

    public function test_request_payout_validates_integer_amount(): void
    {
        $response = $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 15000.75,
            'payout_account_id' => $this->account->id,
        ]);

        $response->assertSessionHasErrors(['jumlah' => 'Nominal withdraw harus berupa angka bulat.']);
    }

    public function test_request_payout_validates_minimum_amount(): void
    {
        $response = $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 9999,
            'payout_account_id' => $this->account->id,
        ]);

        $response->assertSessionHasErrors(['jumlah' => 'Nominal withdraw minimal Rp 10.000.']);
    }

    public function test_max_amount_per_request_limit_enforced(): void
    {
        config(['payouts.max_amount_per_request' => 300000]);

        $response = $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 400000,
            'payout_account_id' => $this->account->id,
        ]);

        $response->assertSessionHasErrors(['jumlah' => 'Nominal melebihi batas Rp 300.000 per pengajuan.']);
        $this->assertDatabaseMissing('owner_payouts', ['idtenant' => $this->tenant->id]);

        // Exact limit is accepted
        $validResponse = $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 300000,
            'payout_account_id' => $this->account->id,
        ]);
        $validResponse->assertRedirect(route('owner.payments', ['tab' => 'saldo']));
        $this->assertDatabaseHas('owner_payouts', ['idtenant' => $this->tenant->id, 'jumlah' => 300000]);
    }

    public function test_max_requests_per_day_limit_enforced(): void
    {
        config(['payouts.max_open' => null]);
        config(['payouts.max_requests_per_day' => 2]);

        // Payout yesterday does not count towards today's quota
        OwnerPayout::create([
            'idtenant' => $this->tenant->id,
            'request_key' => (string) Str::uuid(),
            'payout_account_id' => $this->account->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner Name',
            'jumlah' => 50000,
            'status' => 'pending',
            'requested_at' => now('Asia/Jakarta')->subDay(),
        ]);

        // 1st request today
        $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 50000,
            'payout_account_id' => $this->account->id,
        ])->assertRedirect(route('owner.payments', ['tab' => 'saldo']));

        // 2nd request today
        $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 50000,
            'payout_account_id' => $this->account->id,
        ])->assertRedirect(route('owner.payments', ['tab' => 'saldo']));

        // 3rd request today hits daily limit
        $response = $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 50000,
            'payout_account_id' => $this->account->id,
        ]);

        $response->assertSessionHasErrors(['jumlah' => 'Batas 2 pengajuan withdraw per hari sudah tercapai.']);
    }

    public function test_balance_view_displays_eligibility_rejection_notice(): void
    {
        $this->tenant->update(['saldo_platform' => 0]);

        $response = $this->actingAs($this->owner)->get(route('owner.payments', ['tab' => 'saldo']));
        $response->assertOk();
        $response->assertSee('Belum dapat mengajukan pencairan');
        $response->assertSee('Saldo belum tersedia untuk ditarik.');
    }
}

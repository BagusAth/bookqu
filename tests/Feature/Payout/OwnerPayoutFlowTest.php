<?php

namespace Tests\Feature\Payout;

use App\Actions\Payment\PaymentLifecycle;
use App\Domain\Payment\PaymentTransition;
use App\Models\Booking;
use App\Models\OwnerPayout;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use App\Models\User;
use App\Notifications\OwnerPayoutStatusChangedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * BQ-015: balance crediting (fee 0%), refund debit, payout request and admin review.
 */
class OwnerPayoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private User $admin;

    private TenantPayoutAccount $payoutAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        Mail::fake();
        Notification::fake();

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id, 'saldo_platform' => 0, 'payment_mode' => 'platform']);
        $this->admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);

        $this->payoutAccount = TenantPayoutAccount::create([
            'idtenant' => $this->tenant->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Pemilik',
            'is_default' => true,
            'changed_at' => now(),
        ]);
    }

    public function test_provider_success_credits_the_full_amount_once(): void
    {
        $payment = $this->pendingBookingPayment(150000);

        app(PaymentLifecycle::class)->transition($payment, PaymentTransition::ProviderSucceeded);
        app(PaymentLifecycle::class)->transition($payment->fresh(), PaymentTransition::ProviderSucceeded);

        $this->assertEquals(150000, (float) $this->tenant->fresh()->saldo_platform);
        $this->assertNotNull($payment->fresh()->balance_credited_at);
    }

    public function test_failed_or_owner_settled_payments_credit_nothing(): void
    {
        app(PaymentLifecycle::class)->transition($this->pendingBookingPayment(100000), PaymentTransition::ProviderFailed);
        app(PaymentLifecycle::class)->transition($this->pendingBookingPayment(100000), PaymentTransition::SettledByOwner);

        $this->assertEquals(0, (float) $this->tenant->fresh()->saldo_platform);
    }

    public function test_refund_of_a_credited_payment_is_debited(): void
    {
        $payment = $this->pendingBookingPayment(120000);
        app(PaymentLifecycle::class)->transition($payment, PaymentTransition::ProviderSucceeded);
        $booking = Booking::withoutGlobalScopes()->where('idpayment', $payment->id)->firstOrFail();
        $booking->update(['status' => 'cancelled']);

        app(PaymentLifecycle::class)->refundCancelledBooking($booking->fresh());

        $this->assertEquals(0, (float) $this->tenant->fresh()->saldo_platform);
    }

    public function test_owner_request_holds_the_amount_and_stores_the_bank_account(): void
    {
        $this->tenant->update(['saldo_platform' => 300000]);

        $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 100000,
            'payout_account_id' => $this->payoutAccount->id,
        ])->assertRedirect(route('owner.settings.balance'));

        $this->assertDatabaseHas('owner_payouts', ['idtenant' => $this->tenant->id, 'status' => 'pending', 'nomor_rekening' => '1234567890']);
        $this->assertEquals(200000, (float) $this->tenant->fresh()->saldo_platform);
        $this->actingAs($this->owner)->get('/owner/settings/balance')->assertOk()->assertSee('id="withdraw-form"', false);
    }

    public function test_owner_request_requires_a_valid_bank_account(): void
    {
        $this->tenant->update(['saldo_platform' => 300000]);

        $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 100000,
            'payout_account_id' => 999999,
        ])->assertSessionHasErrors(['payout_account_id']);
        $this->assertDatabaseCount('owner_payouts', 0);
    }

    public function test_payout_history_is_paginated_to_15_records_per_page(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            OwnerPayout::create([
                'idtenant' => $this->tenant->id,
                'request_key' => (string) Str::uuid(),
                'jumlah' => 10000 + ($i * 1000),
                'status' => 'pending',
                'requested_at' => now()->subMinutes(20 - $i),
                'bank_nama' => 'BCA',
                'nomor_rekening' => '1234567890',
                'nama_pemilik_rekening' => 'Owner',
            ]);
        }

        $responsePage1 = $this->actingAs($this->owner)->get('/owner/settings/balance');
        $responsePage1->assertOk();
        $this->assertCount(15, $responsePage1->viewData('payouts'));

        $responsePage2 = $this->actingAs($this->owner)->get('/owner/settings/balance?page=2');
        $responsePage2->assertOk();
        $this->assertCount(1, $responsePage2->viewData('payouts'));
    }

    public function test_payout_request_is_idempotent_with_same_request_key(): void
    {
        $this->tenant->update(['saldo_platform' => 300000]);
        $requestKey = (string) Str::uuid();

        $payload = [
            'request_key' => $requestKey,
            'jumlah' => 100000,
            'payout_account_id' => $this->payoutAccount->id,
        ];

        $this->actingAs($this->owner)->post('/owner/payouts', $payload)->assertRedirect(route('owner.settings.balance'));
        $this->assertEquals(200000, (float) $this->tenant->fresh()->saldo_platform);
        $this->assertDatabaseCount('owner_payouts', 1);

        // Second POST with same request_key
        $this->actingAs($this->owner)->post('/owner/payouts', $payload)->assertRedirect(route('owner.settings.balance'));
        $this->assertEquals(200000, (float) $this->tenant->fresh()->saldo_platform);
        $this->assertDatabaseCount('owner_payouts', 1);
    }

    public function test_payout_requests_with_different_request_keys_create_distinct_payouts(): void
    {
        $this->tenant->update(['saldo_platform' => 300000]);

        $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 100000,
            'payout_account_id' => $this->payoutAccount->id,
        ])->assertRedirect(route('owner.settings.balance'));

        $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 50000,
            'payout_account_id' => $this->payoutAccount->id,
        ])->assertRedirect(route('owner.settings.balance'));

        $this->assertEquals(150000, (float) $this->tenant->fresh()->saldo_platform);
        $this->assertDatabaseCount('owner_payouts', 2);
    }

    public function test_balance_view_renders_request_key_and_alpine_double_submit_protection(): void
    {
        $response = $this->actingAs($this->owner)->get('/owner/settings/balance');
        $response->assertOk();
        $response->assertSee('name="request_key"', false);
        $response->assertSee('x-data="{ submitting: false }"', false);
        $response->assertSee(':disabled="submitting"', false);
        $response->assertSee('Memproses…');
        $response->assertSee('Riwayat Withdraw');
        $response->assertDontSee('Riwayat Penarikan Dana Terbaru');
    }

    public function test_admin_approves_then_records_the_transfer_with_proof(): void
    {
        Storage::fake('local');
        $payout = $this->payout(100000);

        $this->actingAs($this->admin)->post(route('admin.payouts.approve', $payout->id))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('admin.payouts.complete', $payout->id), [
            'transfer_reference' => 'TRF-001', 'proof' => UploadedFile::fake()->create('bukti.pdf', 20, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $payout = OwnerPayout::withoutGlobalScopes()->findOrFail($payout->id);
        $this->assertSame('success', $payout->status);
        $this->assertSame('TRF-001', $payout->transfer_reference);
        $this->assertSame($this->admin->id, $payout->processed_by);
        Storage::disk('local')->assertExists($payout->proof_path);
        $this->assertEquals(0, (float) $this->tenant->fresh()->saldo_platform);
        Notification::assertSentToTimes($this->owner, OwnerPayoutStatusChangedNotification::class, 2);

        $this->actingAs($this->owner)->get(route('owner.payouts.proof', $payout->id))->assertOk();
    }

    public function test_rejected_and_failed_payouts_return_the_amount(): void
    {
        $rejected = $this->payout(100000);
        $failed = $this->payout(50000);

        $this->actingAs($this->admin)->post(route('admin.payouts.reject', $rejected->id), ['rejection_reason' => 'Rekening tidak valid']);
        $this->actingAs($this->admin)->post(route('admin.payouts.approve', $failed->id));
        $this->actingAs($this->admin)->post(route('admin.payouts.fail', $failed->id), ['rejection_reason' => 'Transfer ditolak bank']);

        $this->assertEquals(150000, (float) $this->tenant->fresh()->saldo_platform);
        $this->assertDatabaseHas('owner_payouts', ['id' => $rejected->id, 'status' => 'rejected', 'rejection_reason' => 'Rekening tidak valid']);
        $this->assertDatabaseHas('owner_payouts', ['id' => $failed->id, 'status' => 'failed']);
    }

    public function test_a_finished_payout_cannot_change_again_and_funds_return_only_once(): void
    {
        $payout = $this->payout(100000);
        $this->actingAs($this->admin)->post(route('admin.payouts.reject', $payout->id), ['rejection_reason' => 'Duplikat']);

        $this->actingAs($this->admin)->post(route('admin.payouts.reject', $payout->id), ['rejection_reason' => 'Lagi'])->assertSessionHasErrors('payout');
        $this->actingAs($this->admin)->post(route('admin.payouts.approve', $payout->id))->assertSessionHasErrors('payout');

        $this->assertEquals(100000, (float) $this->tenant->fresh()->saldo_platform);
    }

    public function test_only_admins_review_payouts(): void
    {
        $payout = $this->payout(100000);

        $this->actingAs($this->owner)->get(route('admin.payouts'))->assertForbidden();
        $this->actingAs($this->owner)->post(route('admin.payouts.approve', $payout->id))->assertForbidden();
        $this->assertSame('pending', OwnerPayout::withoutGlobalScopes()->findOrFail($payout->id)->status);
        $this->actingAs($this->admin)->get(route('admin.payouts'))->assertOk()->assertSee('1234567890');
    }

    public function test_an_owner_cannot_download_another_tenants_proof(): void
    {
        Storage::fake('local');
        $otherOwner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $otherTenant = Tenant::factory()->create(['iduser' => $otherOwner->id]);
        $foreign = OwnerPayout::withoutGlobalScopes()->create([
            'idtenant' => $otherTenant->id, 'jumlah' => 10000, 'status' => 'success', 'requested_at' => now(),
            'proof_path' => UploadedFile::fake()->create('x.pdf', 1)->store('payout-proofs', 'local'),
        ]);

        $this->actingAs($this->owner)->get(route('owner.payouts.proof', $foreign->id))->assertNotFound();
    }

    private function pendingBookingPayment(int $amount): Payment
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id]);
        $schedule = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $service->id]);
        $payment = Payment::factory()->create(['idtenant' => $this->tenant->id, 'jumlah' => $amount, 'provider' => 'midtrans']);
        Booking::factory()->create([
            'idtenant' => $this->tenant->id, 'idlayanan' => $service->id, 'idschedule' => $schedule->id,
            'idpayment' => $payment->id, 'status' => 'pending',
        ]);

        return $payment;
    }

    private function payout(int $amount): OwnerPayout
    {
        return OwnerPayout::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id, 'jumlah' => $amount, 'status' => 'pending', 'requested_at' => now(),
            'bank_nama' => 'BCA', 'nomor_rekening' => '1234567890', 'nama_pemilik_rekening' => 'Pemilik',
        ]);
    }
}

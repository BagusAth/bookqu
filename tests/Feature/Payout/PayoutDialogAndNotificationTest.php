<?php

declare(strict_types=1);

namespace Tests\Feature\Payout;

use App\Actions\Admin\ProcessOwnerPayout;
use App\Domain\Payout\PayoutState;
use App\Models\OwnerPayout;
use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use App\Models\User;
use App\Notifications\NewPayoutRequestNotification;
use App\Notifications\OwnerPayoutStatusChangedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayoutDialogAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private User $admin;

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

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
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

    public function test_payout_state_owner_labels_and_tones(): void
    {
        $this->assertSame('Pending', PayoutState::ownerLabel(PayoutState::PENDING));
        $this->assertSame('Pending', PayoutState::ownerLabel(PayoutState::APPROVED));
        $this->assertSame('Berhasil', PayoutState::ownerLabel(PayoutState::SUCCESS));
        $this->assertSame('Gagal', PayoutState::ownerLabel(PayoutState::REJECTED));
        $this->assertSame('Gagal', PayoutState::ownerLabel(PayoutState::FAILED));

        $this->assertSame('Disetujui, transfer diproses', PayoutState::ownerNote(PayoutState::APPROVED));
        $this->assertNull(PayoutState::ownerNote(PayoutState::PENDING));
        $this->assertNull(PayoutState::ownerNote(PayoutState::SUCCESS));

        $this->assertSame('warning', PayoutState::ownerTone(PayoutState::PENDING));
        $this->assertSame('warning', PayoutState::ownerTone(PayoutState::APPROVED));
        $this->assertSame('success', PayoutState::ownerTone(PayoutState::SUCCESS));
        $this->assertSame('danger', PayoutState::ownerTone(PayoutState::REJECTED));
        $this->assertSame('danger', PayoutState::ownerTone(PayoutState::FAILED));
    }

    public function test_admin_complete_requires_at_least_reference_or_proof(): void
    {
        $payout = OwnerPayout::create([
            'idtenant' => $this->tenant->id,
            'request_key' => (string) Str::uuid(),
            'payout_account_id' => $this->account->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner Name',
            'jumlah' => 100000,
            'status' => 'approved',
            'requested_at' => now(),
        ]);

        // Neither provided -> validation error
        $response = $this->actingAs($this->admin)->post("/admin/payouts/{$payout->id}/complete", []);
        $response->assertSessionHasErrors(['transfer_reference', 'proof']);

        // Reference only -> succeeds
        $responseRefOnly = $this->actingAs($this->admin)->post("/admin/payouts/{$payout->id}/complete", [
            'transfer_reference' => 'REF-999',
        ]);
        $responseRefOnly->assertSessionHasNoErrors();
        $this->assertSame('success', $payout->fresh()->status);
        $this->assertSame('REF-999', $payout->fresh()->transfer_reference);
    }

    public function test_admin_complete_succeeds_with_only_proof(): void
    {
        Storage::fake('local');

        $payout = OwnerPayout::create([
            'idtenant' => $this->tenant->id,
            'request_key' => (string) Str::uuid(),
            'payout_account_id' => $this->account->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner Name',
            'jumlah' => 100000,
            'status' => 'approved',
            'requested_at' => now(),
        ]);

        $proofFile = UploadedFile::fake()->create('transfer_receipt.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->admin)->post("/admin/payouts/{$payout->id}/complete", [
            'proof' => $proofFile,
        ]);
        $response->assertSessionHasNoErrors();

        $fresh = $payout->fresh();
        $this->assertSame('success', $fresh->status);
        $this->assertNotNull($fresh->proof_path);
        Storage::disk('local')->assertExists($fresh->proof_path);
    }

    public function test_admin_approval_sets_approved_tracking_without_processed_timestamps(): void
    {
        $payout = OwnerPayout::create([
            'idtenant' => $this->tenant->id,
            'request_key' => (string) Str::uuid(),
            'payout_account_id' => $this->account->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner Name',
            'jumlah' => 100000,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($this->admin)->post("/admin/payouts/{$payout->id}/approve");

        $fresh = $payout->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertNotNull($fresh->approved_at);
        $this->assertEquals($this->admin->id, $fresh->approved_by);
        $this->assertNull($fresh->processed_at);
        $this->assertNull($fresh->processed_by);
    }

    public function test_proof_file_is_cleaned_up_if_transition_fails(): void
    {
        Storage::fake('local');

        $payout = OwnerPayout::create([
            'idtenant' => $this->tenant->id,
            'request_key' => (string) Str::uuid(),
            'payout_account_id' => $this->account->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner Name',
            'jumlah' => 100000,
            'status' => 'pending', // Pending cannot directly transition to SUCCESS! Must be approved first.
            'requested_at' => now(),
        ]);

        $proofFile = UploadedFile::fake()->create('proof.pdf', 50, 'application/pdf');

        $result = app(ProcessOwnerPayout::class)->execute(
            $payout->id,
            PayoutState::SUCCESS,
            $this->admin,
            ['transfer_reference' => 'FAIL-REF'],
            $proofFile
        );

        $this->assertFalse($result);
        $this->assertSame('pending', $payout->fresh()->status);
        // Verify no leftover file stored in disk
        $files = Storage::disk('local')->allFiles('payout-proofs');
        $this->assertEmpty($files);
    }

    public function test_owner_receives_database_and_mail_notifications_on_transitions(): void
    {
        Notification::fake();

        $payout = OwnerPayout::create([
            'idtenant' => $this->tenant->id,
            'request_key' => (string) Str::uuid(),
            'payout_account_id' => $this->account->id,
            'bank_kode' => 'BCA',
            'bank_nama' => 'BCA',
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Owner Name',
            'jumlah' => 100000,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // 1. Approve transition
        app(ProcessOwnerPayout::class)->execute($payout->id, PayoutState::APPROVED, $this->admin);

        Notification::assertSentTo(
            $this->owner,
            OwnerPayoutStatusChangedNotification::class,
            function (OwnerPayoutStatusChangedNotification $n) {
                return $n->via($this->owner) === ['database'];
            }
        );

        // 2. Success transition sends database + mail
        app(ProcessOwnerPayout::class)->execute($payout->id, PayoutState::SUCCESS, $this->admin, ['transfer_reference' => 'TRF-DONE']);

        Notification::assertSentTo(
            $this->owner,
            OwnerPayoutStatusChangedNotification::class,
            function (OwnerPayoutStatusChangedNotification $n) {
                return in_array('mail', $n->via($this->owner), true)
                    && in_array('database', $n->via($this->owner), true);
            }
        );
    }

    public function test_admin_receives_database_notification_on_new_payout_request(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->owner)->post('/owner/payouts', [
            'request_key' => (string) Str::uuid(),
            'jumlah' => 100000,
            'payout_account_id' => $this->account->id,
        ]);
        $response->assertRedirect(route('owner.payments', ['tab' => 'saldo']));

        Notification::assertSentTo(
            $this->admin,
            NewPayoutRequestNotification::class,
            function (NewPayoutRequestNotification $n) {
                return $n->tenantName === 'Barber Pro'
                    && (float) $n->payout->jumlah === 100000.0;
            }
        );
    }

    public function test_owner_balance_view_renders_accessibility_attributes_and_dialog(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.payments', ['tab' => 'saldo']));
        $response->assertOk();

        // Check WCAG dialog accessibility attributes
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);

        // Check header and the Saldo & Pencairan tab in Indonesian (BQ-141, owner-portal.md §8.12)
        $response->assertSee('Pembayaran');
        $response->assertSee('Cara Anda menerima uang, berapa saldo Anda, dan kapan bisa dicairkan.');
        $response->assertSee('Saldo tersedia');
        $response->assertSee('Mutasi saldo');

        // Check dialog triggers and form
        $response->assertSee('Cairkan saldo');
        $response->assertSee('id="withdraw-form"', false);
        $response->assertSee('Ajukan pencairan');
        $response->assertSee('Riwayat pencairan');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Payment\PaymentLifecycle;
use App\Domain\Payment\PaymentTransition;
use App\Models\Booking;
use App\Models\OwnerPayout;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\TenantPayoutAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * BQ-141 (owner-portal.md §8.12): the Pembayaran page with tabs Ringkasan, Transaksi,
 * Saldo & Pencairan, Rekening and Pengaturan.
 */
class PembayaranPageTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private User $admin;

    private TenantPayoutAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 08:00:00', 'Asia/Jakarta'));
        Mail::fake();
        Notification::fake();

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id, 'saldo_platform' => 0, 'payment_mode' => 'platform', 'refunds_enabled' => true, 'cancel_before_hours' => 24]);
        $this->admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->account = TenantPayoutAccount::create([
            'idtenant' => $this->tenant->id, 'bank_kode' => 'BCA', 'bank_nama' => 'BCA', 'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Pemilik', 'is_default' => true, 'changed_at' => now()->subDays(3),
        ]);
    }

    private function paidBooking(int $amount, string $customer = 'Rani', string $code = 'BKQ-PAY1'): Payment
    {
        $service = Service::factory()->create(['idtenant' => $this->tenant->id, 'namalayanan' => 'Foto Studio']);
        $schedule = Schedule::factory()->create(['idtenant' => $this->tenant->id, 'idlayanan' => $service->id]);
        $payment = Payment::factory()->create(['idtenant' => $this->tenant->id, 'jumlah' => $amount, 'provider' => 'midtrans', 'order_id' => 'ORD-'.$code]);
        Booking::factory()->create([
            'idtenant' => $this->tenant->id, 'idlayanan' => $service->id, 'idschedule' => $schedule->id,
            'idpayment' => $payment->id, 'status' => 'pending', 'namapelanggan' => $customer, 'booking_code' => $code,
        ]);
        app(PaymentLifecycle::class)->transition($payment, PaymentTransition::ProviderSucceeded);

        return $payment->fresh();
    }

    private function member(string $role): User
    {
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $user->id, 'role' => $role, 'accepted_at' => now()]);

        return $user;
    }

    public function test_the_page_has_its_five_tabs_and_the_old_balance_url_redirects(): void
    {
        $this->actingAs($this->owner)->get(route('owner.payments'))
            ->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('Ringkasan')
            ->assertSee('Transaksi')
            ->assertSee('Saldo &amp; Pencairan', false)
            ->assertSee('Rekening')
            ->assertSee('Pengaturan');

        $this->get('/owner/settings/balance?page=2')
            ->assertStatus(301)
            ->assertRedirect(route('owner.payments', ['tab' => 'saldo', 'page' => 2]));
    }

    public function test_ac_pay_01_every_route_of_the_page_is_refused_for_manajer_and_kasir(): void
    {
        $payout = OwnerPayout::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id, 'jumlah' => 50000, 'status' => 'pending', 'requested_at' => now(),
            'bank_nama' => 'BCA', 'nomor_rekening' => '1234567890', 'nama_pemilik_rekening' => 'Pemilik',
        ]);

        foreach (['manajer', 'kasir'] as $role) {
            $this->actingAs($this->member($role));
            foreach (['ringkasan', 'transaksi', 'saldo', 'rekening', 'pengaturan'] as $tab) {
                $this->get(route('owner.payments', ['tab' => $tab]))->assertForbidden();
            }
            $this->get('/owner/settings/balance')->assertForbidden();
            $this->get(route('owner.payouts.proof', $payout->id))->assertForbidden();
            $this->post(route('owner.payouts.request'), ['jumlah' => 50000])->assertForbidden();
            $this->post(route('owner.payout-accounts.store'), [])->assertForbidden();
            $this->put(route('owner.payout-accounts.update', $this->account->id), [])->assertForbidden();
            $this->delete(route('owner.payout-accounts.destroy', $this->account->id), [])->assertForbidden();
            $this->post(route('owner.payout-accounts.default', $this->account->id))->assertForbidden();
        }
    }

    public function test_ac_pay_02_the_ledger_balance_equals_the_platform_balance(): void
    {
        $this->paidBooking(150000, 'Rani', 'BKQ-IN1');
        $refunded = $this->paidBooking(80000, 'Budi', 'BKQ-IN2');
        $booking = Booking::withoutGlobalScopes()->where('idpayment', $refunded->id)->firstOrFail();
        $booking->update(['status' => 'cancelled']);
        app(PaymentLifecycle::class)->refundCancelledBooking($booking->fresh());

        $this->actingAs($this->owner)->post(route('owner.payouts.request'), [
            'request_key' => (string) Str::uuid(), 'jumlah' => 60000, 'payout_account_id' => $this->account->id,
        ])->assertSessionHasNoErrors();
        $rejected = OwnerPayout::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->latest('id')->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.payouts.reject', $rejected->id), ['rejection_reason' => 'Rekening tidak valid']);

        $this->actingAs($this->owner)->post(route('owner.payouts.request'), [
            'request_key' => (string) Str::uuid(), 'jumlah' => 50000, 'payout_account_id' => $this->account->id,
        ])->assertSessionHasNoErrors();

        $saldo = (float) $this->tenant->fresh()->saldo_platform;
        $this->assertSame(100000.0, $saldo);

        $response = $this->get(route('owner.payments', ['tab' => 'saldo']))->assertOk();
        $this->assertSame($saldo, (float) $response->viewData('ledger')['total']);
        $this->assertCount(6, $response->viewData('ledger')['rows']->items());
        $response->assertSee('BKQ-IN1')->assertSee('Refund')->assertSee('Pencairan dikembalikan');
    }

    public function test_ac_pay_03_a_repeated_request_key_creates_no_second_payout(): void
    {
        $this->tenant->update(['saldo_platform' => 300000]);
        $payload = ['request_key' => (string) Str::uuid(), 'jumlah' => 100000, 'payout_account_id' => $this->account->id];

        $this->actingAs($this->owner)->post(route('owner.payouts.request'), $payload)
            ->assertRedirect(route('owner.payments', ['tab' => 'saldo']));
        $this->post(route('owner.payouts.request'), $payload)->assertRedirect(route('owner.payments', ['tab' => 'saldo']));

        $this->assertSame(1, OwnerPayout::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->count());
        $this->assertSame(200000.0, (float) $this->tenant->fresh()->saldo_platform);
    }

    public function test_ac_pay_04_minimum_and_number_of_accounts_follow_the_config(): void
    {
        config(['payouts.min_amount' => 50000, 'payouts.max_accounts' => 2]);
        $this->tenant->update(['saldo_platform' => 300000]);

        $this->actingAs($this->owner)->get(route('owner.payments', ['tab' => 'saldo']))
            ->assertSee('Minimal Rp 50.000')
            ->assertDontSee('Minimal Rp 10.000');

        $this->get(route('owner.payments', ['tab' => 'rekening']))
            ->assertSee('1 dari 2 rekening')
            ->assertSee('id="payout-account-create"', false);

        TenantPayoutAccount::create([
            'idtenant' => $this->tenant->id, 'bank_kode' => 'BNI', 'bank_nama' => 'BNI', 'nomor_rekening' => '9876543210',
            'nama_pemilik_rekening' => 'Pemilik', 'is_default' => false, 'changed_at' => now(),
        ]);
        $this->get(route('owner.payments', ['tab' => 'rekening']))
            ->assertSee('2 dari 2 rekening')
            ->assertDontSee('id="payout-account-create"', false);
    }

    public function test_ac_pay_05_no_gateway_credential_field_exists_in_the_owner_portal(): void
    {
        foreach (['owner.settings.payment', 'owner.settings.payments', 'owner.settings.payment-setting'] as $name) {
            $this->assertFalse(Route::has($name), $name.' must be removed (D-016)');
        }

        $views = collect(File::allFiles(resource_path('views/owner')))
            ->merge(File::allFiles(resource_path('views/components/owner')))
            ->merge(File::allFiles(resource_path('views/layouts')));
        foreach ($views as $file) {
            $this->assertDoesNotMatchRegularExpression('/server_key|client_key|merchant_id|payment_mode/', $file->getContents(), $file->getRelativePathname());
        }
    }

    public function test_ringkasan_shows_balance_figures_payment_counts_and_latest_transactions(): void
    {
        $this->paidBooking(150000, 'Rani', 'BKQ-SUM1');
        Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'booking', 'status' => 'pending', 'jumlah' => 10000]);
        Payment::factory()->create(['idtenant' => $this->tenant->id, 'tipe' => 'booking', 'status' => 'gagal', 'jumlah' => 10000]);
        OwnerPayout::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id, 'jumlah' => 40000, 'status' => 'success', 'requested_at' => now()->subDays(5), 'processed_at' => now()->subDays(4),
            'bank_nama' => 'BCA', 'nomor_rekening' => '1234567890', 'nama_pemilik_rekening' => 'Pemilik',
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.payments'))->assertOk()
            ->assertSee('Saldo tersedia')
            ->assertSee('Sedang diproses')
            ->assertSee('Total dicairkan')
            ->assertSee('Rp 40.000')
            ->assertSee('BKQ-SUM1');
        $this->assertSame(['sukses' => 1, 'pending' => 1, 'gagal' => 1, 'refund' => 0], $response->viewData('summary')['counts']);
    }

    public function test_transaksi_lists_every_customer_payment_with_refund_amount_and_stays_in_the_business(): void
    {
        $refunded = $this->paidBooking(80000, 'Budi', 'BKQ-TRX1');
        $booking = Booking::withoutGlobalScopes()->where('idpayment', $refunded->id)->firstOrFail();
        $booking->update(['status' => 'cancelled']);
        app(PaymentLifecycle::class)->refundCancelledBooking($booking->fresh());
        $other = Tenant::factory()->create();
        Payment::factory()->create(['idtenant' => $other->id, 'tipe' => 'booking', 'order_id' => 'ORD-ASING', 'status' => 'sukses']);

        $this->actingAs($this->owner)->get(route('owner.payments', ['tab' => 'transaksi']))
            ->assertOk()
            ->assertSee('ORD-BKQ-TRX1')
            ->assertSee('Budi')
            ->assertSee('Foto Studio')
            ->assertSee('Refund Rp 80.000')
            ->assertDontSee('ORD-ASING');
    }

    public function test_saldo_tab_shows_the_request_steps_eligibility_and_payout_stages(): void
    {
        $this->tenant->update(['saldo_platform' => 0]);

        $this->actingAs($this->owner)->get(route('owner.payments', ['tab' => 'saldo']))
            ->assertSee('Belum dapat mengajukan pencairan')
            ->assertSee('Saldo belum tersedia untuk ditarik.');

        $this->tenant->update(['saldo_platform' => 300000]);
        OwnerPayout::withoutGlobalScopes()->create([
            'idtenant' => $this->tenant->id, 'jumlah' => 40000, 'status' => 'approved', 'requested_at' => now()->subDay(), 'approved_at' => now(),
            'bank_nama' => 'BCA', 'nomor_rekening' => '1234567890', 'nama_pemilik_rekening' => 'Pemilik',
        ]);

        $this->get(route('owner.payments', ['tab' => 'saldo']))
            ->assertSee('id="withdraw-form"', false)
            ->assertSee('name="request_key"', false)
            ->assertSee('Langkah 1 dari 3')
            ->assertSee('Langkah 3 dari 3')
            ->assertSee('Diajukan')
            ->assertSee('Disetujui')
            ->assertSee('Ditransfer');
    }

    public function test_pengaturan_explains_how_payments_work_without_settings(): void
    {
        $this->actingAs($this->owner)->get(route('owner.payments', ['tab' => 'pengaturan']))
            ->assertOk()
            ->assertSee('Biaya platform 0%')
            ->assertSee('15 menit')
            ->assertSee('24 jam')
            ->assertDontSee('name="payment_mode"', false);
    }

    public function test_an_owner_with_an_ended_subscription_still_opens_the_page(): void
    {
        Subscription::query()->delete();

        $this->actingAs($this->owner)->get(route('owner.payments', ['tab' => 'saldo']))->assertOk();
    }
}

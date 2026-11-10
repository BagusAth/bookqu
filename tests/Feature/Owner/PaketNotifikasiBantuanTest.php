<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use App\Notifications\NewBookingOwnerNotification;
use App\Notifications\OwnerPayoutStatusChangedNotification;
use App\Notifications\SubscriptionReminderNotification;
use App\Notifications\TrialExpiringNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * BQ-145 (owner-portal.md §8.15 Paket & Tagihan, §8.16 Notifikasi, §8.17 Bantuan).
 */
class PaketNotifikasiBantuanTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Jakarta'));

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id]);
        $this->plan = Plan::updateOrCreate(['namapaket' => 'medium'], ['hargabulanan' => 249000, 'maxlayanan' => 10, 'maxbooking' => 500, 'isunlimited' => false]);
        Plan::updateOrCreate(['namapaket' => 'small'], ['hargabulanan' => 149000, 'maxlayanan' => 5, 'maxbooking' => 300, 'isunlimited' => false]);
        Subscription::create([
            'idtenant' => $this->tenant->id, 'idplan' => $this->plan->id, 'status' => 'active',
            'langganan_mulai' => now()->subDays(10), 'langganan_berakhir' => Carbon::parse('2026-12-10 23:59:59', 'Asia/Jakarta'),
        ]);
        Service::factory()->count(2)->create(['idtenant' => $this->tenant->id]);
    }

    private function member(string $role): User
    {
        $user = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $user->id, 'role' => $role, 'accepted_at' => now()]);

        return $user;
    }

    private function subscriptionPayment(string $status, int $amount, string $orderId): Payment
    {
        return Payment::factory()->create([
            'idtenant' => $this->tenant->id, 'tipe' => 'subscription', 'idplan' => $this->plan->id,
            'jumlah' => $amount, 'status' => $status, 'order_id' => $orderId,
        ]);
    }

    private function notify(User $user, string $type, array $data): string
    {
        $id = (string) Str::uuid();
        $user->notifications()->create(['id' => $id, 'type' => $type, 'data' => $data]);

        return $id;
    }

    // ── Paket & Tagihan ──

    public function test_paket_shows_plan_status_end_date_usage_comparison_and_billing_history(): void
    {
        $this->member('kasir');
        $this->subscriptionPayment('sukses', 249000, 'SUB-PAID-1');
        $this->subscriptionPayment('pending', 249000, 'SUB-OPEN-1');

        $response = $this->actingAs($this->owner)->get(route('owner.subscription'))
            ->assertOk()
            ->assertSee('Paket &amp; Tagihan', false)
            ->assertSee('Medium')
            ->assertSee('Aktif')
            ->assertSee('10 Des 2026')
            ->assertSee('2 dari 10 layanan')
            ->assertSee('data-plan-features="small"', false)
            ->assertSee(route('owner.checkout.invoice', 'SUB-PAID-1'))
            ->assertSee(route('owner.checkout.payment', 'SUB-OPEN-1'))
            ->assertSee('Bayar sekarang');

        $this->assertSame(['used' => 2, 'limit' => (int) config('plans.medium.team_limit')], $response->viewData('usage')['team']);
    }

    public function test_ac_pkt_03_manajer_and_kasir_are_refused(): void
    {
        $paid = $this->subscriptionPayment('sukses', 249000, 'SUB-PAID-2');

        foreach (['manajer', 'kasir'] as $role) {
            $this->actingAs($this->member($role));
            $this->get(route('owner.subscription'))->assertForbidden();
            $this->get(route('owner.checkout', $this->plan->id))->assertForbidden();
            $this->post(route('owner.checkout.process'), ['idplan' => $this->plan->id])->assertForbidden();
            $this->get(route('owner.checkout.payment', $paid->order_id))->assertForbidden();
            $this->get(route('owner.checkout.invoice', $paid->order_id))->assertForbidden();
        }
    }

    public function test_ac_pkt_04_the_invoice_amount_equals_the_paid_amount(): void
    {
        $paid = $this->subscriptionPayment('sukses', 199000, 'SUB-PROMO-1');
        $this->plan->update(['hargabulanan' => 299000]);

        $html = (string) $this->actingAs($this->owner)->get(route('owner.checkout.invoice', $paid->order_id))
            ->assertOk()
            ->assertSee('Nomor pesanan')
            ->assertDontSee('Kode Booking')
            ->getContent();

        $this->assertStringContainsString('Rp 199.000', $html);
        $this->assertStringNotContainsString('Rp 299.000', $html);
        $this->assertStringNotContainsString('Biaya Layanan Platform', $html);
    }

    public function test_checkout_findings_of_bq_104(): void
    {
        $payment = (string) file_get_contents(resource_path('views/owner/checkout-payment.blade.php'));
        $this->assertStringNotContainsString("snap.pay('{{", $payment);
        $this->assertStringContainsString('snap.pay(@js($snapToken)', $payment);

        $this->actingAs($this->owner)->get(route('owner.checkout', $this->plan->id))
            ->assertOk()
            ->assertSee('data-checkout-summary class="order-1', false)
            ->assertSee('data-checkout-form class="order-2', false);
    }

    // ── Notifikasi ──

    public function test_notifications_list_filters_by_unread_and_type(): void
    {
        $this->notify($this->owner, NewBookingOwnerNotification::class, ['title' => 'Booking baru Rani', 'event_type' => 'new_booking', 'url' => url('/owner/bookings?katakunci=BKQ-1')]);
        $this->notify($this->owner, SubscriptionReminderNotification::class, ['title' => 'Paket hampir habis', 'url' => route('owner.subscription')]);
        $read = $this->notify($this->owner, OwnerPayoutStatusChangedNotification::class, ['title' => 'Pencairan berhasil', 'url' => route('owner.payments', ['tab' => 'saldo'])]);
        $this->owner->notifications()->whereKey($read)->update(['read_at' => now()]);

        $this->actingAs($this->owner)->get(route('owner.notifications'))
            ->assertOk()->assertSee('Booking baru Rani')->assertSee('Paket hampir habis')->assertSee('Pencairan berhasil');
        $this->get(route('owner.notifications', ['filter' => 'unread']))
            ->assertSee('Booking baru Rani')->assertDontSee('Pencairan berhasil');
        $this->get(route('owner.notifications', ['type' => 'paket']))
            ->assertSee('Paket hampir habis')->assertDontSee('Booking baru Rani');
        $this->get(route('owner.notifications', ['type' => 'booking']))
            ->assertSee('Booking baru Rani')->assertDontSee('Paket hampir habis');
    }

    public function test_ac_ntf_01_opening_an_item_marks_it_read_and_lands_on_its_target(): void
    {
        $target = url('/owner/bookings?katakunci=BKQ-77');
        $id = $this->notify($this->owner, NewBookingOwnerNotification::class, ['title' => 'Booking baru', 'url' => $target]);

        $this->actingAs($this->owner)->get(route('owner.notifications'))
            ->assertSee(route('owner.notifications.read', $id), false);

        $this->post(route('owner.notifications.read', $id))->assertRedirect($target);
        $this->assertNotNull($this->owner->notifications()->find($id)->read_at);
    }

    public function test_the_redirect_after_reading_never_leaves_the_portal(): void
    {
        $id = $this->notify($this->owner, NewBookingOwnerNotification::class, ['title' => 'Aneh', 'url' => 'https://evil.example.com/phish']);

        $this->actingAs($this->owner)->post(route('owner.notifications.read', $id))->assertRedirect(route('owner.notifications'));
        $this->post(route('owner.notifications.read', $id), ['redirect' => 'https://evil.example.com'])->assertRedirect(route('owner.notifications'));
        $this->post(route('owner.notifications.read', $id), ['redirect' => '//evil.example.com/x'])->assertRedirect(route('owner.notifications'));
        $this->post(route('owner.notifications.read', $id), ['redirect' => '/owner/bookings'])->assertRedirect('/owner/bookings');
    }

    public function test_delete_uses_the_shared_confirm_dialog_and_mark_all_read_works(): void
    {
        $id = $this->notify($this->owner, NewBookingOwnerNotification::class, ['title' => 'Hapus saya']);

        $html = (string) $this->actingAs($this->owner)->get(route('owner.notifications'))->getContent();
        $this->assertStringContainsString('open-confirm', $html);
        $this->assertStringNotContainsString('return confirm(', $html);

        $this->post(route('owner.notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $this->owner->unreadNotifications()->count());

        $this->delete(route('owner.notifications.destroy', $id))->assertRedirect();
        $this->assertNull($this->owner->notifications()->find($id));
    }

    public function test_ac_ntf_02_a_kasir_never_receives_payout_or_plan_notifications(): void
    {
        $kasir = $this->member('kasir');
        $this->notify($kasir, NewBookingOwnerNotification::class, ['title' => 'Booking untuk kasir']);
        $this->notify($kasir, OwnerPayoutStatusChangedNotification::class, ['title' => 'Pencairan rahasia']);
        $this->notify($kasir, TrialExpiringNotification::class, ['title' => 'Paket rahasia']);

        $this->actingAs($kasir)->get(route('owner.notifications'))
            ->assertOk()
            ->assertSee('Booking untuk kasir')
            ->assertDontSee('Pencairan rahasia')
            ->assertDontSee('Paket rahasia')
            ->assertViewHas('typeFilters', fn (array $filters): bool => ! array_key_exists('pencairan', $filters) && ! array_key_exists('paket', $filters));
    }

    // ── Bantuan ──

    public function test_bantuan_lists_questions_by_page_contacts_and_the_guide_for_every_role(): void
    {
        config(['modules.help' => 'released', 'support.whatsapp' => '6281299990000', 'support.email' => 'bantuan@contoh.test']);

        $this->actingAs($this->member('kasir'))->get(route('owner.help'))
            ->assertOk()
            ->assertSee('Pertanyaan umum')
            ->assertSee('Beranda')
            ->assertSee('Booking')
            ->assertSee('https://wa.me/6281299990000', false)
            ->assertSee('mailto:bantuan@contoh.test', false)
            ->assertSee(route('owner.dashboard'));
    }

    public function test_ac_bnt_01_contact_details_come_from_configuration(): void
    {
        config(['modules.help' => 'released', 'support.whatsapp' => '6280000000001', 'support.email' => 'satu@contoh.test']);
        $this->actingAs($this->owner)->get(route('owner.help'))->assertSee('6280000000001')->assertSee('satu@contoh.test');

        config(['support.whatsapp' => '6280000000002', 'support.email' => 'dua@contoh.test']);
        $this->get(route('owner.help'))->assertSee('6280000000002')->assertDontSee('6280000000001')->assertSee('dua@contoh.test');

        $view = (string) file_get_contents(resource_path('views/owner/help.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/\b62\d{8,}\b|@bookqu\./', $view);
    }

    public function test_bantuan_stays_hidden_until_its_module_is_released(): void
    {
        config(['modules.help' => 'hidden']);

        $this->actingAs($this->owner)->get(route('owner.help'))->assertNotFound();
    }
}

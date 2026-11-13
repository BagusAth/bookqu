<?php

namespace Tests\Feature\Owner;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Domain\Payment\RefundRequestState;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerPortalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_tabs_show_live_preview_endpoints_and_runway(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 10:20', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        Service::factory()->create(['idtenant' => $tenant->id, 'is_active' => true]);

        $this->actingAs($tenant->user)->get(route('owner.schedule', ['tab' => 'slots', 'buat' => 1]))
            ->assertOk()->assertSeeText('Periksa ketersediaan slot 7 hari ke depan.')
            ->assertSee(route('owner.schedule.bulk-preview'))->assertSeeText('Pratinjau slot');
        $this->get(route('owner.schedule', ['tab' => 'prices']))
            ->assertOk()->assertSee(route('owner.schedule.price-simulator'))->assertSee(route('owner.schedule.apply-base-price'));
    }

    public function test_booking_panel_uses_frozen_amounts_and_exposes_only_tenant_actions(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 10:20', 'Asia/Jakarta'));
        $tenant = Tenant::factory()->create();
        $service = Service::factory()->create(['idtenant' => $tenant->id, 'harga' => 999000]);
        $slot = Schedule::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id]);
        $payment = Payment::factory()->create([
            'idtenant' => $tenant->id, 'provider' => 'singapay', 'status' => 'sukses', 'jumlah' => 75001,
            'price_breakdown' => ['base_subtotal' => 75001, 'lines' => [['schedule_id' => $slot->id, 'amount' => 75001]]],
        ]);
        $booking = Booking::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'idschedule' => $slot->id,
            'idpayment' => $payment->id, 'status' => 'paid', 'email' => 'panel@example.test',
        ]);
        $refund = RefundRequest::create([
            'idtenant' => $tenant->id, 'idpayment' => $payment->id, 'booking_id' => $booking->id,
            'reason' => 'Acara berubah', 'bank_nama' => 'Bank uji', 'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Nasabah uji', 'status' => RefundRequestState::Open,
        ]);
        BookingLog::record($booking->id, 'paid', 'Pembayaran tercatat.');

        $this->actingAs($tenant->user)->get(route('owner.bookings', ['tab' => 'action', 'katakunci' => 'panel@example.test']))
            ->assertOk()->assertSeeText('Perlu tindakan (1)')->assertSeeText('Rp 75.001')->assertDontSeeText('999.000');
        $panel = route('owner.bookings.panel', $booking->id);
        $response = $this->getJson($panel)->assertOk()
            ->assertJsonPath('payment.amount', 'Rp 75.001')
            ->assertJsonPath('history.0.actor', 'Sistem')
            ->assertDontSee('1234567890');
        $actions = collect($response->json('actions'))->keyBy('label');
        $this->assertSame('reason', $actions['Batalkan booking']['reason']['name']);
        $this->assertSame('Pelanggan mendapat refund penuh Rp 75.001.', $actions['Batalkan booking']['confirm']['message']);
        $this->assertSame('accepted_half', $actions['Terima 50%']['fields']['decision']);
        $this->assertSame(route('owner.refunds.decide', $refund->id), $actions['Terima 50%']['url']);
        $this->actingAs(Tenant::factory()->create()->user)->getJson($panel)->assertNotFound();
    }

    public function test_csv_export_is_plan_gated_and_neutralizes_formula_cells(): void
    {
        $tenant = Tenant::factory()->create();
        Booking::factory()->create(['idtenant' => $tenant->id, 'namapelanggan' => '=1+1']);

        $this->actingAs($tenant->user)->get(route('owner.bookings.export'))->assertForbidden();
        app(CreateTrialSubscription::class)->execute($tenant);
        $file = $this->get(route('owner.bookings.export'))->assertDownload('bookings.csv');
        $this->assertStringContainsString("'=1+1", $file->streamedContent());
    }

    public function test_settings_render_saved_policy_and_notification_preferences(): void
    {
        $tenant = Tenant::factory()->create(['refunds_enabled' => true, 'cancel_before_hours' => 0, 'reschedule_before_hours' => 12]);
        $tenant->user->forceFill(['notification_preferences' => ['new_booking' => false]])->save();
        $this->actingAs($tenant->user)->get(route('owner.settings', ['tab' => 'policy']))
            ->assertOk()->assertSeeText('Ajukan refund paling lambat 0 jam')
            ->assertSeeText('Pindah jadwal paling lambat 12 jam');
        $this->get(route('owner.settings', ['tab' => 'notifications']))
            ->assertOk()->assertSee(route('owner.settings.notification-preferences'))
            ->assertDontSee('name="preferences[new_booking]" value="1" checked', false)
            ->assertSee('name="preferences[reschedule]" value="1" checked', false);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Actions\Booking\CreateBooking;
use App\Actions\Schedule\GetAvailableSchedules;
use App\Actions\Subscription\CreateTrialSubscription;
use App\Domain\CustomerAccess\AccessProfile;
use App\Mail\BookingGroupInvoiceMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesCustomerAccess;
use Tests\TestCase;

/**
 * BQ-114: existing bookings keep the range of the slots they booked when the service's
 * duration later changes, and multi-slot selection follows slot boundaries.
 */
class BookingTimeRangeAfterDurationChangeTest extends TestCase
{
    use RefreshDatabase;
    use UsesCustomerAccess;

    protected Tenant $tenant;

    protected Service $service;

    protected string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00', 'Asia/Jakarta'));
        $this->withoutMiddleware([PreventRequestForgery::class, ValidateCsrfToken::class]);
        Mail::fake();

        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $owner->id, 'slug' => 'my-business']);
        $this->service = Service::factory()->create(['idtenant' => $this->tenant->id, 'namalayanan' => 'Sesi Foto', 'harga' => 150000, 'durasi' => 60, 'is_active' => true]);
        $this->date = Carbon::now()->addDays(3)->toDateString();
    }

    public function test_single_booking_pages_keep_the_booked_range_after_the_duration_grows(): void
    {
        [$payment, $bookings] = $this->paidGroup([['14:00:00', '14:59:00']]);
        $booking = $bookings[0];
        $this->service->update(['durasi' => 90]);

        $show = $this->grantCustomerAccess($booking)->get('/manage/'.$booking->booking_code.'?token='.$booking->cancellation_token)->assertOk();
        $show->assertSee('14:00 – 15:00', false)
            ->assertSee(str_replace('-', '', $this->date).'T140000/'.str_replace('-', '', $this->date).'T150000', false)
            ->assertSee('60 menit')
            ->assertDontSee('15:30')
            ->assertDontSee('90 menit');

        $this->grantCustomerAccess($booking)->get('/manage/'.$booking->booking_code.'/invoice?token='.$booking->cancellation_token)
            ->assertOk()
            ->assertSee('60 menit')
            ->assertDontSee('90 menit');

        $this->grantCustomerAccess($payment, AccessProfile::Checkout)->get('/my-business/booking/payment/'.$payment->order_id.'/invoice')
            ->assertOk()
            ->assertSee(urlencode('14:00 - 15:00 WIB'), false)
            ->assertSee(str_replace('-', '', $this->date).'T140000/'.str_replace('-', '', $this->date).'T150000', false)
            ->assertSee('60 menit')
            ->assertDontSee('15:30');
    }

    public function test_group_pages_and_mail_list_each_session_from_its_slot(): void
    {
        [$payment, $bookings] = $this->paidGroup([['14:00:00', '14:59:00'], ['15:00:00', '15:59:00']]);
        $this->service->update(['durasi' => 90]);
        $day = str_replace('-', '', $this->date);

        $this->grantCustomerAccess($payment)->get('/manage/payment/'.$payment->order_id)
            ->assertOk()
            ->assertSee('14:00 – 15:00 WIB', false)
            ->assertSee('15:00 – 16:00 WIB', false)
            ->assertSee($day.'T140000/'.$day.'T160000', false)
            ->assertDontSee('16:30');

        $this->grantCustomerAccess($payment)->get('/manage/payment/'.$payment->order_id.'/invoice')
            ->assertOk()
            ->assertSee('14:00 – 15:00 WIB', false)
            ->assertSee('15:00 – 16:00 WIB', false)
            ->assertDontSee('16:30');

        app(TenantContext::class)->clear();
        $html = (new BookingGroupInvoiceMail($payment->fresh(), collect($bookings)->map->fresh(), 'https://example.test/manage'))->render();
        $this->assertStringContainsString('14:00 – 15:00 WIB', $html);
        $this->assertStringContainsString('15:00 – 16:00 WIB', $html);
        $this->assertStringNotContainsString('16:30', $html);
    }

    public function test_slot_picker_shows_the_slot_end_not_the_current_duration(): void
    {
        $this->slot('14:00:00', '14:59:00');
        $this->service->update(['durasi' => 90]);
        app(TenantContext::class)->setTenantId((int) $this->tenant->id);

        $slots = app(GetAvailableSchedules::class)->getTimeSlots($this->tenant, $this->service->fresh(), $this->date);
        $slot = collect($slots)->firstWhere('time', '14:00');

        $this->assertNotNull($slot);
        $this->assertSame('15:00', $slot['end_time']);
        $this->assertSame(60, $slot['duration_minutes']);
    }

    public function test_adjacent_slots_stay_bookable_together_after_the_duration_changes(): void
    {
        app(CreateTrialSubscription::class)->execute($this->tenant);
        $first = $this->slot('14:00:00', '14:59:00');
        $second = $this->slot('15:00:00', '15:59:00');
        $this->service->update(['durasi' => 90]);
        app(TenantContext::class)->setTenantId((int) $this->tenant->id);

        $result = app(CreateBooking::class)->execute(
            $this->tenant,
            $this->service->fresh(),
            $this->date,
            ['14:00', '15:00'],
            [$first->id, $second->id],
            ['namapelanggan' => 'Rani', 'nomorhp' => '081234567890', 'email' => 'rani@example.com', 'catatan' => null],
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertSame(2, Booking::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->count());
    }

    public function test_slots_with_a_gap_are_still_refused(): void
    {
        app(CreateTrialSubscription::class)->execute($this->tenant);
        $first = $this->slot('14:00:00', '14:59:00');
        $third = $this->slot('16:00:00', '16:59:00');
        app(TenantContext::class)->setTenantId((int) $this->tenant->id);

        $result = app(CreateBooking::class)->execute(
            $this->tenant,
            $this->service->fresh(),
            $this->date,
            ['14:00', '16:00'],
            [$first->id, $third->id],
            ['namapelanggan' => 'Rani', 'nomorhp' => '081234567890', 'email' => 'rani@example.com', 'catatan' => null],
        );

        $this->assertSame('Slot harus berurutan tanpa jeda.', $result['error'] ?? null);
        $this->assertSame(0, Booking::withoutGlobalScopes()->where('idtenant', $this->tenant->id)->count());
    }

    private function slot(string $start, string $end): Schedule
    {
        return Schedule::factory()->create([
            'idtenant' => $this->tenant->id,
            'idlayanan' => $this->service->id,
            'tanggal' => $this->date,
            'jam_mulai' => $start,
            'jam_selesai' => $end,
            'status' => 'tersedia',
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $slots
     * @return array{0: Payment, 1: list<Booking>}
     */
    private function paidGroup(array $slots): array
    {
        $payment = Payment::factory()->create(['idtenant' => $this->tenant->id, 'jumlah' => 150000 * count($slots), 'status' => 'sukses']);
        $bookings = [];
        foreach ($slots as $index => [$start, $end]) {
            $schedule = $this->slot($start, $end);
            $bookings[] = Booking::factory()->create([
                'idtenant' => $this->tenant->id,
                'idlayanan' => $this->service->id,
                'idschedule' => $schedule->id,
                'idpayment' => $payment->id,
                'status' => 'paid',
                'tanggalbooking' => $this->date,
                'jam' => $start,
                'booking_code' => 'BKQ-RANGE-'.$index,
                'cancellation_token' => 'cancel-token-'.$index,
                'reschedule_token' => 'reschedule-token-'.$index,
            ]);
        }

        return [$payment, $bookings];
    }
}

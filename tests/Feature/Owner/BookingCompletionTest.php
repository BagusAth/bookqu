<?php

namespace Tests\Feature\Owner;

use App\Actions\Booking\CompleteBookings;
use App\Actions\Booking\UpdateBookingStatus;
use App\Domain\Booking\BookingRules;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** BQ-162 batch 4: automatic completion (FR-BOOKING-024) and bulk completion (FR-BOOKING-025). */
class BookingCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-17 20:00:00', 'Asia/Jakarta'));
        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id]);
        $this->tenant->forceFill(['auto_completion_started_at' => now()->startOfDay()])->save();
        $this->service = Service::factory()->create(['idtenant' => $this->tenant->id]);
    }

    public function test_complete_all_closes_ended_sessions_and_skips_refund_requests_and_future_sessions(): void
    {
        $ended = $this->paidBooking('2026-11-17', '09:00', '10:00');
        $refunding = $this->paidBooking('2026-11-16', '09:00', '10:00');
        $this->openRefund($refunding);
        $future = $this->paidBooking('2026-11-18', '09:00', '10:00');

        $this->actingAs($this->owner)->post(route('owner.bookings.complete-many'), ['all_ended' => 1])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sukses', fn (string $message) => str_starts_with($message, '1 booking ditandai selesai.') && str_contains($message, '1 dilewati'));

        $this->assertSame('completed', $ended->fresh()->status);
        $this->assertSame('paid', $refunding->fresh()->status);
        $this->assertSame('paid', $future->fresh()->status);
    }

    public function test_selected_ids_never_touch_another_tenant(): void
    {
        $own = $this->paidBooking('2026-11-17', '09:00', '10:00');
        $otherTenant = Tenant::factory()->create();
        $otherService = Service::factory()->create(['idtenant' => $otherTenant->id]);
        $foreign = $this->paidBooking('2026-11-17', '09:00', '10:00', $otherTenant, $otherService);

        $this->actingAs($this->owner)->post(route('owner.bookings.complete-many'), ['ids' => [$own->id, $foreign->id]])
            ->assertSessionHas('sukses', fn (string $message) => str_starts_with($message, '1 booking ditandai selesai.'));

        $this->assertSame('completed', $own->fresh()->status);
        $this->assertSame('paid', $foreign->fresh()->status);
    }

    public function test_a_kasir_may_complete_in_bulk_and_the_page_offers_it(): void
    {
        $ended = $this->paidBooking('2026-11-17', '09:00', '10:00');
        $kasir = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $kasir->id, 'role' => 'kasir', 'accepted_at' => now()]);

        $this->actingAs($kasir)->get(route('owner.bookings', ['tab' => 'action']))
            ->assertOk()->assertSee('Tandai selesai semua (1)');
        $this->actingAs($kasir)->post(route('owner.bookings.complete-many'), ['ids' => [$ended->id]])->assertSessionHasNoErrors();

        $this->assertSame('completed', $ended->fresh()->status);
    }

    public function test_bulk_completion_sends_no_mail_per_booking(): void
    {
        Notification::fake();
        $this->paidBooking('2026-11-17', '09:00', '10:00');
        $this->paidBooking('2026-11-17', '11:00', '12:00');

        $this->actingAs($this->owner)->post(route('owner.bookings.complete-many'), ['all_ended' => 1])->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_the_scheduler_completes_sessions_six_hours_after_they_end_and_records_the_system(): void
    {
        $longAgo = $this->paidBooking('2026-11-17', '12:00', '13:00');
        $recent = $this->paidBooking('2026-11-17', '15:00', '16:00');
        $cancelRequested = $this->paidBooking('2026-11-17', '08:00', '09:00');
        DB::table('booking_cancellation_requests')->insert([
            'idtenant' => $this->tenant->id, 'booking_id' => $cancelRequested->id, 'reason' => 'Sakit', 'status' => 'pending', 'created_at' => now(),
        ]);

        $this->artisan('bookings:complete-ended')->assertSuccessful();

        $this->assertSame('completed', $longAgo->fresh()->status);
        $this->assertSame('paid', $recent->fresh()->status);
        $this->assertSame('paid', $cancelRequested->fresh()->status);
        $log = BookingLog::where('booking_id', $longAgo->id)->where('event', 'completed')->firstOrFail();
        $this->assertSame('system', $log->metadata['actor_role'] ?? null);
    }

    public function test_the_scheduler_leaves_tenants_that_switched_it_off_and_no_show_bookings(): void
    {
        $noShow = $this->paidBooking('2026-11-16', '09:00', '10:00');
        $noShow->update(['status' => 'no_show']);
        $this->tenant->update(['auto_complete_bookings' => false]);
        $off = $this->paidBooking('2026-11-16', '11:00', '12:00');

        $this->artisan('bookings:complete-ended')->assertSuccessful();

        $this->assertSame('paid', $off->fresh()->status);
        $this->assertSame('no_show', $noShow->fresh()->status);
    }

    public function test_the_booking_policy_saves_the_automatic_completion_switch(): void
    {
        $this->actingAs($this->owner)->patch(route('owner.settings.booking-policy'), [
            'refunds_enabled' => 1, 'cancel_before_hours' => 24, 'reschedule_before_hours' => 24,
            'booking_min_lead_hours' => 0, 'booking_max_days_ahead' => 30, 'booking_buffer_minutes' => 0,
            'auto_complete_bookings' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($this->tenant->fresh()->auto_complete_bookings);
    }

    public function test_first_activation_keeps_old_sessions_for_manual_completion(): void
    {
        $old = $this->paidBooking('2026-11-16', '09:00', '10:00');
        $this->artisan('bookings:complete-ended')->assertSuccessful();
        $this->assertSame('paid', $old->fresh()->status);

        $this->actingAs($this->owner)->post(route('owner.bookings.complete-many'), ['all_ended' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame('completed', $old->fresh()->status);
        $this->tenant->update(['auto_complete_bookings' => false]);
        $this->travelTo(now()->addDay());
        $this->tenant->update(['auto_complete_bookings' => true]);
        $this->assertTrue($this->tenant->fresh()->auto_completion_started_at->equalTo(now()));
    }

    public function test_ended_count_matches_the_time_range_for_inclusive_and_overnight_slots(): void
    {
        app(TenantContext::class)->setTenantId((int) $this->tenant->id);
        $bookings = [
            $this->paidBooking('2026-11-17', '09:00', '09:59'),
            $this->paidBooking('2026-11-16', '23:00', '00:59'),
            $this->paidBooking('2026-11-17', '23:00', '00:59'),
        ];
        $action = app(CompleteBookings::class);
        foreach (['2026-11-17 00:59', '2026-11-17 01:00', '2026-11-17 09:59', '2026-11-17 10:00', '2026-11-18 01:00'] as $time) {
            $cutoff = Carbon::parse($time, 'Asia/Jakarta');
            $expected = count(array_filter($bookings, fn (Booking $booking): bool => BookingRules::sessionHasEnded([$booking], $cutoff)));
            $this->assertSame($expected, $action->endedCount($this->tenant, $cutoff));
        }
    }

    public function test_bulk_completion_processes_more_than_one_batch(): void
    {
        app(TenantContext::class)->setTenantId((int) $this->tenant->id);
        $this->service->update(['kapasitas' => 300]);
        $first = $this->paidBooking('2026-11-16', '09:00', '10:00');
        Booking::factory()->count(CompleteBookings::BATCH_SIZE + 5)->create([
            'idtenant' => $this->tenant->id, 'idlayanan' => $this->service->id, 'idschedule' => $first->idschedule,
            'tanggalbooking' => '2026-11-16', 'jam' => '09:00:00', 'status' => 'paid', 'peserta' => 1,
        ]);

        $action = app(CompleteBookings::class);
        $result = $action->completeEnded($this->tenant, 'owner', limit: CompleteBookings::BATCH_SIZE + 1);
        $this->assertSame(['completed' => CompleteBookings::BATCH_SIZE + 1, 'skipped' => 0], $result);
        $this->assertSame(5, $action->endedCount($this->tenant, now()));
    }

    public function test_scheduler_reports_a_failed_tenant_and_continues_with_the_next(): void
    {
        $this->paidBooking('2026-11-17', '09:00', '10:00');
        $other = Tenant::factory()->create();
        $other->forceFill(['auto_completion_started_at' => now()->startOfDay()])->save();
        $service = Service::factory()->create(['idtenant' => $other->id]);
        $next = $this->paidBooking('2026-11-17', '09:00', '10:00', $other, $service);
        $real = app(UpdateBookingStatus::class);
        $this->mock(UpdateBookingStatus::class)->shouldReceive('execute')
            ->andReturnUsing(function (Booking $booking, string $status, string $actor, ?string $reason, bool $notify) use ($real): array {
                if ((int) $booking->idtenant === (int) $this->tenant->id) {
                    throw new \RuntimeException('Simulated tenant failure');
                }

                return $real->execute($booking, $status, $actor, $reason, $notify);
            });

        $this->artisan('bookings:complete-ended')->assertSuccessful();
        $this->assertSame('completed', $next->fresh()->status);
        $this->assertNull(app(TenantContext::class)->getTenantId());
    }

    public function test_booking_log_uses_the_application_clock(): void
    {
        $booking = $this->paidBooking('2026-11-16', '09:00', '10:00');
        $log = BookingLog::record($booking->id, 'viewed', 'Dilihat');
        $this->assertSame(now()->format('Y-m-d H:i:s'), $log->fresh()->created_at->format('Y-m-d H:i:s'));
    }

    private function paidBooking(string $date, string $start, string $end, ?Tenant $tenant = null, ?Service $service = null): Booking
    {
        $tenant ??= $this->tenant;
        $service ??= $this->service;
        $slot = Schedule::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'tanggal' => $date,
            'jam_mulai' => $start.':00', 'jam_selesai' => $end.':00', 'status' => 'tersedia',
        ]);

        return Booking::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'idschedule' => $slot->id,
            'tanggalbooking' => $date, 'jam' => $start.':00', 'status' => 'paid',
        ]);
    }

    private function openRefund(Booking $booking): void
    {
        $payment = Payment::factory()->create(['idtenant' => $this->tenant->id, 'idbooking' => $booking->id, 'tipe' => 'booking', 'status' => 'sukses', 'jumlah' => 100000]);
        $booking->update(['idpayment' => $payment->id]);
        RefundRequest::create([
            'idtenant' => $this->tenant->id, 'idpayment' => $payment->id, 'booking_id' => $booking->id, 'reason' => 'Batal',
            'bank_nama' => 'BCA', 'nomor_rekening' => '123', 'nama_pemilik_rekening' => 'Rani', 'status' => 'open',
        ]);
    }
}

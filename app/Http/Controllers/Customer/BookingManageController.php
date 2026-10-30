<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Booking\CancelBooking;
use App\Actions\Booking\RescheduleBooking;
use App\Actions\CustomerAccess\ReviewBooking;
use App\Actions\Schedule\GetAvailableSchedules;
use App\Domain\Booking\BookingRules;
use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessOperation;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\Review;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingManageController extends Controller
{
    use ClearsBookingCache;

    // ── Payment Group Management (New Primary Model) ───────────────────────────

    private function resolvePaymentGroup(string $orderId): Payment
    {
        $context = request()->attributes->get('customer_access');
        abort_unless($context instanceof AccessContext && $context->payment !== null, 404);
        $context->authorize(AccessOperation::Manage, $context->payment);
        abort_unless($context->payment->order_id === $orderId, 404);
        $payment = $context->payment;
        $payment->setRelation('bookings', $context->bookings->load(['layanan', 'schedule', 'tenant.user', 'review']));
        $payment->load('tenant');

        return $payment;
    }

    /**
     * Show payment-level group management page.
     */
    public function showPaymentGroup(Request $request, string $orderId)
    {
        $payment = $this->resolvePaymentGroup($orderId);
        $bookings = $payment->bookings;
        $tenant = $payment->tenant;

        // Log view event for the payment group (throttled)
        $viewKey = "manage:payment_viewed:{$payment->id}:".session()->getId();
        if (! Cache::has($viewKey)) {
            foreach ($bookings as $bk) {
                BookingLog::record($bk->id, 'viewed', 'Halaman manajemen reservasi dibuka oleh customer (Payment Group).');
            }
            Cache::put($viewKey, true, now()->addMinutes(30));
        }

        $isMultiSlot = $bookings->count() > 1;

        return view('customer.manage.payment-show', [
            'payment' => $payment,
            'bookings' => $bookings,
            'tenant' => $tenant,
            'isMultiSlot' => $isMultiSlot,
        ]);
    }

    /**
     * Show payment-level group invoice page.
     */
    public function invoicePaymentGroup(Request $request, string $orderId)
    {
        $payment = $this->resolvePaymentGroup($orderId);

        if ($payment->status !== 'sukses') {
            abort(404, 'Invoice hanya tersedia untuk pembayaran yang berhasil.');
        }

        $bookings = $payment->bookings;
        $tenant = $payment->tenant;
        $booking = $bookings->first();

        return view('customer.manage.payment-invoice', [
            'payment' => $payment,
            'bookings' => $bookings,
            'booking' => $booking,
            'tenant' => $tenant,
        ]);
    }

    // ── Shared token validation (Scoped) ───────────────────────────────────────

    private function resolveBooking(string $bookingCode, string $scope = 'show'): Booking
    {
        $context = request()->attributes->get('customer_access');
        abort_unless($context instanceof AccessContext, 404);
        $booking = $context->bookings->firstWhere('booking_code', $bookingCode);
        abort_unless($booking instanceof Booking, 404);
        $operation = match ($scope) {
            'cancel' => AccessOperation::CancelBooking,
            'reschedule' => AccessOperation::Reschedule,
            'review' => AccessOperation::Review,
            'invoice' => AccessOperation::Invoice,
            default => AccessOperation::Manage,
        };
        $context->authorize($operation, booking: $booking);
        $booking->load(['tenant.user', 'layanan', 'payment', 'logs', 'refund', 'review']);

        return $booking;
    }

    public function show(Request $request, string $bookingCode)
    {
        $booking = $this->resolveBooking($bookingCode, 'show');

        // Log the view event (throttled — only once per session per booking)
        $viewKey = "manage:viewed:{$booking->id}:".session()->getId();
        if (! Cache::has($viewKey)) {
            BookingLog::record($booking->id, 'viewed', 'Halaman manajemen booking dibuka oleh customer.');
            Cache::put($viewKey, true, now()->addMinutes(30));
        }

        $isMultiSlot = $booking->isMultiSlot();
        $canCancel = ! $isMultiSlot && $booking->canBeCancelled();
        $canReschedule = ! $isMultiSlot && $booking->canBeRescheduled();

        // Build cancel deadline message
        $cancelDeadline = null;
        $rescheduleDeadline = null;
        if ($booking->status === 'paid') {
            $tenant = $booking->tenant;
            $bookingDateTime = Carbon::parse($booking->tanggalbooking->toDateString().' '.$booking->jam);
            $cancelHours = $tenant->cancel_before_hours ?? 24;
            $rescheduleHours = $tenant->reschedule_before_hours ?? 24;

            $cancelDeadline = $bookingDateTime->copy()->subHours($cancelHours);
            $rescheduleDeadline = $bookingDateTime->copy()->subHours($rescheduleHours);
        }

        return view('customer.manage.show', [
            'booking' => $booking,
            'isMultiSlot' => $isMultiSlot,
            'canCancel' => $canCancel,
            'canReschedule' => $canReschedule,
            'cancelDeadline' => $cancelDeadline,
            'rescheduleDeadline' => $rescheduleDeadline,
        ]);
    }

    // ── Cancel booking ─────────────────────────────────────────────────────────

    public function cancel(Request $request, string $bookingCode, CancelBooking $cancelBooking)
    {
        $booking = $this->resolveBooking($bookingCode, 'cancel');

        $result = $cancelBooking->execute($booking, 'customer', access: $request->attributes->get('customer_access'));

        if (! $result['success']) {
            return back()->withErrors(['cancel' => $result['error']]);
        }

        return redirect()
            ->route('booking.manage', ['booking_code' => $bookingCode])
            ->with('success', 'Booking berhasil dibatalkan. Email konfirmasi telah dikirim.');
    }

    // ── Show reschedule form ───────────────────────────────────────────────────

    public function showReschedule(Request $request, string $bookingCode)
    {
        $booking = $this->resolveBooking($bookingCode, 'reschedule');

        if ($booking->isMultiSlot()) {
            return redirect()
                ->route('booking.manage', ['booking_code' => $bookingCode])
                ->withErrors(['reschedule' => 'Booking multi-slot tidak dapat dibatalkan atau dijadwalkan ulang per slot secara individual. Silakan hubungi pengelola bisnis.']);
        }

        if ($booking->status !== 'paid') {
            return redirect()
                ->route('booking.manage', ['booking_code' => $bookingCode])
                ->withErrors(['reschedule' => 'Hanya booking berstatus "paid" yang dapat dijadwalkan ulang.']);
        }

        if (! $booking->canBeRescheduled()) {
            $rescheduleHours = $booking->tenant->reschedule_before_hours ?? 24;

            return redirect()
                ->route('booking.manage', ['booking_code' => $bookingCode])
                ->withErrors(['reschedule' => "Reschedule hanya diizinkan minimal {$rescheduleHours} jam sebelum jadwal."]);
        }

        // Build availability payload (same logic as BookingController)
        $wib = 'Asia/Jakarta';
        $nowWib = Carbon::now($wib);
        $minDate = Carbon::today($wib);
        $maxDate = Carbon::today($wib)->addDays(30);
        $tenant = $booking->tenant;
        $service = $booking->layanan;

        $todayStr = $minDate->toDateString();
        $nowTimeStr = $nowWib->format('H:i');

        $availabilityKey = sprintf(
            'tenant:%s:service:%s:availability:%s:%s:reschedule:%s',
            $tenant->id,
            $service->id,
            $minDate->toDateString(),
            $maxDate->toDateString(),
            $booking->id
        );

        $availabilityPayload = Cache::remember($availabilityKey, now()->addSeconds(60), function () use ($tenant, $service, $minDate, $maxDate, $booking, $todayStr, $nowTimeStr) {
            $rows = DB::table('schedules')
                ->leftJoin('bookings', function ($join) use ($booking) {
                    $join->on('schedules.id', '=', 'bookings.idschedule')
                        ->where('bookings.id', '!=', $booking->id); // Exclude current booking's slot
                    BookingRules::applyOccupiesSlotCondition($join, 'bookings.');
                })
                ->where('schedules.idtenant', $tenant->id)
                ->where('schedules.idlayanan', $service->id)
                ->where('schedules.status', 'tersedia')
                // Dates closed by the owner are not offered (BUG-T5-08); the claim refuses them too
                ->whereNotExists(function ($closed) use ($booking) {
                    $closed->selectRaw('1')
                        ->from('owner_blocked_dates')
                        ->where('owner_blocked_dates.idtenant', $booking->idtenant)
                        ->whereRaw('substr(owner_blocked_dates.tanggal, 1, 10) = substr(schedules.tanggal, 1, 10)');
                })
                ->whereBetween('schedules.tanggal', [$minDate->toDateString(), $maxDate->toDateString()])
                ->groupBy('schedules.tanggal')
                ->orderBy('schedules.tanggal')
                ->select([
                    'schedules.tanggal',
                    DB::raw('count(distinct schedules.id) as total_slots'),
                    DB::raw("count(distinct case when bookings.id is null and (substr(schedules.tanggal, 1, 10) > '{$todayStr}' or (substr(schedules.tanggal, 1, 10) = '{$todayStr}' and substr(schedules.jam_mulai, 1, 5) > '{$nowTimeStr}')) then schedules.id end) as available_slots"),
                ])
                ->get();

            return $rows->map(function ($row) {
                return [
                    'date' => Carbon::parse($row->tanggal)->toDateString(),
                    'total_slots' => (int) $row->total_slots,
                    'available_slots' => (int) $row->available_slots,
                ];
            })->values()->all();
        });

        return view('customer.manage.reschedule', [
            'booking' => $booking,
            'availabilityPayload' => $availabilityPayload,
            'minDate' => $minDate->toDateString(),
            'maxDate' => $maxDate->toDateString(),
        ]);
    }

    // ── Process reschedule ─────────────────────────────────────────────────────

    public function reschedule(Request $request, string $bookingCode, RescheduleBooking $rescheduleBooking)
    {
        $booking = $this->resolveBooking($bookingCode, 'reschedule');

        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'schedule_id' => ['required', 'integer'],
        ]);

        $newDate = Carbon::parse($validated['tanggal'])->toDateString();
        $newScheduleId = (int) $validated['schedule_id'];

        $result = $rescheduleBooking->execute($booking, $newScheduleId, 'customer', null, $newDate, $request->attributes->get('customer_access'));

        if (! $result['success']) {
            return back()->withErrors(['reschedule' => $result['error']]);
        }

        return redirect()
            ->route('booking.manage', ['booking_code' => $bookingCode])
            ->with('success', 'Jadwal booking berhasil diubah. Email konfirmasi telah dikirim.');
    }

    // ── Get available time slots for reschedule (AJAX) ─────────────────────────

    public function getTimeSlots(Request $request, string $bookingCode)
    {
        $booking = $this->resolveBooking($bookingCode, 'reschedule');

        $tanggal = $request->query('tanggal');
        if (! $tanggal) {
            return response()->json(['error' => 'Tanggal diperlukan.'], 422);
        }

        $selectedDate = Carbon::parse($tanggal)->toDateString();
        $tenant = $booking->tenant;
        $service = $booking->layanan;

        $slots = app(GetAvailableSchedules::class)
            ->getCustomerRescheduleSlots($tenant, $service, $selectedDate, (int) $booking->id);

        return response()->json(['slots' => $slots]);
    }

    // ── Invoice ────────────────────────────────────────────────────────────────

    public function invoice(Request $request, string $bookingCode)
    {
        $booking = $this->resolveBooking($bookingCode, 'invoice');

        if (! $booking->payment || $booking->payment->status !== 'sukses' || ! in_array($booking->status, ['paid', 'completed'])) {
            abort(404, 'Invoice hanya tersedia untuk pembayaran yang berhasil.');
        }

        return view('customer.manage.invoice', [
            'booking' => $booking,
            'invoiceDate' => $booking->payment?->updated_at ?? $booking->created_at,
        ]);
    }

    // ── Customer Review ───────────────────────────────────────────────────────

    public function storeReview(Request $request, string $bookingCode)
    {
        $booking = $this->resolveBooking($bookingCode, 'review');

        // Guard: only completed bookings can be reviewed
        if ($booking->status !== 'completed') {
            return back()->withErrors(['review' => 'Ulasan hanya dapat diberikan untuk booking yang telah selesai.']);
        }

        // Guard: one review per booking
        if (Review::where('idbooking', $booking->id)->exists()) {
            return back()->withErrors(['review' => 'Anda sudah memberikan ulasan untuk booking ini.']);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'komentar' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Silakan pilih rating bintang 1 sampai 5.',
            'rating.min' => 'Rating minimal 1 bintang.',
            'rating.max' => 'Rating maksimal 5 bintang.',
            'komentar.max' => 'Komentar maksimal 1000 karakter.',
        ]);

        $saved = app(ReviewBooking::class)->execute(
            $request->attributes->get('customer_access'), $booking, $validated
        );
        if (! $saved) {
            return back()->withErrors(['review' => 'Ulasan tidak dapat disimpan.']);
        }

        return redirect()->route('booking.manage', [
            'booking_code' => $booking->booking_code,
        ])->with('success', 'Terima kasih atas ulasan Anda! Masukan Anda sangat berarti bagi kami.');
    }

    // ── Cache helpers ─────────────────────────────────────────────────────────

    private function clearBookingCaches(int $tenantId, int $serviceId, string $date, ?int $bookingId = null): void
    {
        $this->clearBookingAvailabilityCache($tenantId, $serviceId, $date);

        if ($bookingId) {
            $minDate = Carbon::today()->toDateString();
            $maxDate = Carbon::today()->addDays(30)->toDateString();
            Cache::forget("tenant:{$tenantId}:service:{$serviceId}:availability:{$minDate}:{$maxDate}:reschedule:{$bookingId}");
            Cache::forget("tenant:{$tenantId}:service:{$serviceId}:schedules:{$date}:reschedule:{$bookingId}");
        }
    }
}

<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingRescheduledMail;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Review;
use App\Models\Schedule;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Actions\Booking\CancelBooking;
use App\Actions\Booking\RescheduleBooking;
use App\Http\Requests\Booking\RescheduleBookingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingManageController extends Controller
{
    use ClearsBookingCache;

    // ── Payment Group Management (New Primary Model) ───────────────────────────

    /**
     * Resolve and validate a payment group by order_id + manage_token.
     * Aborts 404 if not found, 403 if token invalid or tenant mismatch.
     */
    private function resolvePaymentGroup(string $orderId, ?string $token): Payment
    {
        $payment = Payment::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('order_id', $orderId)
            ->first();

        if (!$payment) {
            abort(404, 'Data pembayaran reservasi tidak ditemukan.');
        }

        // Validate manage_token using constant-time hash_equals
        $validToken = $token && hash_equals((string) $payment->manage_token, (string) $token);

        if (!$validToken) {
            abort(403, 'Token tidak valid. Pastikan Anda menggunakan link yang dikirim ke email Anda.');
        }

        if ($payment->tipe !== 'booking') {
            abort(403, 'Transaksi ini bukan merupakan pembayaran booking.');
        }

        // Set tenant context
        app(\App\Support\TenantContext::class)->setTenantId($payment->idtenant);

        // Load all bookings associated with this payment
        $bookings = Booking::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('idpayment', $payment->id)
            ->with(['layanan', 'schedule', 'tenant.user', 'review'])
            ->orderBy('tanggalbooking')
            ->orderBy('jam')
            ->get();

        if ($bookings->isEmpty()) {
            Log::error('Inconsistent Payment Group: Payment has 0 bookings', ['order_id' => $orderId, 'payment_id' => $payment->id]);
            abort(404, 'Data reservasi tidak ditemukan.');
        }

        // Tenant safety check
        foreach ($bookings as $bk) {
            if ((int) $bk->idtenant !== (int) $payment->idtenant || (int) $bk->idpayment !== (int) $payment->id) {
                Log::error('Inconsistent Payment Group: Booking tenant mismatch', [
                    'payment_id' => $payment->id,
                    'booking_id' => $bk->id,
                    'payment_tenant' => $payment->idtenant,
                    'booking_tenant' => $bk->idtenant,
                ]);
                abort(403, 'Akses data reservasi tidak konsisten.');
            }
        }

        $payment->setRelation('bookings', $bookings);
        $payment->load('tenant');

        return $payment;
    }

    /**
     * Show payment-level group management page.
     */
    public function showPaymentGroup(Request $request, string $orderId)
    {
        $token   = $request->query('token');
        $payment = $this->resolvePaymentGroup($orderId, $token);
        $bookings = $payment->bookings;
        $tenant   = $payment->tenant;

        // Log view event for the payment group (throttled)
        $viewKey = "manage:payment_viewed:{$payment->id}:" . session()->getId();
        if (!Cache::has($viewKey)) {
            foreach ($bookings as $bk) {
                BookingLog::record($bk->id, 'viewed', 'Halaman manajemen reservasi dibuka oleh customer (Payment Group).');
            }
            Cache::put($viewKey, true, now()->addMinutes(30));
        }

        $isMultiSlot = $bookings->count() > 1;

        return view('customer.manage.payment-show', [
            'payment'     => $payment,
            'bookings'    => $bookings,
            'tenant'      => $tenant,
            'token'       => $token,
            'isMultiSlot' => $isMultiSlot,
        ]);
    }

    /**
     * Show payment-level group invoice page.
     */
    public function invoicePaymentGroup(Request $request, string $orderId)
    {
        $token   = $request->query('token');
        $payment = $this->resolvePaymentGroup($orderId, $token);

        if ($payment->status !== 'sukses') {
            abort(404, 'Invoice hanya tersedia untuk pembayaran yang berhasil.');
        }

        $bookings = $payment->bookings;
        $tenant   = $payment->tenant;
        $booking  = $bookings->first();

        return view('customer.manage.payment-invoice', [
            'payment'  => $payment,
            'bookings' => $bookings,
            'booking'  => $booking,
            'tenant'   => $tenant,
            'token'    => $token,
        ]);
    }

    // ── Shared token validation (Legacy) ───────────────────────────────────────

    /**
     * Resolve and validate a booking by booking_code + token.
     * Aborts 404 if not found, 403 if token invalid.
     */
    private function resolveBooking(string $bookingCode, ?string $token): Booking
    {
        $booking = Booking::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('booking_code', $bookingCode)
            ->first();

        if (!$booking) {
            abort(404, 'Booking tidak ditemukan.');
        }

        // Token must match either cancellation, reschedule token, or payment manage_token
        $validToken = $token && (
            hash_equals((string) $booking->cancellation_token, $token) ||
            hash_equals((string) $booking->reschedule_token, $token) ||
            ($booking->payment && !empty($booking->payment->manage_token) && hash_equals((string) $booking->payment->manage_token, $token))
        );

        if (!$validToken) {
            abort(403, 'Token tidak valid. Pastikan Anda menggunakan link yang dikirim ke email Anda.');
        }

        app(\App\Support\TenantContext::class)->setTenantId($booking->idtenant);

        $booking->load(['tenant.user', 'layanan', 'payment', 'logs', 'refund', 'review']);

        return $booking;
    }

    // ── Show booking detail ────────────────────────────────────────────────────

    public function show(Request $request, string $bookingCode)
    {
        $token   = $request->query('token');
        $booking = $this->resolveBooking($bookingCode, $token);

        // Log the view event (throttled — only once per session per booking)
        $viewKey = "manage:viewed:{$booking->id}:" . session()->getId();
        if (!Cache::has($viewKey)) {
            BookingLog::record($booking->id, 'viewed', 'Halaman manajemen booking dibuka oleh customer.');
            Cache::put($viewKey, true, now()->addMinutes(30));
        }

        $isMultiSlot  = $booking->isMultiSlot();
        $canCancel    = !$isMultiSlot && $booking->canBeCancelled();
        $canReschedule = !$isMultiSlot && $booking->canBeRescheduled();

        // Build cancel deadline message
        $cancelDeadline   = null;
        $rescheduleDeadline = null;
        if ($booking->status === 'paid') {
            $tenant           = $booking->tenant;
            $bookingDateTime  = Carbon::parse($booking->tanggalbooking->toDateString() . ' ' . $booking->jam);
            $cancelHours      = $tenant->cancel_before_hours ?? 24;
            $rescheduleHours  = $tenant->reschedule_before_hours ?? 24;

            $cancelDeadline     = $bookingDateTime->copy()->subHours($cancelHours);
            $rescheduleDeadline = $bookingDateTime->copy()->subHours($rescheduleHours);
        }

        return view('customer.manage.show', [
            'booking'             => $booking,
            'token'               => $token,
            'isMultiSlot'         => $isMultiSlot,
            'canCancel'           => $canCancel,
            'canReschedule'       => $canReschedule,
            'cancelDeadline'      => $cancelDeadline,
            'rescheduleDeadline'  => $rescheduleDeadline,
        ]);
    }

    // ── Cancel booking ─────────────────────────────────────────────────────────

    public function cancel(Request $request, string $bookingCode, CancelBooking $cancelBooking)
    {
        $token   = $request->query('token');
        $booking = $this->resolveBooking($bookingCode, $token);

        $result = $cancelBooking->execute($booking, 'customer');

        if (!$result['success']) {
            return back()->withErrors(['cancel' => $result['error']]);
        }

        return redirect()
            ->route('booking.manage', ['booking_code' => $bookingCode, 'token' => $token])
            ->with('success', 'Booking berhasil dibatalkan. Email konfirmasi telah dikirim.');
    }

    // ── Show reschedule form ───────────────────────────────────────────────────

    public function showReschedule(Request $request, string $bookingCode)
    {
        $token   = $request->query('token');
        $booking = $this->resolveBooking($bookingCode, $token);

        if ($booking->isMultiSlot()) {
            return redirect()
                ->route('booking.manage', ['booking_code' => $bookingCode, 'token' => $token])
                ->withErrors(['reschedule' => 'Booking multi-slot tidak dapat dibatalkan atau dijadwalkan ulang per slot secara individual. Silakan hubungi pengelola bisnis.']);
        }

        if ($booking->status !== 'paid') {
            return redirect()
                ->route('booking.manage', ['booking_code' => $bookingCode, 'token' => $token])
                ->withErrors(['reschedule' => 'Hanya booking berstatus "paid" yang dapat dijadwalkan ulang.']);
        }

        if (!$booking->canBeRescheduled()) {
            $rescheduleHours = $booking->tenant->reschedule_before_hours ?? 24;
            return redirect()
                ->route('booking.manage', ['booking_code' => $bookingCode, 'token' => $token])
                ->withErrors(['reschedule' => "Reschedule hanya diizinkan minimal {$rescheduleHours} jam sebelum jadwal."]);
        }

        // Build availability payload (same logic as BookingController)
        $wib     = 'Asia/Jakarta';
        $nowWib  = Carbon::now($wib);
        $minDate = Carbon::today($wib);
        $maxDate = Carbon::today($wib)->addDays(30);
        $tenant  = $booking->tenant;
        $service = $booking->layanan;

        $todayStr   = $minDate->toDateString();
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
                        ->whereIn('bookings.status', ['pending', 'paid', 'completed'])
                        ->where('bookings.id', '!=', $booking->id); // Exclude current booking's slot
                })
                ->where('schedules.idtenant', $tenant->id)
                ->where('schedules.idlayanan', $service->id)
                ->where('schedules.status', 'tersedia')
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
                    'date'            => Carbon::parse($row->tanggal)->toDateString(),
                    'total_slots'     => (int) $row->total_slots,
                    'available_slots' => (int) $row->available_slots,
                ];
            })->values()->all();
        });

        return view('customer.manage.reschedule', [
            'booking'            => $booking,
            'token'              => $token,
            'availabilityPayload' => $availabilityPayload,
            'minDate'            => $minDate->toDateString(),
            'maxDate'            => $maxDate->toDateString(),
        ]);
    }

    // ── Process reschedule ─────────────────────────────────────────────────────

    public function reschedule(Request $request, string $bookingCode, RescheduleBooking $rescheduleBooking)
    {
        $token   = $request->query('token');
        $booking = $this->resolveBooking($bookingCode, $token);

        $validated = $request->validate([
            'tanggal'     => ['required', 'date', 'after_or_equal:today'],
            'schedule_id' => ['required', 'integer'],
        ]);

        $newDate       = Carbon::parse($validated['tanggal'])->toDateString();
        $newScheduleId = (int) $validated['schedule_id'];

        $result = $rescheduleBooking->execute($booking, $newScheduleId, 'customer', null, $newDate);

        if (!$result['success']) {
            return back()->withErrors(['reschedule' => $result['error']]);
        }

        return redirect()
            ->route('booking.manage', ['booking_code' => $bookingCode, 'token' => $token])
            ->with('success', 'Jadwal booking berhasil diubah. Email konfirmasi telah dikirim.');
    }

    // ── Get available time slots for reschedule (AJAX) ─────────────────────────

    public function getTimeSlots(Request $request, string $bookingCode)
    {
        $token   = $request->query('token');
        $booking = $this->resolveBooking($bookingCode, $token);

        $tanggal = $request->query('tanggal');
        if (!$tanggal) {
            return response()->json(['error' => 'Tanggal diperlukan.'], 422);
        }

        $selectedDate = Carbon::parse($tanggal)->toDateString();
        $tenant       = $booking->tenant;
        $service      = $booking->layanan;

        $cacheKey = "tenant:{$tenant->id}:service:{$service->id}:schedules:{$selectedDate}:reschedule:{$booking->id}";

        $slots = Cache::remember($cacheKey, now()->addSeconds(300), function () use ($tenant, $service, $selectedDate, $booking) {
            return DB::table('schedules')
                ->leftJoin('bookings', function ($join) use ($booking) {
                    $join->on('schedules.id', '=', 'bookings.idschedule')
                        ->whereIn('bookings.status', ['pending', 'paid', 'completed'])
                        ->where('bookings.id', '!=', $booking->id);
                })
                ->where('schedules.idtenant', $tenant->id)
                ->where('schedules.idlayanan', $service->id)
                ->where('schedules.status', 'tersedia')
                ->whereDate('schedules.tanggal', $selectedDate)
                ->groupBy('schedules.id', 'schedules.jam_mulai', 'schedules.jam_selesai', 'schedules.tanggal')
                ->orderBy('schedules.jam_mulai')
                ->select([
                    'schedules.id',
                    'schedules.jam_mulai',
                    'schedules.jam_selesai',
                    DB::raw('count(bookings.id) as booking_count'),
                ])
                ->get()
                ->map(function ($row) use ($selectedDate) {
                    $wib = 'Asia/Jakarta';
                    $nowWib = Carbon::now($wib);
                    $selectedDateCarbon = Carbon::parse($selectedDate, $wib);
                    $isToday = $selectedDateCarbon->isSameDay($nowWib);
                    $isPastDate = $selectedDateCarbon->lt($nowWib->copy()->startOfDay());
                    $slotDateTime = Carbon::parse($selectedDate . ' ' . $row->jam_mulai, $wib);
                    $isBooked    = $row->booking_count > 0;
                    $isPast      = $isPastDate || ($isToday && $slotDateTime->lessThanOrEqualTo($nowWib));
                    $isAvailable = !$isBooked && !$isPast;

                    return [
                        'id'           => (int) $row->id,
                        'jam_mulai'    => $slotDateTime->format('H:i'),
                        'jam_selesai'  => Carbon::parse($selectedDate . ' ' . $row->jam_selesai, $wib)->format('H:i'),
                        'is_available' => $isAvailable,
                        'is_booked'    => $isBooked,
                        'is_past'      => $isPast,
                    ];
                })
                ->all();
        });

        return response()->json(['slots' => $slots]);
    }

    // ── Invoice ────────────────────────────────────────────────────────────────

    public function invoice(Request $request, string $bookingCode)
    {
        $token   = $request->query('token');
        $booking = $this->resolveBooking($bookingCode, $token);

        if (!$booking->payment || $booking->payment->status !== 'sukses' || !in_array($booking->status, ['paid', 'completed'])) {
            abort(404, 'Invoice hanya tersedia untuk pembayaran yang berhasil.');
        }

        return view('customer.manage.invoice', [
            'booking'    => $booking,
            'token'      => $token,
            'invoiceDate' => $booking->payment?->updated_at ?? $booking->created_at,
        ]);
    }

    // ── Customer Review ───────────────────────────────────────────────────────

    public function storeReview(Request $request, string $bookingCode)
    {
        $token   = $request->input('token') ?? $request->query('token');
        $booking = $this->resolveBooking($bookingCode, $token);

        // Guard: only completed bookings can be reviewed
        if ($booking->status !== 'completed') {
            return back()->withErrors(['review' => 'Ulasan hanya dapat diberikan untuk booking yang telah selesai.']);
        }

        // Guard: one review per booking
        if (Review::where('idbooking', $booking->id)->exists()) {
            return back()->withErrors(['review' => 'Anda sudah memberikan ulasan untuk booking ini.']);
        }

        $validated = $request->validate([
            'rating'   => ['required', 'integer', 'min:1', 'max:5'],
            'komentar' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Silakan pilih rating bintang 1 sampai 5.',
            'rating.min'      => 'Rating minimal 1 bintang.',
            'rating.max'      => 'Rating maksimal 5 bintang.',
            'komentar.max'    => 'Komentar maksimal 1000 karakter.',
        ]);

        Review::create([
            'idtenant'  => $booking->idtenant,
            'idbooking' => $booking->id,
            'rating'    => (int) $validated['rating'],
            'komentar'  => $validated['komentar'] ?? null,
            'is_hidden' => false,
        ]);

        BookingLog::record(
            $booking->id,
            'reviewed',
            "Customer memberikan ulasan bintang {$validated['rating']}.",
            ['rating' => (int) $validated['rating']]
        );

        return redirect()->route('booking.manage', [
            'booking_code' => $booking->booking_code,
            'token'        => $token,
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

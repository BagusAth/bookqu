<?php

namespace App\Http\Controllers;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingRescheduledMail;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Refund;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingManageController extends Controller
{
    // ── Shared token validation ────────────────────────────────────────────────

    /**
     * Resolve and validate a booking by booking_code + token.
     * Aborts 404 if not found, 403 if token invalid.
     */
    private function resolveBooking(string $bookingCode, ?string $token): Booking
    {
        $booking = Booking::where('booking_code', $bookingCode)
            ->with(['tenant', 'layanan', 'payment', 'logs', 'refund'])
            ->first();

        if (!$booking) {
            abort(404, 'Booking tidak ditemukan.');
        }

        // Token must match either cancellation or reschedule token
        $validToken = $token && (
            hash_equals((string) $booking->cancellation_token, $token) ||
            hash_equals((string) $booking->reschedule_token, $token)
        );

        if (!$validToken) {
            abort(403, 'Token tidak valid. Pastikan Anda menggunakan link yang dikirim ke email Anda.');
        }

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

        $canCancel    = $booking->canBeCancelled();
        $canReschedule = $booking->canBeRescheduled();

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
            'canCancel'           => $canCancel,
            'canReschedule'       => $canReschedule,
            'cancelDeadline'      => $cancelDeadline,
            'rescheduleDeadline'  => $rescheduleDeadline,
        ]);
    }

    // ── Cancel booking ─────────────────────────────────────────────────────────

    public function cancel(Request $request, string $bookingCode)
    {
        $token   = $request->query('token');
        $booking = $this->resolveBooking($bookingCode, $token);

        // ── Guard: only paid bookings can be cancelled ──
        if ($booking->status !== 'paid') {
            return back()->withErrors(['cancel' => 'Booking ini tidak dapat dibatalkan (status: ' . $booking->status . ').']);
        }

        // ── Guard: cancellation policy ──
        if (!$booking->canBeCancelled()) {
            $cancelHours = $booking->tenant->cancel_before_hours ?? 24;
            return back()->withErrors([
                'cancel' => "Booking tidak dapat dibatalkan. Pembatalan hanya diizinkan minimal {$cancelHours} jam sebelum jadwal.",
            ]);
        }

        try {
            DB::transaction(function () use ($booking) {
                // 1. Update booking status
                $booking->update(['status' => 'cancelled']);

                // 2. Release schedule slot (status stays 'tersedia', just the booking is gone)
                //    The slot becomes available again automatically since booking status changed.

                // 3. Create refund record
                if ($booking->payment && $booking->payment->status === 'sukses') {
                    Refund::create([
                        'booking_id' => $booking->id,
                        'payment_id' => $booking->payment->id,
                        'jumlah'     => $booking->payment->jumlah,
                        'status'     => 'pending',
                        'catatan'    => 'Refund otomatis dari pembatalan booking oleh customer.',
                    ]);
                }

                // 4. Audit log
                BookingLog::record(
                    $booking->id,
                    'cancelled',
                    'Booking dibatalkan oleh customer.',
                    ['cancelled_at' => now()->toIso8601String()]
                );

                // 5. Clear availability cache
                $this->clearBookingCaches(
                    $booking->idtenant,
                    $booking->idlayanan,
                    $booking->tanggalbooking->toDateString()
                );
            });

            // 6. Send cancellation email
            try {
                $booking->refresh()->load(['tenant', 'layanan', 'payment', 'refund']);
                Mail::to($booking->email)->send(new BookingCancelledMail($booking));
            } catch (\Throwable $e) {
                Log::warning('BookingManage: Failed to send cancellation email', ['error' => $e->getMessage()]);
            }

            return redirect()
                ->route('booking.manage', ['booking_code' => $bookingCode, 'token' => $token])
                ->with('success', 'Booking berhasil dibatalkan. Email konfirmasi telah dikirim.');
        } catch (\Throwable $e) {
            Log::error('BookingManage: Cancel failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->withErrors(['cancel' => 'Terjadi kesalahan saat membatalkan booking. Silakan coba lagi.']);
        }
    }

    // ── Show reschedule form ───────────────────────────────────────────────────

    public function showReschedule(Request $request, string $bookingCode)
    {
        $token   = $request->query('token');
        $booking = $this->resolveBooking($bookingCode, $token);

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
        $minDate = Carbon::today();
        $maxDate = Carbon::today()->addDays(30);
        $tenant  = $booking->tenant;
        $service = $booking->layanan;

        $availabilityKey = sprintf(
            'tenant:%s:service:%s:availability:%s:%s',
            $tenant->id,
            $service->id,
            $minDate->toDateString(),
            $maxDate->toDateString()
        );

        $availabilityPayload = Cache::remember($availabilityKey, now()->addSeconds(1800), function () use ($tenant, $service, $minDate, $maxDate, $booking) {
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
                    DB::raw('count(distinct case when bookings.id is null then schedules.id end) as available_slots'),
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

    public function reschedule(Request $request, string $bookingCode)
    {
        $token   = $request->query('token');
        $booking = $this->resolveBooking($bookingCode, $token);

        if ($booking->status !== 'paid') {
            return back()->withErrors(['reschedule' => 'Booking tidak dapat dijadwalkan ulang.']);
        }

        if (!$booking->canBeRescheduled()) {
            return back()->withErrors(['reschedule' => 'Waktu reschedule telah habis.']);
        }

        $validated = $request->validate([
            'tanggal'     => ['required', 'date', 'after_or_equal:today'],
            'schedule_id' => ['required', 'integer'],
        ]);

        $newDate       = Carbon::parse($validated['tanggal'])->toDateString();
        $newScheduleId = (int) $validated['schedule_id'];
        $tenant        = $booking->tenant;
        $service       = $booking->layanan;

        try {
            DB::transaction(function () use ($booking, $newDate, $newScheduleId, $tenant, $service) {
                // Lock and validate new schedule slot
                $schedule = DB::table('schedules')
                    ->where('id', $newScheduleId)
                    ->where('idtenant', $tenant->id)
                    ->where('idlayanan', $service->id)
                    ->whereDate('tanggal', $newDate)
                    ->where('status', 'tersedia')
                    ->lockForUpdate()
                    ->first();

                if (!$schedule) {
                    throw new \Exception('SLOT_NOT_FOUND');
                }

                // Check no active booking already occupies this slot (excluding current booking)
                $slotTaken = DB::table('bookings')
                    ->where('idschedule', $newScheduleId)
                    ->whereIn('status', ['pending', 'paid', 'completed'])
                    ->where('id', '!=', $booking->id)
                    ->exists();

                if ($slotTaken) {
                    throw new \Exception('SLOT_TAKEN');
                }

                // Save old schedule for history
                $oldDate       = $booking->tanggalbooking->toDateString();
                $oldTime       = $booking->jam;
                $oldScheduleId = $booking->idschedule;

                // Update booking to new slot
                $booking->update([
                    'idschedule'               => $newScheduleId,
                    'tanggalbooking'            => $newDate,
                    'jam'                       => Carbon::createFromFormat('H:i:s', $schedule->jam_mulai)->format('H:i'),
                    'rescheduled_from_date'     => $oldDate,
                    'rescheduled_from_time'     => $oldTime,
                    'rescheduled_from_schedule' => $oldScheduleId,
                ]);

                // Audit log
                BookingLog::record(
                    $booking->id,
                    'rescheduled',
                    'Jadwal booking diubah oleh customer.',
                    [
                        'from_date'     => $oldDate,
                        'from_time'     => $oldTime,
                        'from_schedule' => $oldScheduleId,
                        'to_date'       => $newDate,
                        'to_time'       => $schedule->jam_mulai,
                        'to_schedule'   => $newScheduleId,
                    ]
                );

                // Clear caches for both old and new dates
                $this->clearBookingCaches($tenant->id, $service->id, $oldDate);
                $this->clearBookingCaches($tenant->id, $service->id, $newDate);
            });

            // Send reschedule email
            try {
                $booking->refresh()->load(['tenant', 'layanan']);
                Mail::to($booking->email)->send(new BookingRescheduledMail($booking));
            } catch (\Throwable $e) {
                Log::warning('BookingManage: Failed to send reschedule email', ['error' => $e->getMessage()]);
            }

            return redirect()
                ->route('booking.manage', ['booking_code' => $bookingCode, 'token' => $token])
                ->with('success', 'Jadwal booking berhasil diubah. Email konfirmasi telah dikirim.');
        } catch (\Exception $e) {
            if (in_array($e->getMessage(), ['SLOT_NOT_FOUND', 'SLOT_TAKEN'])) {
                return back()->withErrors(['reschedule' => 'Slot waktu yang dipilih tidak tersedia. Silakan pilih waktu lain.']);
            }

            Log::error('BookingManage: Reschedule failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->withErrors(['reschedule' => 'Terjadi kesalahan. Silakan coba lagi.']);
        }
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
                    $start       = Carbon::createFromFormat('H:i:s', $row->jam_mulai);
                    $isBooked    = $row->booking_count > 0;
                    $isPast      = Carbon::parse($selectedDate)->isToday() && $start->lessThanOrEqualTo(now());
                    $isAvailable = !$isBooked && !$isPast;

                    return [
                        'id'           => (int) $row->id,
                        'jam_mulai'    => $start->format('H:i'),
                        'jam_selesai'  => Carbon::createFromFormat('H:i:s', $row->jam_selesai)->format('H:i'),
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

        return view('customer.manage.invoice', [
            'booking'    => $booking,
            'token'      => $token,
            'invoiceDate' => $booking->payment?->updated_at ?? $booking->created_at,
        ]);
    }

    // ── Cache helpers ─────────────────────────────────────────────────────────

    private function clearBookingCaches(int $tenantId, int $serviceId, string $date): void
    {
        Cache::forget("tenant:{$tenantId}:service:{$serviceId}:schedules:{$date}");
        $minDate = now()->toDateString();
        $maxDate = now()->addDays(30)->toDateString();
        Cache::forget("tenant:{$tenantId}:service:{$serviceId}:availability:{$minDate}:{$maxDate}");
    }
}

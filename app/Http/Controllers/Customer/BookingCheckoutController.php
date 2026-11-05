<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Booking\CreateBooking;
use App\Actions\Booking\ResumeCheckout;
use App\Actions\Booking\StorefrontCheckoutState;
use App\Actions\CustomerAccess\CustomerAccessLinks;
use App\Domain\Booking\BookingTimeRange;
use App\Http\Requests\Booking\CreateBookingRequest;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Support\CustomerBookingRoutes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class BookingCheckoutController extends StorefrontBookingController
{
    public function showCheckout(string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        if (! $tenant) {
            abort(404);
        }

        $booking = $this->drafts->read();
        $serviceId = $booking->serviceId ?? null;
        $selectedDate = $booking->date ?? null;
        $rawJam = $booking->times ?? null;
        $rawScheduleIds = $booking->scheduleIds ?? (! empty($booking->scheduleId) ? [$booking->scheduleId] : []);

        // Normalise to arrays
        $selectedTimes = is_array($rawJam) ? $rawJam : ($rawJam ? [$rawJam] : []);
        $scheduleIds = is_array($rawScheduleIds) ? $rawScheduleIds : ($rawScheduleIds ? [$rawScheduleIds] : []);

        if (! $serviceId || ! $selectedDate || empty($selectedTimes)) {
            return $this->restartBooking($slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);
        if (! $service || ! $service->hasActiveFulfillment()) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        // Resolve schedules for each selected time
        $schedules = [];
        $hargaPerSlot = [];
        foreach ($selectedTimes as $i => $time) {
            $sid = $scheduleIds[$i] ?? null;
            $schedule = null;
            if ($sid) {
                $schedule = Schedule::where('idtenant', $tenant->id)
                    ->where('idlayanan', $service->id)
                    ->whereDate('tanggal', $selectedDate)
                    ->find($sid);
            }
            if (! $schedule) {
                $schedule = Schedule::where('idtenant', $tenant->id)
                    ->where('idlayanan', $service->id)
                    ->whereDate('tanggal', $selectedDate)
                    ->where(function ($q) use ($time) {
                        $q->where('jam_mulai', $time)
                            ->orWhere('jam_mulai', $time.':00');
                    })
                    ->first();
            }

            if (! $schedule) {
                return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                    ->withErrors(['jam' => 'Pilihan jadwal sudah tidak tersedia.']);
            }

            $schedules[] = $schedule;
            $hargaPerSlot[] = $schedule->harga_override !== null ? (float) $schedule->harga_override : (float) $service->harga;
        }

        $hargaAkhir = array_sum($hargaPerSlot);
        $hasPriceOverride = collect($hargaPerSlot)->contains(fn ($price) => (float) $price !== (float) $service->harga);
        $selectedRanges = array_map(fn (Schedule $slot) => BookingTimeRange::forSlot($selectedDate, (string) $slot->jam_mulai, (string) $slot->jam_selesai), $schedules);
        $selectedMinutes = array_sum(array_map(fn (BookingTimeRange $range) => (int) $range->durationMinutes(), $selectedRanges));
        $selectedTime = $selectedTimes[0] ?? null;
        $checkoutState = app(StorefrontCheckoutState::class);
        $checkoutFingerprint = $checkoutState->fingerprint($service, $selectedDate, $schedules);
        $pendingCheckout = $checkoutState->pending($tenant);

        return view('customer.booking.checkout', compact(
            'tenant',
            'service',
            'selectedDate',
            'selectedTime',
            'selectedTimes',
            'hargaAkhir',
            'hasPriceOverride',
            'schedules',
            'selectedRanges',
            'selectedMinutes',
            'checkoutFingerprint',
            'pendingCheckout'
        ) + ['bookingPolicy' => $tenant->only([
            'refunds_enabled', 'cancel_before_hours', 'reschedule_before_hours',
            'booking_min_lead_hours', 'booking_max_days_ahead', 'booking_buffer_minutes',
        ])]);
    }

    /**
     * Process checkout for multiple time slots.
     * Creates one booking per slot, with a single payment covering total.
     */
    public function processCheckout(CreateBookingRequest $request, string $slug_usaha, CreateBooking $createBooking)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant) {
            abort(404);
        }

        $resume = app(ResumeCheckout::class)->execute($tenant, $request->input('checkout_fingerprint'));
        if ($resume !== null) {
            return redirect()->away($resume);
        }

        $booking = $this->drafts->read();
        $serviceId = $booking->serviceId ?? null;
        $selectedDate = $booking->date ?? null;
        $rawJam = $booking->times ?? $request->input('jam');
        $rawScheduleIds = $booking->scheduleIds ?? (! empty($booking->scheduleId) ? [$booking->scheduleId] : ($request->input('schedule_ids') ?? ($request->has('schedule_id') ? [$request->input('schedule_id')] : [])));

        if (is_string($rawJam)) {
            $rawJam = [$rawJam];
        }
        if (is_numeric($rawScheduleIds)) {
            $rawScheduleIds = [(int) $rawScheduleIds];
        }

        // Normalise to arrays
        $selectedTimes = is_array($rawJam) ? $rawJam : ($rawJam ? [$rawJam] : []);
        $scheduleIds = is_array($rawScheduleIds) ? $rawScheduleIds : ($rawScheduleIds ? [$rawScheduleIds] : []);

        // Fallback: If scheduleIds is empty but selectedTimes is present, lookup schedule IDs from DB
        if (empty($scheduleIds) && ! empty($selectedTimes) && $selectedDate && $serviceId) {
            foreach ($selectedTimes as $time) {
                $foundSchedule = DB::table('schedules')
                    ->where('idtenant', $tenant->id)
                    ->where('idlayanan', (int) $serviceId)
                    ->whereDate('tanggal', $selectedDate)
                    ->where(function ($q) use ($time) {
                        $q->where('jam_mulai', $time)
                            ->orWhere('jam_mulai', $time.':00');
                    })
                    ->first();
                if ($foundSchedule) {
                    $scheduleIds[] = $foundSchedule->id;
                }
            }
        }

        if (! $serviceId || ! $selectedDate || empty($selectedTimes)) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (! $service || ! $service->hasActiveFulfillment()) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        if (empty($scheduleIds) || count($selectedTimes) !== count($scheduleIds)) {
            return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                ->withErrors(['jam' => 'Pilihan jadwal tidak valid.']);
        }

        if (count($scheduleIds) !== count(array_unique($scheduleIds))) {
            return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                ->withErrors(['jam' => 'Jadwal yang dipilih tidak boleh duplikat.']);
        }

        // The page carries a fingerprint of what it showed; another tab or a price change makes it stale.
        $checkoutState = app(StorefrontCheckoutState::class);
        $fingerprint = $request->input('checkout_fingerprint');
        if (is_string($fingerprint) && $fingerprint !== ''
            && ! hash_equals((string) $checkoutState->fingerprintFor($tenant, $service, $selectedDate, $scheduleIds), $fingerprint)) {
            return CustomerBookingRoutes::route('customer.booking.checkout', $slug_usaha)
                ->withInput($request->except('checkout_fingerprint'))
                ->with('error', 'Pilihan jadwal atau harga berubah, misalnya karena diubah di tab lain. Periksa kembali ringkasan pesanan sebelum melanjutkan.');
        }

        $customerData = [
            'namapelanggan' => $request->input('namapelanggan'),
            'nomorhp' => $request->input('nomorhp'),
            'email' => $request->input('email'),
            'catatan' => $request->input('catatan'),
        ];

        $result = $createBooking->execute(
            $tenant,
            $service,
            $selectedDate,
            $selectedTimes,
            $scheduleIds,
            $customerData
        );

        // Handle slot conflict or quota error
        if (isset($result['error'])) {
            if (! empty($result['snap_error'])) {
                return back()->with('error', $result['error']);
            }
            if (isset($result['quota_error'])) {
                return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha)
                    ->withErrors(['tanggal' => $result['error']]);
            }

            return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                ->withErrors(['jam' => $result['error']]);
        }

        $this->drafts->clear();
        if (empty($result['free']) && ($result['payment'] ?? null) instanceof Payment) {
            $checkoutState->remember($result['payment'], is_string($fingerprint) ? $fingerprint : null);
        }

        return redirect()->away(app(CustomerAccessLinks::class)->entry($result['access']));
    }

    public function validateVoucher(Request $request, string $slug_usaha)
    {
        return response()->json([
            'valid' => false,
            'message' => 'Fitur voucher saat ini belum aktif.',
        ], 422);
    }
}

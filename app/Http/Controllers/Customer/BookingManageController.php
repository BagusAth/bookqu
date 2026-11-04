<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Booking\CancelBooking;
use App\Actions\Booking\GetInvoiceAmounts;
use App\Actions\Booking\RescheduleBooking;
use App\Actions\CustomerAccess\ResolveManagementTarget;
use App\Actions\CustomerAccess\ReviewBooking;
use App\Actions\Schedule\GetAvailableSchedules;
use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessOperation;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BookingManageController extends Controller
{
    public function __construct(private ResolveManagementTarget $targets) {}

    private function access(Request $request): AccessContext
    {
        $context = $request->attributes->get('customer_access');
        abort_unless($context instanceof AccessContext, 404);

        return $context;
    }

    /**
     * Show payment-level group management page.
     */
    public function showPaymentGroup(Request $request, string $orderId)
    {
        $payment = $this->targets->payment($this->access($request), $orderId);
        $bookings = $payment->bookings;
        $tenant = $payment->tenant;

        // Log view event for the payment group (throttled)
        $viewKey = "manage:payment_viewed:{$payment->id}:".session()->getId();
        if (! Cache::has($viewKey)) {
            foreach ($bookings as $bk) {
                BookingLog::record((int) $bk->getKey(), 'viewed', 'Halaman manajemen reservasi dibuka oleh customer (Payment Group).');
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
        $payment = $this->targets->payment($this->access($request), $orderId);

        if ($payment->status !== 'sukses') {
            abort(404, 'Invoice hanya tersedia untuk pembayaran yang berhasil.');
        }

        $bookings = $payment->bookings;
        $tenant = $payment->tenant;
        $booking = $bookings->first();

        return view('customer.manage.payment-invoice', app(GetInvoiceAmounts::class)->execute($payment) + [
            'payment' => $payment,
            'bookings' => $bookings,
            'booking' => $booking,
            'tenant' => $tenant,
        ]);
    }

    // ── Shared token validation (Scoped) ───────────────────────────────────────

    public function show(Request $request, string $bookingCode)
    {
        $booking = $this->targets->booking($this->access($request), $bookingCode, AccessOperation::Manage);

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
        $booking = $this->targets->booking($this->access($request), $bookingCode, AccessOperation::CancelBooking);

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
        $booking = $this->targets->booking($this->access($request), $bookingCode, AccessOperation::Reschedule);

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

        $calendar = app(GetAvailableSchedules::class)->getCustomerRescheduleCalendar($booking);

        return view('customer.manage.reschedule', ['booking' => $booking] + $calendar);
    }

    // ── Process reschedule ─────────────────────────────────────────────────────

    public function reschedule(Request $request, string $bookingCode, RescheduleBooking $rescheduleBooking)
    {
        $booking = $this->targets->booking($this->access($request), $bookingCode, AccessOperation::Reschedule);

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
        $booking = $this->targets->booking($this->access($request), $bookingCode, AccessOperation::Reschedule);

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
        $booking = $this->targets->booking($this->access($request), $bookingCode, AccessOperation::Invoice);

        if (! ($booking->payment instanceof Payment) || $booking->payment->status !== 'sukses' || ! in_array($booking->status, ['paid', 'completed'])) {
            abort(404, 'Invoice hanya tersedia untuk pembayaran yang berhasil.');
        }

        return view('customer.manage.invoice', app(GetInvoiceAmounts::class)->execute($booking->payment) + [
            'booking' => $booking,
            'invoiceDate' => $booking->payment?->updated_at ?? $booking->created_at,
        ]);
    }

    // ── Customer Review ───────────────────────────────────────────────────────

    public function storeReview(Request $request, string $bookingCode)
    {
        $booking = $this->targets->booking($this->access($request), $bookingCode, AccessOperation::Review);

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
}

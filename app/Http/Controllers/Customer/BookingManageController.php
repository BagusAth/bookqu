<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Booking\GetBookingTimeRanges;
use App\Actions\Booking\RescheduleBooking;
use App\Actions\Customer\GetCustomerInvoice;
use App\Actions\Customer\GetCustomerManagementPage;
use App\Actions\Customer\GetCustomerPanel;
use App\Actions\CustomerAccess\ResolveManagementTarget;
use App\Actions\CustomerAccess\ReviewBooking;
use App\Actions\Payment\RequestBookingRefund;
use App\Actions\Schedule\GetAvailableSchedules;
use App\Domain\Booking\BookingRules;
use App\Domain\CustomerAccess\AccessContext;
use App\Domain\CustomerAccess\AccessOperation;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
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
    public function showPaymentGroup(Request $request, string $orderId, GetCustomerManagementPage $page)
    {
        $payment = $this->targets->payment($this->access($request), $orderId);
        $bookings = $payment->bookings;
        $tenant = $payment->tenant;
        abort_unless($tenant instanceof Tenant, 404);

        // Log view event for the payment group (throttled)
        $viewKey = "manage:payment_viewed:{$payment->id}:".session()->getId();
        if (! Cache::has($viewKey)) {
            foreach ($bookings as $bk) {
                BookingLog::record((int) $bk->getKey(), 'viewed', 'Halaman manajemen reservasi dibuka oleh customer (Payment Group).');
            }
            Cache::put($viewKey, true, now()->addMinutes(30));
        }

        return view('customer.manage.details', $page->execute($this->access($request), $tenant, $bookings, $payment, group: true));
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
        abort_unless($tenant instanceof Tenant, 404);

        return view('customer.booking.invoice', app(GetCustomerInvoice::class)->execute($tenant, $payment, $bookings,
            route('booking.manage.payment', ['order_id' => $orderId])));
    }

    // ── Shared token validation (Scoped) ───────────────────────────────────────

    public function show(Request $request, string $bookingCode, GetCustomerManagementPage $page)
    {
        $booking = $this->targets->booking($this->access($request), $bookingCode, AccessOperation::Manage);

        // Log the view event (throttled — only once per session per booking)
        $viewKey = "manage:viewed:{$booking->id}:".session()->getId();
        if (! Cache::has($viewKey)) {
            BookingLog::record($booking->id, 'viewed', 'Halaman manajemen booking dibuka oleh customer.');
            Cache::put($viewKey, true, now()->addMinutes(30));
        }

        $tenant = $booking->tenant;
        abort_unless($tenant instanceof Tenant, 404);
        $payment = $booking->payment;

        return view('customer.manage.details', $page->execute($this->access($request), $tenant, [$booking], $payment instanceof Payment ? $payment : null, group: false));
    }

    // ── Cancel booking ─────────────────────────────────────────────────────────

    public function cancel(Request $request, string $bookingCode, RequestBookingRefund $refund): RedirectResponse
    {
        $access = $this->access($request);
        $booking = $this->targets->booking($access, $bookingCode, AccessOperation::CancelBooking);
        if (! BookingRules::canRequestRefund($booking)) {
            return back()->withErrors(['cancel' => 'Permintaan refund tidak tersedia. Hubungi pemilik.']);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'bank_nama' => ['required', 'string', 'max:100'],
            'nomor_rekening' => ['required', 'string', 'regex:/^[0-9]{5,30}$/'],
            'nama_pemilik_rekening' => ['required', 'string', 'max:100'],
        ]);
        $refund->execute($booking, $access, $data);

        return redirect()->route('booking.manage', ['booking_code' => $bookingCode])
            ->with('success', 'Permintaan refund dikirim. Booking tetap berlaku sampai ada keputusan.');
    }

    // ── Show reschedule form ───────────────────────────────────────────────────

    public function showReschedule(Request $request, string $bookingCode)
    {
        $booking = $this->targets->booking($this->access($request), $bookingCode, AccessOperation::Reschedule);

        if ($booking->isMultiSlot()) {
            return redirect()
                ->route('booking.manage', ['booking_code' => $bookingCode])
                ->withErrors(['reschedule' => 'Perubahan jadwal per sesi tidak tersedia untuk reservasi beberapa sesi. Hubungi bisnis jika Anda perlu bantuan.']);
        }

        if ($booking->status !== 'paid') {
            return redirect()
                ->route('booking.manage', ['booking_code' => $bookingCode])
                ->withErrors(['reschedule' => 'Hanya reservasi yang sudah terkonfirmasi yang dapat dijadwalkan ulang.']);
        }

        if (! $booking->canBeRescheduled()) {
            $rescheduleHours = $booking->tenant->reschedule_before_hours ?? 24;

            return redirect()
                ->route('booking.manage', ['booking_code' => $bookingCode])
                ->withErrors(['reschedule' => "Ubah jadwal paling lambat {$rescheduleHours} jam sebelum sesi."]);
        }

        $tenant = $booking->tenant;
        abort_unless($tenant instanceof Tenant, 404);
        $calendar = app(GetAvailableSchedules::class)->getCustomerRescheduleCalendar($booking);

        return view('customer.manage.reschedule', app(GetBookingTimeRanges::class)->execute([$booking]) + [
            'booking' => $booking,
            'tenant' => $tenant,
            'merchantWaUrl' => GetCustomerPanel::whatsAppUrl((string) $tenant->nomorhp),
        ] + $calendar);
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
            return back()->withErrors(['reschedule' => $result['error']])->withInput($validated);
        }

        return redirect()
            ->route('booking.manage', ['booking_code' => $bookingCode])
            ->with('success', 'Jadwal reservasi sudah diubah.');
    }

    // ── Get available time slots for reschedule (AJAX) ─────────────────────────

    public function getTimeSlots(Request $request, string $bookingCode)
    {
        $booking = $this->targets->booking($this->access($request), $bookingCode, AccessOperation::Reschedule);

        $tanggal = $request->query('tanggal');
        if (! $tanggal) {
            return response()->json(['error' => 'Tanggal diperlukan.'], 422);
        }

        $selectedDate = $request->validate(
            ['tanggal' => ['required', 'date_format:Y-m-d']],
            ['tanggal.date_format' => 'Tanggal tidak valid.'],
        )['tanggal'];
        $tenant = $booking->tenant;
        $service = $booking->layanan;

        abort_unless($tenant instanceof Tenant && $service instanceof Service, 404);

        return response()->json(app(GetAvailableSchedules::class)->getCustomerRescheduleSlots($booking, $tenant, $service, $selectedDate));
    }

    // ── Invoice ────────────────────────────────────────────────────────────────

    public function invoice(Request $request, string $bookingCode)
    {
        $booking = $this->targets->booking($this->access($request), $bookingCode, AccessOperation::Invoice);

        if (! ($booking->payment instanceof Payment) || $booking->payment->status !== 'sukses' || ! in_array($booking->status, ['paid', 'completed'])) {
            abort(404, 'Invoice hanya tersedia untuk pembayaran yang berhasil.');
        }

        $tenant = $booking->tenant;
        abort_unless($tenant instanceof Tenant, 404);

        return view('customer.booking.invoice', app(GetCustomerInvoice::class)->execute($tenant, $booking->payment, [$booking],
            route('booking.manage', ['booking_code' => $bookingCode])));
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

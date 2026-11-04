<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Booking\CreateBooking;
use App\Actions\Booking\GetBookingTimeRanges;
use App\Actions\Booking\GetScheduleSelection;
use App\Actions\Booking\ResumeCheckout;
use App\Actions\Booking\StorefrontCheckoutState;
use App\Actions\Booking\ValidateBookingDate;
use App\Actions\CustomerAccess\CancelCheckoutPayment;
use App\Actions\CustomerAccess\CustomerAccessLinks;
use App\Actions\Payment\OutcomeIntakeResult;
use App\Actions\Payment\PaymentOutcomeIntake;
use App\Actions\Tenant\GetStorefrontTenant;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingTimeRange;
use App\Domain\Subscription\EntitlementRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CreateBookingRequest;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\CustomerBookingRoutes;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingController extends Controller
{
    use ClearsBookingCache;

    public function showProgramSelection(?string $slug_usaha = null)
    {
        $tenant = $this->resolveTenant((string) ($slug_usaha ?? request()->route('slug_usaha')));

        if (! $tenant) {
            abort(404);
        }

        $servicesData = Cache::remember($this->getActiveServicesCacheKey($tenant->id), now()->addSeconds(3600), function () use ($tenant) {
            return Service::query()
                ->where('idtenant', $tenant->id)
                ->where('is_active', true)
                ->orderByDesc('is_popular')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Service $service) => $service->getAttributes())
                ->all();
        });

        $services = Service::hydrate($servicesData ?? []);
        $services->load('category');
        $services = $services->filter(fn (Service $service) => $service->hasActiveFulfillment());

        $servicesPayload = $services->map(function (Service $service) {
            $priceLabel = 'Rp '.number_format($service->harga, 0, ',', '.');

            return [
                'id' => $service->id,
                'category_id' => $service->idcategory ? (int) $service->idcategory : null,
                'category_name' => $service->category?->name ?? null,
                'name' => $service->namalayanan,
                'price' => (float) $service->harga,
                'price_label' => $priceLabel,
                'price_unit' => $service->satuan_harga ?: 'sesi',
                'duration' => (int) ($service->durasi ?? 60),
                'duration_unit' => $service->satuan_durasi ?: 'menit',
            ];
        })->values();

        $categories = $services->pluck('category')->filter(fn ($cat) => $cat && $cat->is_active)->unique('id')->values();
        $pendingCheckout = app(StorefrontCheckoutState::class)->pending($tenant);

        $subscription = Subscription::with('plan')->where('idtenant', $tenant->id)->latest('id')->first();
        $acceptsOnlineBookings = EntitlementRules::hasEffectivePlan($subscription);

        return view('customer.booking.program-selection', compact('tenant', 'services', 'servicesPayload', 'categories', 'pendingCheckout', 'acceptsOnlineBookings'));
    }

    public function selectProgram(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        if (! $tenant) {
            abort(404);
        }

        $validated = $request->validate([
            'service_id' => ['required', 'integer'],
        ]);

        $service = $this->resolveService($tenant->id, (int) $validated['service_id']);

        if (! $service || ! $service->hasActiveFulfillment()) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha)
                ->withErrors(['service' => 'Layanan ini sedang tidak tersedia karena staf atau sumber daya tidak aktif.']);
        }

        session([
            'booking' => [
                'tenant_id' => $tenant->id,
                'service_id' => $service->id,
                'tanggal' => null,
                'jam' => null,
            ],
        ]);

        return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha);
    }

    public function showScheduleSelection(Request $request, string $slug_usaha, GetScheduleSelection $selection): View|RedirectResponse
    {
        $tenant = $this->resolveTenant($slug_usaha);
        abort_unless($tenant !== null, 404);
        $booking = session('booking', []);
        if (isset($booking['tenant_id']) && (int) $booking['tenant_id'] !== $tenant->id) {
            session()->forget('booking');

            return $this->restartBooking($slug_usaha);
        }
        $service = $this->resolveService($tenant->id, (int) ($booking['service_id'] ?? 0));
        if (! $service || ! $service->hasActiveFulfillment()) {
            return $this->restartBooking($slug_usaha);
        }
        $date = $request->query('tanggal', $booking['tanggal'] ?? null);
        $data = $selection->execute($tenant, $service, is_string($date) ? $date : null);
        $sameDate = ($booking['tanggal'] ?? null) === $data['selectedDate'];
        $times = $sameDate ? ($booking['jam'] ?? []) : [];
        $selectedTimes = is_array($times) ? $times : [$times];
        $selectedScheduleIds = $sameDate ? ($booking['schedule_ids'] ?? []) : [];

        return view('customer.booking.schedule-selection', array_merge($data, compact('tenant', 'service', 'selectedTimes', 'selectedScheduleIds')));
    }

    public function getScheduleSlots(Request $request, string $slug_usaha, GetScheduleSelection $selection): JsonResponse
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant) {
            return response()->json(['error' => 'Bisnis tidak ditemukan.'], 404);
        }
        $booking = session('booking', []);
        if (isset($booking['tenant_id']) && (int) $booking['tenant_id'] !== $tenant->id) {
            return response()->json(['error' => 'Pilihan layanan tidak tersedia. Silakan pilih layanan kembali.'], 400);
        }
        $service = $this->resolveService($tenant->id, (int) ($booking['service_id'] ?? 0));
        if (! $service || ! $service->hasActiveFulfillment()) {
            return response()->json(['error' => 'Silakan pilih layanan yang tersedia terlebih dahulu.'], 400);
        }
        $validator = Validator::make($request->only('tanggal'), ['tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Jakarta')->toDateString(), 'before_or_equal:'.now('Asia/Jakarta')->addDays(30)->toDateString()]]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Periksa kembali tanggal yang dipilih.', 'errors' => $validator->errors()], 422);
        }
        $validated = $validator->validated();
        $data = $selection->execute($tenant, $service, $validated['tanggal']);

        return response()->json([
            'success' => true, 'date' => $data['selectedDate'], 'is_blocked' => $data['isBlocked'],
            'morning' => $data['morningSlots'], 'afternoon' => $data['afternoonSlots'], 'evening' => $data['eveningSlots'],
            'date_formatted' => $data['selectedDateFormatted'], 'date_label' => $data['selectedDateLabel'],
        ]);
    }

    public function selectSchedule(Request $request, string $slug_usaha): RedirectResponse
    {
        $tenant = $this->resolveTenant($slug_usaha);

        $simulate = $request->boolean('simulate') && app()->environment('local');

        if (! $tenant) {
            abort(404);
        }

        $booking = session('booking', []);
        $sessionTenantId = $booking['tenant_id'] ?? null;

        if ($sessionTenantId && (int) $sessionTenantId !== $tenant->id) {
            session()->forget('booking');

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking['service_id'] ?? null;

        if (! $serviceId) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (! $service || ! $service->hasActiveFulfillment()) {
            session()->forget('booking');

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        try {
            $selectedDate = app(ValidateBookingDate::class)->execute($tenant, $service, $request->input('tanggal'), $simulate);
            if (($booking['tanggal'] ?? null) !== $selectedDate) {
                session()->forget(['booking.jam', 'booking.schedule_id', 'booking.schedule_ids']);
            }
            session()->put('booking.tenant_id', $tenant->id);
            session()->put('booking.tanggal', $selectedDate);

            return $this->storeSelectedTime($request, $slug_usaha, $tenant, $service, session('booking.tanggal'), $simulate, 'customer.booking.schedule');
        } catch (ValidationException $e) {
            return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha)->withErrors($e->errors());
        }
    }

    public function showDateSelection(string $slug_usaha)
    {
        session()->reflash();

        return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha);
    }

    public function selectDate(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        $simulate = $request->boolean('simulate') && app()->environment('local');

        if (! $tenant) {
            abort(404);
        }

        $booking = session('booking', []);
        $sessionTenantId = $booking['tenant_id'] ?? null;

        if ($sessionTenantId && (int) $sessionTenantId !== $tenant->id) {
            session()->forget('booking');

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking['service_id'] ?? null;

        if (! $serviceId) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (! $service || ! $service->hasActiveFulfillment()) {
            session()->forget('booking');

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        try {
            $selectedDate = app(ValidateBookingDate::class)->execute($tenant, $service, $request->input('tanggal'), $simulate);
        } catch (ValidationException $e) {
            return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha)->withErrors($e->errors());
        }
        session()->put('booking.tenant_id', $tenant->id);
        session()->put('booking.tanggal', $selectedDate);
        session()->put('booking.jam', null);
        session()->forget(['booking.schedule_id', 'booking.schedule_ids']);

        return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha);
    }

    public function showTimeSelection(string $slug_usaha)
    {
        session()->reflash();

        return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha);
    }

    public function selectTime(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        $simulate = $request->boolean('simulate') && app()->environment('local');

        if (! $tenant) {
            abort(404);
        }

        $booking = session('booking', []);
        $sessionTenantId = $booking['tenant_id'] ?? null;

        if ($sessionTenantId && (int) $sessionTenantId !== $tenant->id) {
            session()->forget('booking');

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking['service_id'] ?? null;
        $selectedDate = $booking['tanggal'] ?? null;

        if (! $serviceId) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        if (! $selectedDate) {
            return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (! $service || ! $service->hasActiveFulfillment()) {
            session()->forget('booking');

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        session()->put('booking.tenant_id', $tenant->id);

        return $this->storeSelectedTime($request, $slug_usaha, $tenant, $service, $selectedDate, $simulate, 'customer.booking.time');
    }

    public function showCheckout(string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        if (! $tenant) {
            abort(404);
        }

        $booking = session('booking', []);
        $serviceId = $booking['service_id'] ?? null;
        $selectedDate = $booking['tanggal'] ?? null;
        $rawJam = $booking['jam'] ?? null;
        $rawScheduleIds = $booking['schedule_ids'] ?? (! empty($booking['schedule_id']) ? [$booking['schedule_id']] : []);

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
        // BQ-114: show each chosen slot's own range, not the service's current duration
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
        ));
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

        $booking = session('booking', []);
        $serviceId = $booking['service_id'] ?? null;
        $selectedDate = $booking['tanggal'] ?? null;
        $rawJam = $booking['jam'] ?? $request->input('jam');
        $rawScheduleIds = $booking['schedule_ids'] ?? (! empty($booking['schedule_id']) ? [$booking['schedule_id']] : ($request->input('schedule_ids') ?? ($request->has('schedule_id') ? [$request->input('schedule_id')] : [])));

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

        if (empty($scheduleIds) || empty($selectedTimes) || count($selectedTimes) !== count($scheduleIds)) {
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

        session()->forget('booking');
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

    public function showPayment(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        if ($payment->status === 'sukses') {
            return CustomerBookingRoutes::route('customer.booking.invoice', [$slug_usaha, $payment]);
        }

        $payment->load(['bookings.layanan']);
        $timeRanges = app(GetBookingTimeRanges::class)->execute($payment->bookings);

        $provider = $payment->provider ?: ($payment->metode === 'singapay' ? 'singapay' : config('payment.default', 'singapay'));
        $isMidtrans = $provider === 'midtrans';
        $clientKey = $isMidtrans ? config('payment.drivers.midtrans.client_key') : null;
        $snapUrl = $isMidtrans ? config('payment.drivers.midtrans.snap_url') : null;

        if ($payment->status === 'gagal') {
            return view('customer.booking.payment', $timeRanges + [
                'tenant' => $tenant,
                'payment' => $payment,
                'provider' => $provider,
                'paymentState' => 'failed',
                'paymentUrl' => null,
                'snapToken' => null,
                'clientKey' => $clientKey,
                'snapUrl' => $snapUrl,
            ]);
        }

        if ($payment->isExpired()) {
            return view('customer.booking.payment', $timeRanges + [
                'tenant' => $tenant,
                'payment' => $payment,
                'provider' => $provider,
                'paymentState' => 'expired',
                'paymentUrl' => null,
                'snapToken' => null,
                'clientKey' => $clientKey,
                'snapUrl' => $snapUrl,
            ]);
        }

        return view('customer.booking.payment', $timeRanges + [
            'tenant' => $tenant,
            'payment' => $payment,
            'provider' => $provider,
            'paymentState' => 'pending',
            'paymentUrl' => $payment->payment_url,
            'snapToken' => $payment->snap_token,
            'clientKey' => $clientKey,
            'snapUrl' => $snapUrl,
        ]);
    }

    public function cancelPayment(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        try {
            app(CancelCheckoutPayment::class)->execute(request()->attributes->get('customer_access'), $payment);
        } catch (HttpException $refused) {
            // Paid or expired meanwhile: a form submit returns to the payment page, which shows the real state.
            if ($refused->getStatusCode() !== 422 || request()->expectsJson()) {
                throw $refused;
            }

            return CustomerBookingRoutes::route('customer.booking.payment', [$slug_usaha, $payment->order_id])
                ->with('error', 'Pesanan tidak dapat dibatalkan karena pembayarannya sudah diterima atau sudah berakhir.');
        }

        session()->forget('booking');

        return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha)
            ->with('info', 'Transaksi berhasil dibatalkan. Anda dapat memilih layanan atau jadwal baru.');
    }

    public function checkPaymentStatus(string $slug_usaha, Payment $payment, PaymentOutcomeIntake $intake)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        return $this->paymentStatusResponse($intake->check($payment, request()->attributes->get('customer_access')), $slug_usaha, $payment);
    }

    public function handleCallback(string $slug_usaha, Payment $payment, PaymentOutcomeIntake $intake)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        return $this->paymentStatusResponse($intake->check($payment, request()->attributes->get('customer_access')), $slug_usaha, $payment);
    }

    private function paymentStatusResponse(OutcomeIntakeResult $result, string $slug_usaha, Payment $payment)
    {
        $current = $result->payment ?? $payment;

        return match ($result->status()) {
            'sukses' => response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => CustomerBookingRoutes::url('customer.booking.invoice', [$slug_usaha, $payment]),
            ]),
            'gagal' => response()->json(array_filter([
                'status' => 'gagal',
                'is_expired' => $current->isExpired() ?: null,
                'message' => $current->isExpired()
                    ? 'Waktu pembayaran telah habis. Silakan buat reservasi baru.'
                    : 'Pembayaran gagal atau dibatalkan.',
            ], fn ($value) => $value !== null)),
            default => response()->json([
                'status' => 'pending',
                'message' => 'Menunggu pembayaran diselesaikan...',
            ]),
        };
    }

    public function showInvoice(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        // Section 10: Invoice hanya boleh diakses jika payment sukses
        if ($payment->status === 'pending') {
            return CustomerBookingRoutes::route('customer.booking.payment', [$slug_usaha, $payment->order_id])
                ->with('error', 'Silakan selesaikan pembayaran terlebih dahulu untuk melihat invoice.');
        }

        if ($payment->status !== 'sukses') {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha)
                ->with('error', 'Invoice hanya dapat diakses untuk pembayaran yang telah berhasil.');
        }

        $bookings = Booking::with('layanan')
            ->where('idpayment', $payment->id)
            ->orderBy('tanggalbooking')
            ->orderBy('jam')
            ->get();

        if ($bookings->isEmpty()) {
            abort(404, 'Data booking tidak ditemukan.');
        }

        $booking = $bookings->first();

        return view('customer.booking.invoice', app(GetBookingTimeRanges::class)->execute($bookings) + compact('tenant', 'payment', 'bookings', 'booking'));
    }

    /**
     * Back to the first step, keeping a message flashed by the request that sent the customer here.
     */
    private function storeSelectedTime(Request $request, string $slug_usaha, Tenant $tenant, Service $service, string $selectedDate, bool $simulate, string $errorRoute): RedirectResponse
    {
        $wasOriginalJamArray = is_array($request->input('jam')) || ($request->has('schedule_ids') && is_array($request->input('schedule_ids')));
        $rawJam = $request->input('jam');
        $rawScheduleIds = $request->input('schedule_ids') ?? ($request->has('schedule_id') ? [$request->input('schedule_id')] : null);

        if (is_string($rawJam)) {
            $rawJam = [$rawJam];
        }
        if (is_numeric($rawScheduleIds)) {
            $rawScheduleIds = [(int) $rawScheduleIds];
        }

        if ($rawJam !== null || $rawScheduleIds !== null) {
            $request->merge([
                'jam' => $rawJam,
                'schedule_ids' => $rawScheduleIds,
            ]);
        }

        // Validate arrays for multi-select
        $rules = [
            'jam' => ['required', 'array', 'min:1'],
            'jam.*' => ['required', 'date_format:H:i', 'distinct'],
        ];

        if ($simulate) {
            $rules['schedule_ids'] = ['nullable', 'array'];
            $rules['schedule_ids.*'] = ['nullable', 'integer'];
        } else {
            $rules['schedule_ids'] = ['required', 'array', 'min:1'];
            $rules['schedule_ids.*'] = ['required', 'integer', 'distinct'];
        }

        $validated = $request->validate($rules);

        $jamArray = $validated['jam'];
        $scheduleIdArray = $validated['schedule_ids'] ?? [];

        if ($simulate) {
            session()->put('booking.jam', count($jamArray) === 1 ? $jamArray[0] : $jamArray);
            session()->put('booking.schedule_id', $scheduleIdArray[0] ?? null);
            session()->put('booking.schedule_ids', $scheduleIdArray);

            return CustomerBookingRoutes::route('customer.booking.checkout', [
                'slug_usaha' => $slug_usaha,
                'simulate' => 1,
            ]);
        }

        if (count($jamArray) !== count($scheduleIdArray)) {
            return CustomerBookingRoutes::route($errorRoute, $slug_usaha)
                ->withErrors(['jam' => 'Jumlah jam dan jadwal tidak sesuai.']);
        }

        if (count($scheduleIdArray) !== count(array_unique($scheduleIdArray))) {
            return CustomerBookingRoutes::route($errorRoute, $slug_usaha)
                ->withErrors(['jam' => 'Jadwal yang dipilih tidak boleh duplikat.']);
        }

        $selectedDate = Carbon::parse($selectedDate)->toDateString();
        $wib = 'Asia/Jakarta';
        $nowWib = Carbon::now($wib);

        $validatedSchedules = [];

        foreach ($jamArray as $index => $jamValue) {
            $scheduleId = (int) $scheduleIdArray[$index];

            $schedule = DB::transaction(function () use ($tenant, $service, $scheduleId, $selectedDate) {
                $row = DB::table('schedules')
                    ->where('id', $scheduleId)
                    ->where('idtenant', $tenant->id)
                    ->where('idlayanan', $service->id)
                    ->whereDate('tanggal', $selectedDate)
                    ->where('status', 'tersedia')
                    ->lockForUpdate()
                    ->first();

                if (! $row) {
                    return null;
                }

                $isBooked = BookingRules::isSlotOccupied((int) $scheduleId);

                return $isBooked ? null : $row;
            });

            if (! $schedule) {
                return CustomerBookingRoutes::route($errorRoute, $slug_usaha)
                    ->withErrors(['jam' => "Jam {$jamValue} sudah tidak tersedia. Silakan pilih jam lain."]);
            }

            $slotDateTime = Carbon::parse($selectedDate.' '.$schedule->jam_mulai, $wib);
            $resolvedTime = $slotDateTime->format('H:i');

            if ($jamValue !== $resolvedTime) {
                return CustomerBookingRoutes::route($errorRoute, $slug_usaha)
                    ->withErrors(['jam' => "Jam {$jamValue} tidak sesuai dengan jadwal."]);
            }

            if ($slotDateTime->lessThanOrEqualTo($nowWib)) {
                return CustomerBookingRoutes::route($errorRoute, $slug_usaha)
                    ->withErrors(['jam' => "Waktu {$jamValue} sudah terlewat (WIB). Silakan pilih jam lain."]);
            }

            $validatedSchedules[] = $schedule;
        }

        // Contiguous sequence check from the slot boundaries (BQ-114)
        usort($validatedSchedules, fn ($a, $b) => strcmp($a->jam_mulai, $b->jam_mulai));

        if (! BookingRules::validateContiguousSlots($validatedSchedules)) {
            return CustomerBookingRoutes::route($errorRoute, $slug_usaha)
                ->withErrors(['jam' => 'Slot harus berurutan tanpa jeda.']);
        }

        $validatedScheduleIds = array_map(fn ($s) => $s->id, $validatedSchedules);
        $validatedTimes = array_map(fn ($s) => Carbon::parse($selectedDate.' '.$s->jam_mulai, $wib)->format('H:i'), $validatedSchedules);

        // Store both scalar (for single slot test assertion) and array in session
        $storedJam = $wasOriginalJamArray ? $validatedTimes : (count($validatedTimes) === 1 ? $validatedTimes[0] : $validatedTimes);
        session()->put('booking.jam', $storedJam);
        session()->put('booking.schedule_id', $validatedScheduleIds[0]);
        session()->put('booking.schedule_ids', $validatedScheduleIds);

        return CustomerBookingRoutes::route('customer.booking.checkout', $slug_usaha);
    }

    private function restartBooking(string $slug_usaha)
    {
        session()->reflash();

        return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
    }

    private function resolveTenant(string $slug_usaha): ?Tenant
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return $tenantId === null ? null : app(GetStorefrontTenant::class)->execute($tenantId);
    }

    private function resolveService(int $tenantId, int $serviceId): ?Service
    {
        $serviceData = Cache::remember($this->getServiceCacheKey($tenantId, $serviceId), now()->addSeconds(3600), function () use ($tenantId, $serviceId) {
            // scope-bypass: filtered by the resolved storefront tenant
            return Service::withoutGlobalScopes()
                ->where('id', $serviceId)
                ->where('idtenant', $tenantId)
                ->where('is_active', true)
                ->first()?->getAttributes();
        });

        if (! $serviceData) {
            return null;
        }

        return Service::hydrate([$serviceData])->first();
    }
}

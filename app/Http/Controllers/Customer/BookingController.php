<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Booking\CreateBooking;
use App\Actions\Booking\StorefrontCheckoutState;
use App\Actions\CustomerAccess\CancelCheckoutPayment;
use App\Actions\CustomerAccess\CustomerAccessLinks;
use App\Actions\Payment\OutcomeIntakeResult;
use App\Actions\Payment\PaymentOutcomeIntake;
use App\Actions\Schedule\GetAvailableSchedules;
use App\Actions\Tenant\GetStorefrontTenant;
use App\Domain\Booking\BookingRules;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        return view('customer.booking.program-selection', compact('tenant', 'services', 'servicesPayload', 'categories', 'pendingCheckout'));
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

        return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha);
    }

    public function showScheduleSelection(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        $simulate = request()->boolean('simulate') && app()->environment('local');

        if (!$tenant) {
            abort(404);
        }

        $booking = session('booking', []);
        $sessionTenantId = $booking['tenant_id'] ?? null;

        if ($sessionTenantId && (int) $sessionTenantId !== $tenant->id) {
            session()->forget('booking');
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking['service_id'] ?? null;

        if (!$serviceId) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (!$service || !$service->hasActiveFulfillment()) {
            session()->forget('booking');
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        session()->put('booking.tenant_id', $tenant->id);

        $wib = 'Asia/Jakarta';
        $minDate = Carbon::today($wib);
        $maxDate = Carbon::today($wib)->addDays(30);

        $availabilityPayload = app(\App\Actions\Schedule\GetAvailableSchedules::class)
            ->getDateAvailability($tenant, $service, $minDate, $maxDate);

        $availabilityByDate = collect($availabilityPayload)->keyBy('date');
        $dates = [];
        $currentDate = $minDate->copy();

        while ($currentDate->lte($maxDate)) {
            $dStr = $currentDate->toDateString();
            $avail = $availabilityByDate->get($dStr);
            $totalSlots = $avail ? (int) $avail['total_slots'] : 0;
            $availSlots = $avail ? (int) $avail['available_slots'] : 0;
            $isBlocked = $avail ? (bool) $avail['is_blocked'] : false;

            $dates[] = [
                'date' => $dStr,
                'day_name' => $currentDate->format('D'), // Mon, Tue, Wed, Thu, Fri, Sat, Sun
                'day_name_id' => $currentDate->translatedFormat('D'),
                'day_number' => $currentDate->format('j'), // 13, 14, 15...
                'month_name' => $currentDate->format('M'),
                'is_today' => $currentDate->isToday(),
                'total_slots' => $totalSlots,
                'available_slots' => $availSlots,
                'is_blocked' => $isBlocked,
                'is_available' => !$isBlocked && $availSlots > 0,
            ];
            $currentDate->addDay();
        }

        // Determine default selected date
        $requestedDate = $request->query('tanggal', $booking['tanggal'] ?? null);
        $selectedDate = null;

        if ($requestedDate) {
            $parsed = Carbon::parse($requestedDate, $wib)->toDateString();
            if ($parsed >= $minDate->toDateString() && $parsed <= $maxDate->toDateString()) {
                $selectedDate = $parsed;
            }
        }

        if (!$selectedDate) {
            foreach ($dates as $d) {
                if ($d['is_available']) {
                    $selectedDate = $d['date'];
                    break;
                }
            }
        }

        if (!$selectedDate) {
            $selectedDate = $minDate->toDateString();
        }

        // Load slots for selected date
        $initialSlots = app(\App\Actions\Schedule\GetAvailableSchedules::class)
            ->getTimeSlots($tenant, $service, $selectedDate);

        $morningSlots = [];
        $afternoonSlots = [];
        $eveningSlots = [];

        foreach ($initialSlots as $slot) {
            $startTime = Carbon::createFromFormat('H:i', substr($slot['time'], 0, 5));
            $period = (int) $startTime->format('H') < 12 ? 'AM' : 'PM';
            $enhancedSlot = array_merge($slot, [
                'period' => $period,
                'time_12' => $startTime->format('h:i'),
            ]);

            if ($slot['session'] === 'morning') {
                $morningSlots[] = $enhancedSlot;
            } elseif ($slot['session'] === 'afternoon') {
                $afternoonSlots[] = $enhancedSlot;
            } else {
                $eveningSlots[] = $enhancedSlot;
            }
        }

        $priceLabel = 'Rp ' . number_format($service->harga, 0, ',', '.');
        $durasiMenit = (int) ($service->durasi ?? 60);
        $durationLabel = $durasiMenit >= 60 && ($durasiMenit % 60 === 0)
            ? ($durasiMenit / 60) . ' Hour'
            : $durasiMenit . ' Mins';

        $servicePayload = [
            'id' => $service->id,
            'name' => $service->namalayanan,
            'price' => (float) $service->harga,
            'price_label' => $priceLabel,
            'price_unit' => $service->satuan_harga ?: 'sesi',
            'duration' => $durasiMenit,
            'duration_unit' => $service->satuan_durasi ?: 'menit',
            'duration_label' => $durationLabel,
            'category_name' => $service->category?->name ?? null,
            'image_url' => $service->thumbnail_path ? \Illuminate\Support\Facades\Storage::url($service->thumbnail_path) : null,
        ];

        $rawJam = $booking['jam'] ?? null;
        $selectedTimes = is_array($rawJam) ? $rawJam : ($rawJam ? [$rawJam] : []);
        $rawScheduleIds = $booking['schedule_ids'] ?? (!empty($booking['schedule_id']) ? [$booking['schedule_id']] : []);
        $selectedScheduleIds = is_array($rawScheduleIds) ? $rawScheduleIds : ($rawScheduleIds ? [$rawScheduleIds] : []);

        // Guard against stale session: If stored date in session differs from selected date, do not use stale slots
        if (!empty($booking['tanggal']) && $booking['tanggal'] !== $selectedDate) {
            $selectedTimes = [];
            $selectedScheduleIds = [];
        }

        $selectedDateCarbon = Carbon::parse($selectedDate, $wib);

        return view('customer.booking.schedule-selection', [
            'tenant' => $tenant,
            'service' => $service,
            'servicePayload' => $servicePayload,
            'availabilityPayload' => $availabilityPayload,
            'dates' => $dates,
            'selectedDate' => $selectedDate,
            'selectedDateFormatted' => $selectedDateCarbon->format('M d, Y'),
            'selectedDateLabel' => $selectedDateCarbon->translatedFormat('l, d F Y'),
            'initialSlots' => $initialSlots,
            'morningSlots' => $morningSlots,
            'afternoonSlots' => $afternoonSlots,
            'eveningSlots' => $eveningSlots,
            'selectedTimes' => $selectedTimes,
            'selectedScheduleIds' => $selectedScheduleIds,
            'minDate' => $minDate->toDateString(),
            'maxDate' => $maxDate->toDateString(),
            'simulate' => $simulate,
        ]);
    }

    /**
     * AJAX endpoint: Fetch available time slots for a specific date.
     */
    public function getScheduleSlots(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $booking = session('booking', []);
        $serviceId = $booking['service_id'] ?? $request->input('service_id');
        if (!$serviceId) {
            return response()->json(['error' => 'Service not selected'], 400);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);
        if (!$service || !$service->hasActiveFulfillment()) {
            return response()->json(['error' => 'Service not available'], 400);
        }

        $dateStr = $request->input('tanggal');
        if (!$dateStr) {
            return response()->json(['error' => 'Tanggal is required'], 422);
        }

        $wib = 'Asia/Jakarta';
        $parsedDate = Carbon::parse($dateStr, $wib);
        $dateFormatted = $parsedDate->toDateString();

        $isBlocked = DB::table('owner_blocked_dates')
            ->where('idtenant', $tenant->id)
            ->whereDate('tanggal', $dateFormatted)
            ->exists();

        if ($isBlocked) {
            return response()->json([
                'success' => true,
                'is_blocked' => true,
                'date' => $dateFormatted,
                'date_label' => $parsedDate->translatedFormat('l, d F Y'),
                'date_header' => $parsedDate->format('M d, Y'),
                'slots' => [],
                'morning' => [],
                'afternoon' => [],
                'evening' => [],
            ]);
        }

        $slots = app(\App\Actions\Schedule\GetAvailableSchedules::class)
            ->getTimeSlots($tenant, $service, $dateFormatted);

        $morning = [];
        $afternoon = [];
        $evening = [];

        foreach ($slots as $slot) {
            $startTime = Carbon::createFromFormat('H:i', substr($slot['time'], 0, 5));
            $period = (int) $startTime->format('H') < 12 ? 'AM' : 'PM';
            $enhancedSlot = array_merge($slot, [
                'period' => $period,
                'time_12' => $startTime->format('h:i'),
            ]);

            if ($slot['session'] === 'morning') {
                $morning[] = $enhancedSlot;
            } elseif ($slot['session'] === 'afternoon') {
                $afternoon[] = $enhancedSlot;
            } else {
                $evening[] = $enhancedSlot;
            }
        }

        return response()->json([
            'success' => true,
            'is_blocked' => false,
            'date' => $dateFormatted,
            'date_label' => $parsedDate->translatedFormat('l, d F Y'),
            'date_header' => $parsedDate->format('M d, Y'),
            'slots' => $slots,
            'morning' => $morning,
            'afternoon' => $afternoon,
            'evening' => $evening,
        ]);
    }

    /**
     * Unified Schedule Submission (POST select-schedule).
     * Validates date and time slots together, then advances to Confirm/Checkout (Step 3).
     */
    public function selectSchedule(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        $simulate = $request->boolean('simulate') && app()->environment('local');

        if (!$tenant) {
            abort(404);
        }

        $booking = session('booking', []);
        $sessionTenantId = $booking['tenant_id'] ?? null;

        if ($sessionTenantId && (int) $sessionTenantId !== $tenant->id) {
            session()->forget('booking');
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking['service_id'] ?? null;
        if (!$serviceId) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);
        if (!$service || !$service->hasActiveFulfillment()) {
            session()->forget('booking');
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        session()->put('booking.tenant_id', $tenant->id);

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
        ]);

        $selectedDate = Carbon::parse($validated['tanggal'], 'Asia/Jakarta')->toDateString();
        $minDate = Carbon::today('Asia/Jakarta');
        $maxDate = Carbon::today('Asia/Jakarta')->addDays(30);

        if ($selectedDate < $minDate->toDateString() || $selectedDate > $maxDate->toDateString()) {
            return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha)
                ->withErrors(['tanggal' => 'Tanggal yang dipilih berada di luar batas pemesanan.']);
        }

        // Quota check
        $subscription = \App\Models\Subscription::with('plan')->where('idtenant', $tenant->id)->latest()->first();
        if (!\App\Domain\Subscription\EntitlementRules::isUnlimitedBooking($subscription)) {
            $dateCarbon = Carbon::parse($selectedDate);
            $totalMonthlyBookings = DB::table('bookings')
                ->where('idtenant', $tenant->id)
                ->whereYear('tanggalbooking', $dateCarbon->year)
                ->whereMonth('tanggalbooking', $dateCarbon->month)
                ->whereIn('status', ['pending', 'paid', 'completed'])
                ->count();

            $bookingQuota = \App\Domain\Subscription\EntitlementRules::canCreateBooking($subscription, $totalMonthlyBookings, 1);
            if (!$bookingQuota['allowed']) {
                return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha)
                    ->withErrors(['tanggal' => 'Kapasitas kuota booking bulanan bisnis ini telah penuh. Silakan hubungi pemilik bisnis.']);
            }
        }

        $isBlocked = DB::table('owner_blocked_dates')
            ->where('idtenant', $tenant->id)
            ->whereDate('tanggal', $selectedDate)
            ->exists();

        if ($isBlocked) {
            return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha)
                ->withErrors(['tanggal' => 'Tanggal ini sedang ditutup oleh pemilik bisnis.']);
        }

        session()->put('booking.tanggal', $selectedDate);

        // Continue with time slots validation
        return $this->selectTime($request, $slug_usaha);
    }

    public function showDateSelection(string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        $simulate = request()->boolean('simulate') && app()->environment('local');

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
            return $this->restartBooking($slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (! $service || ! $service->hasActiveFulfillment()) {
            session()->forget('booking');

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        session()->put('booking.tenant_id', $tenant->id);

        $wib = 'Asia/Jakarta';
        $nowWib = Carbon::now($wib);
        $minDate = Carbon::today($wib);
        $maxDate = Carbon::today($wib)->addDays(30);

        $availabilityPayload = app(GetAvailableSchedules::class)
            ->getDateAvailability($tenant, $service, $minDate, $maxDate);

        $priceLabel = 'Rp '.number_format($service->harga, 0, ',', '.');
        $servicePayload = [
            'id' => $service->id,
            'name' => $service->namalayanan,
            'price' => (float) $service->harga,
            'price_label' => $priceLabel,
            'price_unit' => $service->satuan_harga ?: 'sesi',
            'duration' => (int) $service->durasi,
            'duration_unit' => $service->satuan_durasi ?: 'menit',
        ];

        $selectedDate = $booking['tanggal'] ?? null;

        return view('customer.booking.date-selection', [
            'tenant' => $tenant,
            'service' => $service,
            'servicePayload' => $servicePayload,
            'availabilityPayload' => $availabilityPayload,
            'selectedDate' => $selectedDate,
            'minDate' => $minDate->toDateString(),
            'maxDate' => $maxDate->toDateString(),
            'simulate' => $simulate,
        ]);
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

        session()->put('booking.tenant_id', $tenant->id);

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
        ]);

        $selectedDate = Carbon::parse($validated['tanggal'], 'Asia/Jakarta')->toDateString();
        $minDate = Carbon::today('Asia/Jakarta');
        $maxDate = Carbon::today('Asia/Jakarta')->addDays(30);

        if ($selectedDate < $minDate->toDateString() || $selectedDate > $maxDate->toDateString()) {
            return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha)
                ->withErrors(['tanggal' => 'Tanggal yang dipilih di luar rentang pemesanan (hari ini sampai 30 hari ke depan).']);
        }

        if ($simulate) {
            session()->put('booking.tanggal', $selectedDate);
            session()->put('booking.jam', null);

            return CustomerBookingRoutes::route('customer.booking.time', [
                'slug_usaha' => $slug_usaha,
                'simulate' => 1,
            ]);
        }

        // Subscription monthly booking limit check via EntitlementRules
        $subscription = Subscription::with('plan')->where('idtenant', $tenant->id)->latest()->first();
        if (! EntitlementRules::isUnlimitedBooking($subscription)) {
            $dateCarbon = Carbon::parse($selectedDate);
            $totalMonthlyBookings = DB::table('bookings')
                ->where('idtenant', $tenant->id)
                ->whereYear('tanggalbooking', $dateCarbon->year)
                ->whereMonth('tanggalbooking', $dateCarbon->month)
                ->whereIn('status', ['pending', 'paid', 'completed'])
                ->count();

            $bookingQuota = EntitlementRules::canCreateBooking($subscription, $totalMonthlyBookings, 1);
            if (! $bookingQuota['allowed']) {
                return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha)
                    ->withErrors(['tanggal' => 'Kapasitas kuota booking bulanan bisnis ini telah penuh (maksimal '.$subscription->plan->maxbooking.' booking/bulan). Silakan hubungi pemilik bisnis.']);
            }
        }

        $isBlocked = DB::table('owner_blocked_dates')
            ->where('idtenant', $tenant->id)
            ->whereDate('tanggal', $selectedDate)
            ->exists();

        if ($isBlocked) {
            return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha)
                ->withErrors(['tanggal' => 'Tanggal ini sedang ditutup oleh pemilik bisnis.']);
        }

        $availableSlots = app(GetAvailableSchedules::class)
            ->countAvailableSlotsOnDate($tenant, $service, $selectedDate);

        if ($availableSlots < 1) {
            return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha)
                ->withErrors(['tanggal' => 'Tanggal ini sudah penuh. Silakan pilih tanggal lain.']);
        }

        session()->put('booking.tanggal', $selectedDate);
        session()->put('booking.jam', null);

        return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha);
    }

    public function showTimeSelection(string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        $simulate = request()->boolean('simulate') && app()->environment('local');

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
            return $this->restartBooking($slug_usaha);
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

        $selectedDate = Carbon::parse($selectedDate)->toDateString();
        $isBlocked = DB::table('owner_blocked_dates')
            ->where('idtenant', $tenant->id)
            ->whereDate('tanggal', $selectedDate)
            ->exists();

        if ($isBlocked) {
            return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha)
                ->withErrors(['tanggal' => 'Tanggal ini telah ditutup oleh pemilik bisnis.']);
        }

        $timeSlotsPayload = app(GetAvailableSchedules::class)
            ->getTimeSlots($tenant, $service, $selectedDate);

        $priceLabel = 'Rp '.number_format($service->harga, 0, ',', '.');
        $servicePayload = [
            'id' => $service->id,
            'name' => $service->namalayanan,
            'price' => (float) $service->harga,
            'price_label' => $priceLabel,
            'price_unit' => $service->satuan_harga ?: 'sesi',
            'duration' => (int) $service->durasi,
            'duration_unit' => $service->satuan_durasi ?: 'menit',
        ];

        // Support both old single-string format and new array format
        $rawJam = $booking['jam'] ?? null;
        $selectedTimes = [];
        if (is_array($rawJam)) {
            $selectedTimes = $rawJam;
        } elseif (is_string($rawJam) && $rawJam !== '') {
            $selectedTimes = [$rawJam];
        }
        $selectedDateLabel = Carbon::parse($selectedDate)->locale('id')->translatedFormat('l, j F');

        return view('customer.booking.time-selection', [
            'tenant' => $tenant,
            'service' => $service,
            'servicePayload' => $servicePayload,
            'timeSlotsPayload' => $timeSlotsPayload,
            'selectedDate' => $selectedDate,
            'selectedDateLabel' => $selectedDateLabel,
            'selectedTimes' => $selectedTimes,
            'simulate' => $simulate,
        ]);
    }

    /**
     * Handle multi-select time submission.
     * Accepts jam[] and schedule_ids[] arrays.
     */
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
            return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                ->withErrors(['jam' => 'Jumlah jam dan jadwal tidak sesuai.']);
        }

        if (count($scheduleIdArray) !== count(array_unique($scheduleIdArray))) {
            return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
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
                return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                    ->withErrors(['jam' => "Jam {$jamValue} sudah tidak tersedia. Silakan pilih jam lain."]);
            }

            $slotDateTime = Carbon::parse($selectedDate.' '.$schedule->jam_mulai, $wib);
            $resolvedTime = $slotDateTime->format('H:i');

            if ($jamValue !== $resolvedTime) {
                return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                    ->withErrors(['jam' => "Jam {$jamValue} tidak sesuai dengan jadwal."]);
            }

            if ($slotDateTime->lessThanOrEqualTo($nowWib)) {
                return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                    ->withErrors(['jam' => "Waktu {$jamValue} sudah terlewat (WIB). Silakan pilih jam lain."]);
            }

            $validatedSchedules[] = $schedule;
        }

        // Contiguous sequence check based on service duration
        usort($validatedSchedules, fn ($a, $b) => strcmp($a->jam_mulai, $b->jam_mulai));
        $durationMinutes = (int) $service->durasi;

        for ($i = 1; $i < count($validatedSchedules); $i++) {
            $prevStart = Carbon::parse($selectedDate.' '.$validatedSchedules[$i - 1]->jam_mulai, $wib);
            $currStart = Carbon::parse($selectedDate.' '.$validatedSchedules[$i]->jam_mulai, $wib);
            if ((int) abs($currStart->diffInMinutes($prevStart)) !== $durationMinutes) {
                return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                    ->withErrors(['jam' => 'Slot harus berurutan tanpa jeda.']);
            }
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
            'schedules',
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
            $checkoutState->remember($result['payment']);
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

        $provider = $payment->provider ?: ($payment->metode === 'singapay' ? 'singapay' : config('payment.default', 'singapay'));
        $isMidtrans = $provider === 'midtrans';
        $clientKey = $isMidtrans ? config('payment.drivers.midtrans.client_key') : null;
        $snapUrl = $isMidtrans ? config('payment.drivers.midtrans.snap_url') : null;

        if ($payment->status === 'gagal') {
            return view('customer.booking.payment', [
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
            return view('customer.booking.payment', [
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

        return view('customer.booking.payment', [
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

        return view('customer.booking.invoice', compact('tenant', 'payment', 'bookings', 'booking'));
    }

    /**
     * Back to the first step, keeping a message flashed by the request that sent the customer here.
     */
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

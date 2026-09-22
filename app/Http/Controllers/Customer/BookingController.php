<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;

use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Mail\BookingGroupInvoiceMail;
use App\Services\MidtransPaymentService;
use App\Traits\ClearsBookingCache;
use App\Support\CustomerBookingRoutes;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Transaction as MidtransTransaction;

class BookingController extends Controller
{
    use ClearsBookingCache;
    public function __construct()
    {
        MidtransConfig::$serverKey = config('midtrans.server_key');
        MidtransConfig::$isProduction = config('midtrans.is_production');
        MidtransConfig::$isSanitized = config('midtrans.is_sanitized');
        MidtransConfig::$is3ds = config('midtrans.is_3ds');
        // P0-17: SSL Hardening
        $curlOptions = [CURLOPT_HTTPHEADER => []];
        if (app()->environment('local')) {
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = 0;
        } else {
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
        }
        MidtransConfig::$curlOptions = $curlOptions;
    }
    public function showProgramSelection(string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        if (!$tenant) {
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
            $priceLabel = 'Rp ' . number_format($service->harga, 0, ',', '.');

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

        return view('customer.booking.program-selection', compact('tenant', 'services', 'servicesPayload', 'categories'));
    }

    public function selectProgram(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        if (!$tenant) {
            abort(404);
        }

        $validated = $request->validate([
            'service_id' => ['required', 'integer'],
        ]);

        $service = $this->resolveService($tenant->id, (int) $validated['service_id']);

        if (!$service || !$service->hasActiveFulfillment()) {
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

    public function showDateSelection(string $slug_usaha)
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
        $nowWib = Carbon::now($wib);
        $minDate = Carbon::today($wib);
        $maxDate = Carbon::today($wib)->addDays(30);

        $todayStr = $minDate->toDateString();
        $nowTimeStr = $nowWib->format('H:i');

        $availabilityKey = $this->getAvailabilityCacheKey($tenant->id, $service->id);

        $availabilityPayload = Cache::remember($availabilityKey, now()->addSeconds(60), function () use ($tenant, $service, $minDate, $maxDate, $todayStr, $nowTimeStr) {
            $rows = DB::table('schedules')
                ->leftJoin('bookings', function ($join) {
                    $join->on('schedules.id', '=', 'bookings.idschedule')
                        ->where(function ($q) {
                            $q->whereIn('bookings.status', ['paid', 'completed'])
                              ->orWhere(function ($sub) {
                                  $sub->where('bookings.status', 'pending')
                                      ->where('bookings.created_at', '>=', now()->subMinutes(15));
                              });
                        });
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

            $blockedDates = DB::table('owner_blocked_dates')
                ->where('idtenant', $tenant->id)
                ->whereBetween('tanggal', [$minDate->toDateString(), $maxDate->toDateString()])
                ->pluck('tanggal')
                ->map(fn ($d) => Carbon::parse($d)->toDateString())
                ->all();

            return $rows->map(function ($row) use ($blockedDates) {
                $dateStr = Carbon::parse($row->tanggal)->toDateString();
                $isBlocked = in_array($dateStr, $blockedDates, true);

                return [
                    'date' => $dateStr,
                    'total_slots' => (int) $row->total_slots,
                    'available_slots' => $isBlocked ? 0 : (int) $row->available_slots,
                ];
            })->values()->all();
        });

        $priceLabel = 'Rp ' . number_format($service->harga, 0, ',', '.');
        $servicePayload = [
            'id' => $service->id,
            'name' => $service->namalayanan,
            'price' => (float) $service->harga,
            'price_label' => $priceLabel,
            'price_unit' => $service->satuan_harga ?: 'sesi',
            'duration' => (int) $service->durasi,
            'duration_unit' => $service->satuan_durasi ?: 'menit',
        ];

        $selectedDate = null;

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
            return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha)
                ->withErrors(['tanggal' => 'Selected date is outside the booking window.']);
        }

        if ($simulate) {
            session()->put('booking.tanggal', $selectedDate);
            session()->put('booking.jam', null);

            return CustomerBookingRoutes::route('customer.booking.time', [
                'slug_usaha' => $slug_usaha,
                'simulate' => 1,
            ]);
        }

        // Subscription monthly booking limit check
        $subscription = \App\Models\Subscription::with('plan')->where('idtenant', $tenant->id)->latest()->first();
        $isUnlimitedBooking = ($subscription && $subscription->status === 'trial')
            || ($subscription?->plan?->isunlimited ?? false)
            || (($subscription?->plan?->namapaket ?? '') === 'pro')
            || (($subscription?->plan?->maxbooking ?? 0) <= 0);

        if (!$isUnlimitedBooking && ($subscription?->plan?->maxbooking ?? 0) > 0) {
            $dateCarbon = Carbon::parse($selectedDate);
            $totalMonthlyBookings = DB::table('bookings')
                ->where('idtenant', $tenant->id)
                ->whereYear('tanggalbooking', $dateCarbon->year)
                ->whereMonth('tanggalbooking', $dateCarbon->month)
                ->whereIn('status', ['pending', 'paid', 'completed'])
                ->count();

            if ($totalMonthlyBookings >= $subscription->plan->maxbooking) {
                return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha)
                    ->withErrors(['tanggal' => 'Kapasitas kuota booking bulanan bisnis ini telah penuh (maksimal ' . $subscription->plan->maxbooking . ' booking/bulan). Silakan hubungi pemilik bisnis.']);
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

        $wib = 'Asia/Jakarta';
        $nowWib = Carbon::now($wib);
        $isToday = Carbon::parse($selectedDate, $wib)->isSameDay($nowWib);

        $availableSlotsQuery = DB::table('schedules')
            ->leftJoin('bookings', function ($join) {
                $join->on('schedules.id', '=', 'bookings.idschedule')
                    ->where(function ($q) {
                        $q->whereIn('bookings.status', ['paid', 'completed'])
                          ->orWhere(function ($sub) {
                              $sub->where('bookings.status', 'pending')
                                  ->where('bookings.created_at', '>=', now()->subMinutes(15));
                          });
                    });
            })
            ->where('schedules.idtenant', $tenant->id)
            ->where('schedules.idlayanan', $service->id)
            ->where('schedules.status', 'tersedia')
            ->whereDate('schedules.tanggal', $selectedDate)
            ->whereNull('bookings.id');

        if ($isToday) {
            $nowTimeStr = $nowWib->format('H:i');
            $availableSlotsQuery->whereRaw("substr(schedules.jam_mulai, 1, 5) > ?", [$nowTimeStr]);
        }

        $availableSlots = $availableSlotsQuery->count();

        if ($availableSlots < 1) {
            return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha)
                ->withErrors(['tanggal' => 'Selected date is fully booked.']);
        }

        session()->put('booking.tanggal', $selectedDate);
        session()->put('booking.jam', null);

        return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha);
    }

    public function showTimeSelection(string $slug_usaha)
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
        $selectedDate = $booking['tanggal'] ?? null;

        if (!$serviceId) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        if (!$selectedDate) {
            return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (!$service || !$service->hasActiveFulfillment()) {
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

        $scheduleCacheKey = $this->getSchedulesCacheKey($tenant->id, $service->id, $selectedDate);

        $scheduleRows = Cache::remember($scheduleCacheKey, now()->addSeconds(300), function () use ($tenant, $service, $selectedDate) {
            return DB::table('schedules')
                ->leftJoin('bookings', function ($join) {
                    $join->on('schedules.id', '=', 'bookings.idschedule')
                        ->where(function ($q) {
                            $q->whereIn('bookings.status', ['paid', 'completed'])
                              ->orWhere(function ($sub) {
                                  $sub->where('bookings.status', 'pending')
                                      ->where('bookings.created_at', '>=', now()->subMinutes(15));
                              });
                        });
                })
                ->where('schedules.idtenant', $tenant->id)
                ->where('schedules.idlayanan', $service->id)
                ->where('schedules.status', 'tersedia')
                ->whereDate('schedules.tanggal', $selectedDate)
                ->groupBy('schedules.id', 'schedules.jam_mulai', 'schedules.jam_selesai', 'schedules.tanggal', 'schedules.harga_override')
                ->orderBy('schedules.jam_mulai')
                ->select([
                    'schedules.id',
                    'schedules.jam_mulai',
                    'schedules.jam_selesai',
                    'schedules.tanggal',
                    'schedules.harga_override',
                    DB::raw('count(bookings.id) as booking_count'),
                ])
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => (int) $row->id,
                        'jam_mulai' => $row->jam_mulai,
                        'jam_selesai' => $row->jam_selesai,
                        'tanggal' => $row->tanggal,
                        'harga_override' => $row->harga_override !== null ? (float) $row->harga_override : null,
                        'booking_count' => (int) $row->booking_count,
                    ];
                })
                ->all();
        });

        $wib = 'Asia/Jakarta';
        $nowWib = Carbon::now($wib);
        $selectedDateCarbon = Carbon::parse($selectedDate, $wib);
        $isToday = $selectedDateCarbon->isSameDay($nowWib);
        $isPastDate = $selectedDateCarbon->lt($nowWib->copy()->startOfDay());

        $timeSlotsPayload = collect($scheduleRows)->map(function (array $row) use ($isToday, $isPastDate, $nowWib, $selectedDate, $wib, $service) {
            $slotDateTime = Carbon::parse($selectedDate . ' ' . $row['jam_mulai'], $wib);
            $hour = (int) $slotDateTime->format('H');
            $session = 'evening';

            if ($hour >= 5 && $hour <= 11) {
                $session = 'morning';
            } elseif ($hour >= 12 && $hour <= 17) {
                $session = 'afternoon';
            }

            $isPast = $isPastDate || ($isToday && $slotDateTime->lessThanOrEqualTo($nowWib));
            $isBooked = $row['booking_count'] > 0;
            $isAvailable = !$isPast && !$isBooked;
            $slotPrice = $row['harga_override'] !== null ? (float) $row['harga_override'] : (float) $service->harga;

            return [
                'id' => $row['id'],
                'time' => $slotDateTime->format('H:i'),
                'label' => $slotDateTime->format('H:i'),
                'period' => 'WIB',
                'session' => $session,
                'price' => $slotPrice,
                'price_label' => 'Rp ' . number_format($slotPrice, 0, ',', '.'),
                'is_available' => $isAvailable,
                'is_disabled' => !$isAvailable,
                'is_booked' => $isBooked,
                'is_past' => $isPast,
            ];
        })->values()->all();

        $priceLabel = 'Rp ' . number_format($service->harga, 0, ',', '.');
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
        $selectedDateLabel = Carbon::parse($selectedDate)->format('l, F jS');

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
        $selectedDate = $booking['tanggal'] ?? null;

        if (!$serviceId) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        if (!$selectedDate) {
            return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (!$service || !$service->hasActiveFulfillment()) {
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
            'jam'   => ['required', 'array', 'min:1'],
            'jam.*' => ['required', 'date_format:H:i', 'distinct'],
        ];

        if ($simulate) {
            $rules['schedule_ids']   = ['nullable', 'array'];
            $rules['schedule_ids.*'] = ['nullable', 'integer'];
        } else {
            $rules['schedule_ids']   = ['required', 'array', 'min:1'];
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

                if (!$row) {
                    return null;
                }

                $isBooked = DB::table('bookings')
                    ->where('idschedule', $scheduleId)
                    ->where(function ($q) {
                        $q->whereIn('status', ['paid', 'completed'])
                          ->orWhere(function ($sub) {
                              $sub->where('status', 'pending')
                                  ->where('created_at', '>=', now()->subMinutes(15));
                          });
                    })
                    ->exists();

                return $isBooked ? null : $row;
            });

            if (!$schedule) {
                return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                    ->withErrors(['jam' => "Jam {$jamValue} sudah tidak tersedia. Silakan pilih jam lain."]);
            }

            $slotDateTime = Carbon::parse($selectedDate . ' ' . $schedule->jam_mulai, $wib);
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
        usort($validatedSchedules, fn($a, $b) => strcmp($a->jam_mulai, $b->jam_mulai));
        $durationMinutes = (int) $service->durasi;

        for ($i = 1; $i < count($validatedSchedules); $i++) {
            $prevStart = Carbon::parse($selectedDate . ' ' . $validatedSchedules[$i - 1]->jam_mulai, $wib);
            $currStart = Carbon::parse($selectedDate . ' ' . $validatedSchedules[$i]->jam_mulai, $wib);
            if ((int) abs($currStart->diffInMinutes($prevStart)) !== $durationMinutes) {
                return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                    ->withErrors(['jam' => 'Slot harus berurutan tanpa jeda.']);
            }
        }

        $validatedScheduleIds = array_map(fn($s) => $s->id, $validatedSchedules);
        $validatedTimes = array_map(fn($s) => Carbon::parse($selectedDate . ' ' . $s->jam_mulai, $wib)->format('H:i'), $validatedSchedules);

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

        if (!$tenant) {
            abort(404);
        }

        $booking = session('booking', []);
        $serviceId = $booking['service_id'] ?? null;
        $selectedDate = $booking['tanggal'] ?? null;
        $rawJam = $booking['jam'] ?? null;
        $rawScheduleIds = $booking['schedule_ids'] ?? (!empty($booking['schedule_id']) ? [$booking['schedule_id']] : []);

        // Normalise to arrays
        $selectedTimes = is_array($rawJam) ? $rawJam : ($rawJam ? [$rawJam] : []);
        $scheduleIds   = is_array($rawScheduleIds) ? $rawScheduleIds : ($rawScheduleIds ? [$rawScheduleIds] : []);

        if (!$serviceId || !$selectedDate || empty($selectedTimes)) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);
        if (!$service || !$service->hasActiveFulfillment()) {
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
                    ->find($sid);
            }
            if (!$schedule) {
                $schedule = Schedule::where('idtenant', $tenant->id)
                    ->where('idlayanan', $service->id)
                    ->whereDate('tanggal', $selectedDate)
                    ->where('jam_mulai', $time . ':00')
                    ->first();
            }
            $schedules[] = $schedule;
            $hargaPerSlot[] = $schedule && $schedule->harga_override !== null ? (float) $schedule->harga_override : (float) $service->harga;
        }

        $hargaAkhir = array_sum($hargaPerSlot);
        $selectedTime = $selectedTimes[0] ?? null;

        return view('customer.booking.checkout', compact(
            'tenant',
            'service',
            'selectedDate',
            'selectedTime',
            'selectedTimes',
            'hargaAkhir',
            'schedules'
        ));
    }

    /**
     * Process checkout for multiple time slots.
     * Creates one booking per slot, with a single payment covering total.
     */
    public function processCheckout(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant) {
            abort(404);
        }

        $booking = session('booking', []);
        $serviceId = $booking['service_id'] ?? null;
        $selectedDate = $booking['tanggal'] ?? null;
        $rawJam = $booking['jam'] ?? $request->input('jam');
        $rawScheduleIds = $booking['schedule_ids'] ?? (!empty($booking['schedule_id']) ? [$booking['schedule_id']] : ($request->input('schedule_ids') ?? ($request->has('schedule_id') ? [$request->input('schedule_id')] : [])));

        if (is_string($rawJam)) {
            $rawJam = [$rawJam];
        }
        if (is_numeric($rawScheduleIds)) {
            $rawScheduleIds = [(int) $rawScheduleIds];
        }

        // Normalise to arrays
        $selectedTimes = is_array($rawJam) ? $rawJam : ($rawJam ? [$rawJam] : []);
        $scheduleIds   = is_array($rawScheduleIds) ? $rawScheduleIds : ($rawScheduleIds ? [$rawScheduleIds] : []);

        // Fallback: If scheduleIds is empty but selectedTimes is present, lookup schedule IDs from DB
        if (empty($scheduleIds) && !empty($selectedTimes) && $selectedDate && $serviceId) {
            foreach ($selectedTimes as $time) {
                $foundSchedule = DB::table('schedules')
                    ->where('idtenant', $tenant->id)
                    ->where('idlayanan', (int) $serviceId)
                    ->whereDate('tanggal', $selectedDate)
                    ->where(function ($q) use ($time) {
                        $q->where('jam_mulai', $time)
                          ->orWhere('jam_mulai', $time . ':00');
                    })
                    ->first();
                if ($foundSchedule) {
                    $scheduleIds[] = $foundSchedule->id;
                }
            }
        }

        if (!$serviceId || !$selectedDate || empty($selectedTimes)) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (!$service || !$service->hasActiveFulfillment()) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $request->validate([
            'namapelanggan' => 'required|string|max:150',
            'nomorhp'       => 'required|string|max:20',
            'email'         => 'required|email|max:100',
            'catatan'       => 'nullable|string|max:500',
        ]);

        // Section 18: Add-on feature disabled in production
        if ($request->filled('selected_addons') || (!empty($request->selected_addons))) {
            return back()->withInput()->withErrors([
                'selected_addons' => 'Fitur item tambahan (add-on) belum tersedia.',
                'addons'          => 'Fitur item tambahan (add-on) belum tersedia.',
            ]);
        }

        // Section 20: Voucher feature disabled in production
        if ($request->filled('voucher_code')) {
            return back()->withInput()->withErrors([
                'voucher_code' => 'Fitur voucher diskon belum tersedia.',
                'voucher'      => 'Fitur voucher diskon belum tersedia.',
            ]);
        }

        if (empty($scheduleIds) || empty($selectedTimes) || count($selectedTimes) !== count($scheduleIds)) {
            return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                ->withErrors(['jam' => 'Pilihan jadwal tidak valid.']);
        }

        if (count($scheduleIds) !== count(array_unique($scheduleIds))) {
            return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                ->withErrors(['jam' => 'Jadwal yang dipilih tidak boleh duplikat.']);
        }

        $slotCount = count($scheduleIds);

        // Wrap slot availability check, atomic quota check, and booking/payment creation in a transaction
        $result = DB::transaction(function () use ($tenant, $service, $selectedDate, $selectedTimes, $scheduleIds, $request, $slotCount, $slug_usaha) {
            $wib = 'Asia/Jakarta';
            $nowWib = Carbon::now($wib);

            // 1. Atomic monthly booking quota check inside transaction: lock stable synchronization row (Subscription or Tenant)
            $subscription = \App\Models\Subscription::with('plan')
                ->where('idtenant', $tenant->id)
                ->latest()
                ->lockForUpdate()
                ->first();

            if (!$subscription) {
                DB::table('tenants')->where('id', $tenant->id)->lockForUpdate()->first();
            }

            $isUnlimitedBooking = ($subscription && $subscription->status === 'trial')
                || ($subscription?->plan?->isunlimited ?? false)
                || (($subscription?->plan?->namapaket ?? '') === 'pro')
                || (($subscription?->plan?->maxbooking ?? 0) <= 0);

            if (!$isUnlimitedBooking && ($subscription?->plan?->maxbooking ?? 0) > 0) {
                $dateCarbon = Carbon::parse($selectedDate, $wib);
                $totalMonthlyBookings = DB::table('bookings')
                    ->where('idtenant', $tenant->id)
                    ->whereYear('tanggalbooking', $dateCarbon->year)
                    ->whereMonth('tanggalbooking', $dateCarbon->month)
                    ->whereIn('status', ['pending', 'paid', 'completed'])
                    ->count();

                if (($totalMonthlyBookings + $slotCount) > $subscription->plan->maxbooking) {
                    return [
                        'quota_error' => true,
                        'error' => 'Kapasitas kuota booking bulanan bisnis ini tidak mencukupi (tersisa ' . max(0, $subscription->plan->maxbooking - $totalMonthlyBookings) . ' dari ' . $subscription->plan->maxbooking . '). Silakan kurangi jumlah slot atau hubungi pemilik bisnis.'
                    ];
                }
            }

            // 2. Lock all selected schedules
            $lockedSchedules = DB::table('schedules')
                ->whereIn('id', $scheduleIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($lockedSchedules->count() !== $slotCount) {
                return ['error' => 'Satu atau lebih jadwal tidak ditemukan atau tidak tersedia.'];
            }

            $orderedSchedules = [];
            foreach ($scheduleIds as $sid) {
                $schedule = $lockedSchedules->get($sid);
                if (!$schedule) {
                    return ['error' => 'Jadwal tidak ditemukan.'];
                }

                if ((int) $schedule->idtenant !== (int) $tenant->id) {
                    return ['error' => 'Jadwal tidak sesuai dengan unit bisnis terpilih.'];
                }

                if ((int) $schedule->idlayanan !== (int) $service->id) {
                    return ['error' => 'Jadwal tidak sesuai dengan paket layanan terpilih.'];
                }

                $schedDate = Carbon::parse($schedule->tanggal, $wib)->toDateString();
                if ($schedDate !== $selectedDate) {
                    return ['error' => 'Tanggal jadwal tidak sesuai dengan tanggal pemesanan.'];
                }

                if ($schedule->status !== 'tersedia') {
                    return ['error' => "Jadwal untuk jam {$schedule->jam_mulai} tidak tersedia."];
                }

                $slotDateTime = Carbon::parse($schedDate . ' ' . $schedule->jam_mulai, $wib);
                if ($slotDateTime->lessThanOrEqualTo($nowWib)) {
                    return ['error' => "Waktu sesi {$schedule->jam_mulai} sudah terlewat (WIB). Silakan pilih waktu lain."];
                }

                // Check if slot already has an active booking
                $isBooked = DB::table('bookings')
                    ->where('idschedule', $schedule->id)
                    ->where(function ($q) {
                        $q->whereIn('status', ['paid', 'completed'])
                          ->orWhere(function ($sub) {
                              $sub->where('status', 'pending')
                                  ->where('created_at', '>=', now()->subMinutes(15));
                          });
                    })
                    ->exists();

                if ($isBooked) {
                    return ['error' => "Slot waktu {$schedule->jam_mulai} sudah dibooking oleh pelanggan lain. Silakan pilih waktu lain."];
                }

                $orderedSchedules[] = $schedule;
            }

            // 3. Contiguous validation: Sort schedules by jam_mulai ascending
            usort($orderedSchedules, fn($a, $b) => strcmp($a->jam_mulai, $b->jam_mulai));
            $durationMinutes = (int) $service->durasi;

            for ($i = 1; $i < count($orderedSchedules); $i++) {
                $prevStart = Carbon::parse($selectedDate . ' ' . $orderedSchedules[$i - 1]->jam_mulai, $wib);
                $currStart = Carbon::parse($selectedDate . ' ' . $orderedSchedules[$i]->jam_mulai, $wib);
                if ((int) abs($currStart->diffInMinutes($prevStart)) !== $durationMinutes) {
                    return ['error' => 'Slot harus berurutan tanpa jeda.'];
                }
            }

            // 4. Calculate slot prices based on schedule override or service price
            $slotPrices = [];
            foreach ($orderedSchedules as $schedule) {
                $slotPrices[] = $schedule->harga_override !== null ? (float) $schedule->harga_override : (float) $service->harga;
            }

            $hargaAkhir = max(0, array_sum($slotPrices));

            $fullCatatan = trim($request->catatan ?? '');
            if ($slotCount > 1) {
                $timeList = implode(', ', array_map(fn($s) => substr($s->jam_mulai, 0, 5), $orderedSchedules));
                $fullCatatan .= ($fullCatatan ? ' | ' : '') . "Multi-slot ({$slotCount}x): {$timeList}";
            }

            // 5. Free booking path ($0)
            if ($hargaAkhir <= 0) {
                $orderId = 'FREE-' . $tenant->id . '-' . time() . '-' . rand(100, 999);
                $payment = Payment::create([
                    'idtenant'       => $tenant->id,
                    'tipe'           => 'booking',
                    'jumlah'         => 0,
                    'status'         => 'sukses',
                    'metode'         => 'gratis',
                    'order_id'       => $orderId,
                    'manage_token'   => Booking::generateSecureToken(),
                    'nama_pembayar'  => $request->namapelanggan,
                    'email_pembayar' => $request->email,
                    'hp_pembayar'    => $request->nomorhp,
                    'catatan'        => $fullCatatan ?: null,
                ]);

                $createdBookings = [];
                foreach ($orderedSchedules as $schedule) {
                    $slotTime = Carbon::parse($selectedDate . ' ' . $schedule->jam_mulai, $wib)->format('H:i');
                    $bk = Booking::create([
                        'idtenant'           => $tenant->id,
                        'idlayanan'          => $service->id,
                        'idschedule'         => $schedule->id,
                        'namapelanggan'      => $request->namapelanggan,
                        'nomorhp'            => $request->nomorhp,
                        'email'              => $request->email,
                        'tanggalbooking'     => $selectedDate,
                        'jam'                => $slotTime . ':00',
                        'status'             => 'paid',
                        'idpayment'          => $payment->id,
                        'booking_code'       => Booking::generateBookingCode(),
                        'cancellation_token' => Booking::generateSecureToken(),
                        'reschedule_token'   => Booking::generateSecureToken(),
                        'catatan'            => $fullCatatan ?: null,
                    ]);
                    $createdBookings[] = $bk;
                }

                if (!empty($createdBookings)) {
                    $payment->update(['idbooking' => $createdBookings[0]->id]);
                }

                return [
                    'free'       => true,
                    'payment'    => $payment,
                    'bookings'   => $createdBookings,
                    'schedules'  => $orderedSchedules,
                    'hargaAkhir' => 0,
                ];
            }

            // 6. Paid booking path: create single payment and N bookings with status 'pending'
            $orderId = 'BKG-' . $tenant->id . '-' . time() . '-' . rand(100, 999);

            $payment = Payment::create([
                'idtenant'       => $tenant->id,
                'tipe'           => 'booking',
                'jumlah'         => $hargaAkhir,
                'status'         => 'pending',
                'metode'         => 'midtrans',
                'order_id'       => $orderId,
                'manage_token'   => Booking::generateSecureToken(),
                'expired_at'     => now()->addMinutes(15),
                'nama_pembayar'  => $request->namapelanggan,
                'email_pembayar' => $request->email,
                'hp_pembayar'    => $request->nomorhp,
                'catatan'        => $fullCatatan ?: null,
            ]);

            $createdBookings = [];
            foreach ($orderedSchedules as $schedule) {
                $slotTime = Carbon::parse($selectedDate . ' ' . $schedule->jam_mulai, $wib)->format('H:i');
                $newBooking = Booking::create([
                    'idtenant'           => $tenant->id,
                    'idlayanan'          => $service->id,
                    'idschedule'         => $schedule->id,
                    'namapelanggan'      => $request->namapelanggan,
                    'nomorhp'            => $request->nomorhp,
                    'email'              => $request->email,
                    'tanggalbooking'     => $selectedDate,
                    'jam'                => $slotTime . ':00',
                    'status'             => 'pending',
                    'idpayment'          => $payment->id,
                    'booking_code'       => Booking::generateBookingCode(),
                    'cancellation_token' => Booking::generateSecureToken(),
                    'reschedule_token'   => Booking::generateSecureToken(),
                    'catatan'            => $fullCatatan ?: null,
                ]);
                $createdBookings[] = $newBooking;
            }

            if (!empty($createdBookings)) {
                $payment->update(['idbooking' => $createdBookings[0]->id]);
            }

            return [
                'free'       => false,
                'bookings'   => $createdBookings,
                'payment'    => $payment,
                'schedules'  => $orderedSchedules,
                'orderId'    => $orderId,
                'hargaAkhir' => $hargaAkhir,
            ];
        });

        // Handle slot conflict or quota error
        if (isset($result['error'])) {
            if (isset($result['quota_error'])) {
                return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha)
                    ->withErrors(['tanggal' => $result['error']]);
            }
            return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha)
                ->withErrors(['jam' => $result['error']]);
        }

        // Free booking: send notifications and redirect to invoice
        if ($result['free']) {
            $payment = $result['payment'];
            $bookingsList = collect($result['bookings']);
            $firstBooking = $bookingsList->first();

            if ($firstBooking) {
                $firstBooking->load(['tenant.user', 'layanan', 'payment']);

                $owner = $firstBooking->tenant?->user;
                if ($owner) {
                    try {
                        $owner->notify(new \App\Notifications\NewBookingOwnerNotification($firstBooking));
                    } catch (\Exception $e) {
                        Log::error('Gagal kirim notif free booking ke owner: ' . $e->getMessage());
                    }
                }

                $recipientEmail = $firstBooking->email ?: $payment->email_pembayar;
                if ($recipientEmail) {
                    try {
                        $manageUrl = $payment->getManageUrl();
                        \Illuminate\Support\Facades\Mail::to($recipientEmail)
                            ->send(new BookingGroupInvoiceMail($payment, $bookingsList, $manageUrl));
                    } catch (\Exception $e) {
                        Log::error('Gagal kirim email invoice grup free booking: ' . $e->getMessage());
                    }
                }
            }

            $this->clearBookingAvailabilityCache(
                $tenant->id,
                $service->id,
                Carbon::parse($selectedDate)->toDateString()
            );
            session()->forget('booking');
            return CustomerBookingRoutes::route('customer.booking.invoice', [$slug_usaha, $payment])
                ->with('success', 'Booking berhasil dikonfirmasi! (' . count($result['bookings']) . ' slot)');
        }

        // Paid booking: get Midtrans snap token
        $payment    = $result['payment'];
        $bookings   = $result['bookings'];
        $hargaAkhir = $result['hargaAkhir'];
        $orderId    = $result['orderId'];

        $itemName = 'Booking: ' . $service->namalayanan;
        if (count($bookings) > 1) {
            $itemName .= ' (' . count($bookings) . ' slot)';
        }

        $params = [
            'transaction_details' => [
                'order_id'    => $orderId,
                'gross_amount' => (int) $hargaAkhir,
            ],
            'customer_details' => [
                'first_name' => $request->namapelanggan,
                'email'      => $request->email,
                'phone'      => $request->nomorhp,
            ],
            'item_details' => [
                [
                    'id'       => 'SRV-' . $service->id,
                    'price'    => (int) $hargaAkhir,
                    'quantity' => 1,
                    'name'     => $itemName,
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit'       => 'minute',
                'duration'   => 15,
            ],
        ];

        try {
            $snapToken = app()->environment('testing')
                ? 'mocked-snap-token'
                : Snap::getSnapToken($params);
            $payment->update(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Error (Booking): ' . $e->getMessage());
            // Snap token failed — cancel payment and all bookings, release slots
            DB::transaction(function () use ($payment, $bookings) {
                $payment->update(['status' => 'gagal']);
                foreach ($bookings as $bk) {
                    $bk->update(['status' => 'cancelled']);
                }
            });

            // Invalidate cache so slots are free again
            $this->clearBookingAvailabilityCache(
                $tenant->id,
                $service->id,
                Carbon::parse($selectedDate)->toDateString()
            );

            return back()->with('error', 'Gagal memproses pembayaran. Error: ' . $e->getMessage());
        }

        // Invalidate cache immediately so slots are marked unavailable on public calendar
        $this->clearBookingAvailabilityCache(
            $tenant->id,
            $service->id,
            Carbon::parse($selectedDate)->toDateString()
        );

        session()->forget('booking');
        return CustomerBookingRoutes::route('customer.booking.payment', [$slug_usaha, $payment]);
    }

    public function validateVoucher(Request $request, string $slug_usaha)
    {
        return response()->json([
            'valid'   => false,
            'message' => 'Fitur voucher saat ini belum aktif.',
        ], 422);
    }

    public function showPayment(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        if ($payment->status === 'sukses') {
            return CustomerBookingRoutes::route('customer.booking.invoice', [$slug_usaha, $payment]);
        }

        if ($payment->isExpired() && $payment->status === 'pending') {
            DB::transaction(function () use ($payment) {
                $lockedPayment = Payment::lockForUpdate()->find($payment->id);
                if ($lockedPayment && $lockedPayment->status === 'pending') {
                    $lockedPayment->update(['status' => 'gagal']);
                    $bookings = Booking::lockForUpdate()->where('idpayment', $lockedPayment->id)->get();
                    foreach ($bookings as $bk) {
                        $bk->update(['status' => 'cancelled']);
                    }

                    DB::afterCommit(function () use ($bookings) {
                        foreach ($bookings as $bk) {
                            if ($bk->idlayanan && $bk->tanggalbooking) {
                                $tanggal = is_string($bk->tanggalbooking)
                                    ? $bk->tanggalbooking
                                    : $bk->tanggalbooking->format('Y-m-d');
                                $this->clearBookingAvailabilityCache((int) $bk->idtenant, (int) $bk->idlayanan, $tanggal);
                            }
                        }
                    });
                }
            });

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha)
                ->with('error', 'Waktu pembayaran telah habis.');
        }

        $payment->load(['bookings.layanan']);

        return view('customer.booking.payment', [
            'tenant' => $tenant,
            'payment' => $payment,
            'snapToken' => $payment->snap_token,
            'clientKey' => config('midtrans.client_key'),
            'snapUrl' => config('midtrans.snap_url'),
        ]);
    }

    public function cancelPayment(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        if ($payment->status === 'pending') {
            DB::transaction(function () use ($payment) {
                $lockedPayment = Payment::lockForUpdate()->find($payment->id);
                if ($lockedPayment && $lockedPayment->status === 'pending') {
                    $lockedPayment->update(['status' => 'gagal']);
                    $bookings = Booking::lockForUpdate()->where('idpayment', $lockedPayment->id)->get();
                    foreach ($bookings as $bk) {
                        $bk->update(['status' => 'cancelled']);
                    }

                    DB::afterCommit(function () use ($bookings) {
                        foreach ($bookings as $bk) {
                            if ($bk->idlayanan && $bk->tanggalbooking) {
                                $tanggal = is_string($bk->tanggalbooking)
                                    ? $bk->tanggalbooking
                                    : $bk->tanggalbooking->format('Y-m-d');
                                $this->clearBookingAvailabilityCache((int) $bk->idtenant, (int) $bk->idlayanan, $tanggal);
                            }
                        }
                    });
                }
            });
        }

        session()->forget('booking');

        return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha)
            ->with('info', 'Transaksi berhasil dibatalkan. Anda dapat memilih layanan atau jadwal baru.');
    }

    public function checkPaymentStatus(string $slug_usaha, Payment $payment, MidtransPaymentService $paymentService)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant || $payment->idtenant !== $tenant->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Fast-path: jika database sudah sukses
        if ($payment->status === 'sukses') {
            return response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => CustomerBookingRoutes::url('customer.booking.invoice', [$slug_usaha, $payment]),
            ]);
        }

        $syncResult = $paymentService->verifyAndSync($payment);

        if ($syncResult['status'] === 'sukses') {
            return response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => CustomerBookingRoutes::url('customer.booking.invoice', [$slug_usaha, $payment]),
            ]);
        }

        if ($syncResult['status'] === 'gagal') {
            return response()->json([
                'status' => 'gagal',
                'message' => $syncResult['message'] ?? 'Pembayaran gagal atau dibatalkan.',
            ]);
        }

        if ($syncResult['status'] === 'error') {
            // Periksa ulang database untuk mengantisipasi webhook masuk bersamaan
            $payment->refresh();
            if ($payment->status === 'sukses') {
                return response()->json([
                    'status' => 'sukses',
                    'message' => 'Pembayaran berhasil dikonfirmasi!',
                    'redirect' => CustomerBookingRoutes::url('customer.booking.invoice', [$slug_usaha, $payment]),
                ]);
            }

            // Kembalikan pending agar auto-poller terus mencoba tanpa menampilkan error palsu ke user
            return response()->json([
                'status' => 'pending',
                'message' => 'Sedang memverifikasi dengan payment gateway...',
            ]);
        }

        return response()->json([
            'status' => 'pending',
            'message' => $syncResult['message'] ?? 'Menunggu pembayaran diselesaikan...',
        ]);
    }

    public function handleCallback(string $slug_usaha, Payment $payment, Request $request, MidtransPaymentService $paymentService)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant || $payment->idtenant !== $tenant->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Fast-path jika database sudah sukses
        if ($payment->status === 'sukses') {
            return response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => CustomerBookingRoutes::url('customer.booking.invoice', [$slug_usaha, $payment]),
            ]);
        }

        // Lakukan server-side verification ke Midtrans (Midtrans server adalah authority tunggal)
        // P0 Security Fix: HAPUS fallback client payload! Browser payload tidak boleh dipercaya
        $syncResult = $paymentService->verifyAndSync($payment);

        if ($syncResult['status'] === 'sukses') {
            return response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => CustomerBookingRoutes::url('customer.booking.invoice', [$slug_usaha, $payment]),
            ]);
        }

        if ($syncResult['status'] === 'gagal') {
            return response()->json([
                'status' => 'gagal',
                'message' => $syncResult['message'] ?? 'Pembayaran gagal atau dibatalkan.',
            ]);
        }

        return response()->json([
            'status' => 'pending',
            'message' => $syncResult['message'] ?? 'Pembayaran sedang diproses / diverifikasi.',
        ]);
    }

    public function showInvoice(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant || $payment->idtenant !== $tenant->id) {
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

        foreach ($bookings as $bk) {
            if (!$bk->booking_code) {
                $bk->assignManagementTokens();
                $bk->refresh();
            }
        }

        $booking = $bookings->first();

        return view('customer.booking.invoice', compact('tenant', 'payment', 'bookings', 'booking'));
    }

    private function resolveTenant(string $slug_usaha): ?Tenant
    {
        $tenantData = Cache::remember("tenant:slug:{$slug_usaha}", now()->addSeconds(3600), function () use ($slug_usaha) {
            return Tenant::query()->where('slug', $slug_usaha)->first()?->getAttributes();
        });

        if (!$tenantData) {
            return null;
        }

        $tenant = Tenant::hydrate([$tenantData])->first();
        if ($tenant && !app(\App\Support\TenantContext::class)->hasTenant()) {
            app(\App\Support\TenantContext::class)->setTenantId($tenant->id);
        }

        return $tenant;
    }

    private function resolveService(int $tenantId, int $serviceId): ?Service
    {
        $serviceData = Cache::remember($this->getServiceCacheKey($tenantId, $serviceId), now()->addSeconds(3600), function () use ($tenantId, $serviceId) {
            return Service::withoutGlobalScopes()
                ->where('id', $serviceId)
                ->where('idtenant', $tenantId)
                ->where('is_active', true)
                ->first()?->getAttributes();
        });

        if (!$serviceData) {
            return null;
        }

        return Service::hydrate([$serviceData])->first();
    }
}

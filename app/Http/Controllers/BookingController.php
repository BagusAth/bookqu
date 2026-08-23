<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Transaction as MidtransTransaction;

class BookingController extends Controller
{
    public function __construct()
    {
        MidtransConfig::$serverKey = config('midtrans.server_key');
        MidtransConfig::$isProduction = config('midtrans.is_production');
        MidtransConfig::$isSanitized = config('midtrans.is_sanitized');
        MidtransConfig::$is3ds = config('midtrans.is_3ds');
<<<<<<< HEAD

=======
        
>>>>>>> 6a13f203747615a9225aa2afecb9fa6d553ad20e
        MidtransConfig::$curlOptions = [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => [],
        ];
    }
<<<<<<< HEAD

=======
>>>>>>> 6a13f203747615a9225aa2afecb9fa6d553ad20e
    public function showProgramSelection(string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        if (!$tenant) {
            abort(404);
        }

        $servicesData = Cache::remember("tenant:{$tenant->id}:services:active", now()->addSeconds(3600), function () use ($tenant) {
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

        $servicesPayload = $services->map(function (Service $service) {
            $priceLabel = 'Rp ' . number_format($service->harga, 0, ',', '.');

            return [
                'id' => $service->id,
                'name' => $service->namalayanan,
                'price' => (float) $service->harga,
                'price_label' => $priceLabel,
                'price_unit' => $service->satuan_harga ?: 'sesi',
            ];
        })->values();

        return view('customer.booking.program-selection', compact('tenant', 'services', 'servicesPayload'));
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

        if (!$service) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        session([
            'booking' => [
                'tenant_id' => $tenant->id,
                'service_id' => $service->id,
                'tanggal' => null,
                'jam' => null,
            ],
        ]);

        return redirect()->route('customer.booking.date', $slug_usaha);
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
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking['service_id'] ?? null;

        if (!$serviceId) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (!$service) {
            session()->forget('booking');
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        session()->put('booking.tenant_id', $tenant->id);

        $minDate = Carbon::today();
        $maxDate = Carbon::today()->addDays(30);

        $availabilityKey = sprintf(
            'tenant:%s:service:%s:availability:%s:%s',
            $tenant->id,
            $service->id,
            $minDate->toDateString(),
            $maxDate->toDateString()
        );

        $availabilityPayload = Cache::remember($availabilityKey, now()->addSeconds(3600), function () use ($tenant, $service, $minDate, $maxDate) {
            $rows = DB::table('schedules')
                ->leftJoin('bookings', function ($join) {
                    $join->on('schedules.id', '=', 'bookings.idschedule')
                        ->whereIn('bookings.status', ['pending', 'paid', 'completed']);
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
                    'date' => Carbon::parse($row->tanggal)->toDateString(),
                    'total_slots' => (int) $row->total_slots,
                    'available_slots' => (int) $row->available_slots,
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

        if (!$tenant) {
            abort(404);
        }

        $booking = session('booking', []);
        $sessionTenantId = $booking['tenant_id'] ?? null;

        if ($sessionTenantId && (int) $sessionTenantId !== $tenant->id) {
            session()->forget('booking');
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking['service_id'] ?? null;

        if (!$serviceId) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (!$service) {
            session()->forget('booking');
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        session()->put('booking.tenant_id', $tenant->id);

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
        ]);

        $selectedDate = Carbon::parse($validated['tanggal'])->toDateString();
        $minDate = Carbon::today();
        $maxDate = Carbon::today()->addDays(30);

        if ($selectedDate < $minDate->toDateString() || $selectedDate > $maxDate->toDateString()) {
            return redirect()->route('customer.booking.date', $slug_usaha)
                ->withErrors(['tanggal' => 'Selected date is outside the booking window.']);
        }

        if ($simulate) {
            session()->put('booking.tanggal', $selectedDate);
            session()->put('booking.jam', null);

            return redirect()->route('customer.booking.time', [
                'slug_usaha' => $slug_usaha,
                'simulate' => 1,
            ]);
        }

        $subscription = \App\Models\Subscription::with('plan')->where('idtenant', $tenant->id)->latest()->first();
        $maxbooking = $subscription->plan->maxbooking ?? 500;

        $totalBookingsThatDay = DB::table('bookings')
            ->where('idtenant', $tenant->id)
            ->whereDate('tanggalbooking', $selectedDate)
            ->whereIn('status', ['pending', 'paid', 'completed'])
            ->count();

        if ($totalBookingsThatDay >= $maxbooking) {
            return redirect()->route('customer.booking.date', $slug_usaha)
                ->withErrors(['tanggal' => 'Kapasitas maksimal booking harian bisnis ini telah penuh.']);
        }

        $availableSlots = DB::table('schedules')
            ->leftJoin('bookings', function ($join) {
                $join->on('schedules.id', '=', 'bookings.idschedule')
                    ->whereIn('bookings.status', ['pending', 'paid', 'completed']);
            })
            ->where('schedules.idtenant', $tenant->id)
            ->where('schedules.idlayanan', $service->id)
            ->where('schedules.status', 'tersedia')
            ->whereDate('schedules.tanggal', $selectedDate)
            ->whereNull('bookings.id')
            ->count();

        if ($availableSlots < 1) {
            return redirect()->route('customer.booking.date', $slug_usaha)
                ->withErrors(['tanggal' => 'Selected date is fully booked.']);
        }

        session()->put('booking.tanggal', $selectedDate);
        session()->put('booking.jam', null);

        return redirect()->route('customer.booking.time', $slug_usaha);
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
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking['service_id'] ?? null;
        $selectedDate = $booking['tanggal'] ?? null;

        if (!$serviceId) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        if (!$selectedDate) {
            return redirect()->route('customer.booking.date', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (!$service) {
            session()->forget('booking');
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        session()->put('booking.tenant_id', $tenant->id);

        $selectedDate = Carbon::parse($selectedDate)->toDateString();
        $scheduleCacheKey = "tenant:{$tenant->id}:service:{$service->id}:schedules:{$selectedDate}";

        $scheduleRows = Cache::remember($scheduleCacheKey, now()->addSeconds(3600), function () use ($tenant, $service, $selectedDate) {
            return DB::table('schedules')
                ->leftJoin('bookings', function ($join) {
                    $join->on('schedules.id', '=', 'bookings.idschedule')
                        ->whereIn('bookings.status', ['pending', 'paid', 'completed']);
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
                    'schedules.tanggal',
                    DB::raw('count(bookings.id) as booking_count'),
                ])
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => (int) $row->id,
                        'jam_mulai' => $row->jam_mulai,
                        'jam_selesai' => $row->jam_selesai,
                        'tanggal' => $row->tanggal,
                        'booking_count' => (int) $row->booking_count,
                    ];
                })
                ->all();
        });

        $now = Carbon::now();
        $isToday = Carbon::parse($selectedDate)->isSameDay($now);

        $timeSlotsPayload = collect($scheduleRows)->map(function (array $row) use ($isToday, $now) {
            $startTime = Carbon::createFromFormat('H:i:s', $row['jam_mulai']);
            $hour = (int) $startTime->format('H');
            $session = 'evening';

            if ($hour >= 5 && $hour <= 11) {
                $session = 'morning';
            } elseif ($hour >= 12 && $hour <= 17) {
                $session = 'afternoon';
            }

            $isPast = $isToday && $startTime->lessThanOrEqualTo($now);
            $isBooked = $row['booking_count'] > 0;
            $isAvailable = !$isPast && !$isBooked;

            return [
                'id' => $row['id'],
                'time' => $startTime->format('H:i'),
                'label' => $startTime->format('H:i'),
                'period' => $startTime->format('A'),
                'session' => $session,
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

        $selectedTime = $booking['jam'] ?? null;
        $selectedDateLabel = Carbon::parse($selectedDate)->format('l, F jS');

        return view('customer.booking.time-selection', [
            'tenant' => $tenant,
            'service' => $service,
            'servicePayload' => $servicePayload,
            'timeSlotsPayload' => $timeSlotsPayload,
            'selectedDate' => $selectedDate,
            'selectedDateLabel' => $selectedDateLabel,
            'selectedTime' => $selectedTime,
            'simulate' => $simulate,
        ]);
    }

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
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking['service_id'] ?? null;
        $selectedDate = $booking['tanggal'] ?? null;

        if (!$serviceId) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        if (!$selectedDate) {
            return redirect()->route('customer.booking.date', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (!$service) {
            session()->forget('booking');
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        session()->put('booking.tenant_id', $tenant->id);

        $rules = [
            'jam' => ['required', 'date_format:H:i'],
        ];

        if ($simulate) {
            $rules['schedule_id'] = ['nullable', 'integer'];
        } else {
            $rules['schedule_id'] = ['required', 'integer'];
        }

        $validated = $request->validate($rules);

        if ($simulate) {
            session()->put('booking.jam', $validated['jam']);

            return redirect()->route('customer.booking.checkout', [
                'slug_usaha' => $slug_usaha,
                'simulate' => 1,
            ]);
        }

        $scheduleId = (int) $validated['schedule_id'];
        $selectedDate = Carbon::parse($selectedDate)->toDateString();
        $now = Carbon::now();

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
                ->whereIn('status', ['pending', 'paid', 'completed'])
                ->exists();

            return $isBooked ? null : $row;
        });

        if (!$schedule) {
            return redirect()->route('customer.booking.time', $slug_usaha)
                ->withErrors(['jam' => 'Selected time is no longer available.']);
        }

        $scheduleTime = Carbon::createFromFormat('H:i:s', $schedule->jam_mulai);
        $selectedTime = $scheduleTime->format('H:i');

        if ($validated['jam'] !== $selectedTime) {
            return redirect()->route('customer.booking.time', $slug_usaha)
                ->withErrors(['jam' => 'Selected time does not match the schedule.']);
        }

        if (Carbon::parse($selectedDate)->isSameDay($now) && $scheduleTime->lessThanOrEqualTo($now)) {
            return redirect()->route('customer.booking.time', $slug_usaha)
                ->withErrors(['jam' => 'Selected time has already passed.']);
        }

        session()->put('booking.jam', $selectedTime);

        return redirect()->route('customer.booking.checkout', $slug_usaha);
    }

    public function showCheckout(string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        if (!$tenant) {
            abort(404);
        }

        $booking = session('booking', []);
<<<<<<< HEAD
        $sessionTenantId = $booking['tenant_id'] ?? null;

        // ── Session integrity checks ──
        if ($sessionTenantId && (int) $sessionTenantId !== $tenant->id) {
            session()->forget('booking');
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking['service_id'] ?? null;
        $selectedDate = $booking['tanggal'] ?? null;
        $selectedTime = $booking['jam'] ?? null;

        if (!$serviceId) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        if (!$selectedDate) {
            return redirect()->route('customer.booking.date', $slug_usaha);
        }

        if (!$selectedTime) {
            return redirect()->route('customer.booking.time', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (!$service) {
            session()->forget('booking');
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        // ── Resolve schedule for the selected time ──
        $selectedDate = Carbon::parse($selectedDate)->toDateString();
        $timeFormatted = $selectedTime . ':00';

        $schedule = DB::table('schedules')
            ->where('idtenant', $tenant->id)
            ->where('idlayanan', $service->id)
            ->whereDate('tanggal', $selectedDate)
            ->where('jam_mulai', $timeFormatted)
            ->where('status', 'tersedia')
            ->first();

        $scheduleId = $schedule?->id;

        // ── Build view data ──
        $priceLabel = 'Rp ' . number_format($service->harga, 0, ',', '.');
        $selectedDateLabel = Carbon::parse($selectedDate)->format('l, d F Y');

        return view('customer.booking.checkout', [
            'tenant' => $tenant,
            'service' => $service,
            'selectedDate' => $selectedDate,
            'selectedDateLabel' => $selectedDateLabel,
            'selectedTime' => $selectedTime,
            'scheduleId' => $scheduleId,
            'priceLabel' => $priceLabel,
            'clientKey' => config('midtrans.client_key'),
            'snapUrl' => config('midtrans.snap_url'),
        ]);
    }

    public function createPayment(Request $request, string $slug_usaha): JsonResponse
    {
        $tenant = $this->resolveTenant($slug_usaha);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found.'], 404);
        }

        // ── Validate customer input ──
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:100'],
            'nomorhp' => ['required', 'string', 'max:20'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'schedule_id' => ['required', 'integer'],
        ]);

        // ── Validate session booking ──
        $booking = session('booking', []);
=======
>>>>>>> 6a13f203747615a9225aa2afecb9fa6d553ad20e
        $serviceId = $booking['service_id'] ?? null;
        $selectedDate = $booking['tanggal'] ?? null;
        $selectedTime = $booking['jam'] ?? null;

        if (!$serviceId || !$selectedDate || !$selectedTime) {
<<<<<<< HEAD
            return response()->json(['error' => 'Booking session expired. Please start over.'], 422);
        }

        if ((int) ($booking['tenant_id'] ?? 0) !== $tenant->id) {
            return response()->json(['error' => 'Invalid booking session.'], 422);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (!$service) {
            return response()->json(['error' => 'Service not found.'], 404);
        }

        $scheduleId = (int) $validated['schedule_id'];
        $selectedDate = Carbon::parse($selectedDate)->toDateString();
        $totalAmount = (int) $service->harga;

        // ── Create booking + payment inside transaction with lock ──
        try {
            $result = DB::transaction(function () use (
                $tenant, $service, $scheduleId, $selectedDate, $selectedTime,
                $totalAmount, $validated
            ) {
                // Lock the schedule to prevent double-booking
                $schedule = DB::table('schedules')
                    ->where('id', $scheduleId)
                    ->where('idtenant', $tenant->id)
                    ->where('idlayanan', $service->id)
                    ->whereDate('tanggal', $selectedDate)
                    ->where('status', 'tersedia')
                    ->lockForUpdate()
                    ->first();

                if (!$schedule) {
                    throw new \Exception('SLOT_UNAVAILABLE');
                }

                // Check no active booking for this schedule
                $isBooked = DB::table('bookings')
                    ->where('idschedule', $scheduleId)
                    ->whereIn('status', ['pending', 'paid', 'completed'])
                    ->exists();

                if ($isBooked) {
                    throw new \Exception('SLOT_TAKEN');
                }

                // Create booking
                $newBooking = Booking::create([
                    'idtenant' => $tenant->id,
                    'idlayanan' => $service->id,
                    'idschedule' => $scheduleId,
                    'namapelanggan' => $validated['nama'],
                    'nomorhp' => $validated['nomorhp'],
                    'email' => $validated['email'],
                    'tanggalbooking' => $selectedDate,
                    'jam' => $selectedTime,
                    'status' => 'pending',
                    'catatan' => $validated['catatan'] ?? null,
                ]);

                // Generate unique order ID
                $orderId = $this->generateBookingOrderId();

                // Create payment
                $payment = Payment::create([
                    'idtenant' => $tenant->id,
                    'idbooking' => $newBooking->id,
                    'tipe' => 'booking',
                    'jumlah' => $totalAmount,
                    'status' => 'pending',
                    'metode' => 'midtrans',
                    'order_id' => $orderId,
                    'expired_at' => now()->addHour(),
                    'nama_pembayar' => $validated['nama'],
                    'email_pembayar' => $validated['email'],
                    'hp_pembayar' => $validated['nomorhp'],
                    'catatan' => $validated['catatan'] ?? null,
                ]);

                // Link payment to booking
                $newBooking->update(['idpayment' => $payment->id]);

                // Generate Midtrans Snap token
                $params = [
                    'transaction_details' => [
                        'order_id' => $orderId,
                        'gross_amount' => $totalAmount,
                    ],
                    'customer_details' => [
                        'first_name' => $validated['nama'],
                        'email' => $validated['email'],
                        'phone' => $validated['nomorhp'],
                    ],
                    'item_details' => [
                        [
                            'id' => 'SVC-' . $service->id,
                            'price' => $totalAmount,
                            'quantity' => 1,
                            'name' => substr($service->namalayanan, 0, 50),
                        ],
                    ],
                    'expiry' => [
                        'start_time' => now()->format('Y-m-d H:i:s O'),
                        'unit' => 'hour',
                        'duration' => 1,
                    ],
                ];

                $snapToken = Snap::getSnapToken($params);
                $payment->update(['snap_token' => $snapToken]);

                return [
                    'snap_token' => $snapToken,
                    'order_id' => $orderId,
                    'booking_id' => $newBooking->id,
                ];
            });

            // Store booking result in session for success page
            session()->put('booking.booking_id', $result['booking_id']);
            session()->put('booking.order_id', $result['order_id']);

            return response()->json([
                'snap_token' => $result['snap_token'],
                'order_id' => $result['order_id'],
            ]);
        } catch (\Exception $e) {
            if ($e->getMessage() === 'SLOT_UNAVAILABLE' || $e->getMessage() === 'SLOT_TAKEN') {
                return response()->json([
                    'error' => 'This time slot is no longer available. Please go back and choose a different time.',
                ], 409);
            }

            Log::error('Booking Payment Error: ' . $e->getMessage(), [
                'tenant_id' => $tenant->id,
                'service_id' => $service->id,
                'schedule_id' => $scheduleId,
            ]);

            return response()->json([
                'error' => 'Failed to process payment. Please try again.',
            ], 500);
        }
    }

    public function paymentSuccess(string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

=======
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);
        if (!$service) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $schedule = Schedule::where('idtenant', $tenant->id)
            ->where('idlayanan', $service->id)
            ->whereDate('tanggal', $selectedDate)
            ->where('jam_mulai', $selectedTime . ':00')
            ->first();

        $hargaAkhir = $schedule && $schedule->harga_override ? $schedule->harga_override : $service->harga;

        return view('customer.booking.checkout', compact('tenant', 'service', 'selectedDate', 'selectedTime', 'hargaAkhir', 'schedule'));
    }

    public function processCheckout(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);
>>>>>>> 6a13f203747615a9225aa2afecb9fa6d553ad20e
        if (!$tenant) {
            abort(404);
        }

        $booking = session('booking', []);
<<<<<<< HEAD
        $bookingId = $booking['booking_id'] ?? null;

        $bookingRecord = null;
        $service = null;

        if ($bookingId) {
            $bookingRecord = Booking::where('id', $bookingId)
                ->where('idtenant', $tenant->id)
                ->first();

            if ($bookingRecord) {
                $service = $this->resolveService($tenant->id, $bookingRecord->idlayanan);
            }
        }

        // Clear the booking session
        session()->forget('booking');

        return view('customer.booking.booking-success', [
            'tenant' => $tenant,
            'booking' => $bookingRecord,
            'service' => $service,
        ]);
    }

    public function paymentFailed(string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        if (!$tenant) {
            abort(404);
        }

        return view('customer.booking.booking-failed', [
            'tenant' => $tenant,
        ]);
    }

    private function generateBookingOrderId(): string
    {
        $prefix = 'BKG-' . now()->format('Ymd') . '-';
        $lastPayment = Payment::where('order_id', 'like', $prefix . '%')
            ->orderByDesc('order_id')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->order_id, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
=======
        $serviceId = $booking['service_id'] ?? null;
        $selectedDate = $booking['tanggal'] ?? null;
        $selectedTime = $booking['jam'] ?? null;

        if (!$serviceId || !$selectedDate || !$selectedTime) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);
        $schedule = Schedule::where('idtenant', $tenant->id)
            ->where('idlayanan', $service->id)
            ->whereDate('tanggal', $selectedDate)
            ->where('jam_mulai', $selectedTime . ':00')
            ->first();

        if (!$service || !$schedule) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $request->validate([
            'namapelanggan' => 'required|string|max:150',
            'nomorhp' => 'required|string|max:20',
            'email' => 'required|email|max:100',
            'catatan' => 'nullable|string|max:500',
        ]);

        $hargaAkhir = $schedule->harga_override ? $schedule->harga_override : $service->harga;

        // Jika gratis, langsung sukses
        if ($hargaAkhir <= 0) {
            Booking::create([
                'idtenant' => $tenant->id,
                'idlayanan' => $service->id,
                'idschedule' => $schedule->id,
                'namapelanggan' => $request->namapelanggan,
                'nomorhp' => $request->nomorhp,
                'email' => $request->email,
                'tanggalbooking' => $selectedDate,
                'jam' => $selectedTime . ':00',
                'status' => 'paid', // Langsung paid karena gratis
                'catatan' => $request->catatan,
            ]);

            session()->forget('booking');
            return redirect()->route('customer.booking.program', $slug_usaha)->with('success', 'Booking berhasil!');
        }

        // Midtrans Flow
        $orderId = 'BKG-' . $tenant->id . '-' . time() . '-' . rand(100, 999);

        $payment = Payment::create([
            'idtenant' => $tenant->id,
            'tipe' => 'booking',
            'jumlah' => $hargaAkhir,
            'status' => 'pending',
            'metode' => 'midtrans',
            'order_id' => $orderId,
            'expired_at' => now()->addMinutes(15),
            'nama_pembayar' => $request->namapelanggan,
            'email_pembayar' => $request->email,
            'hp_pembayar' => $request->nomorhp,
            'catatan' => $request->catatan,
        ]);

        $newBooking = Booking::create([
            'idtenant' => $tenant->id,
            'idlayanan' => $service->id,
            'idschedule' => $schedule->id,
            'namapelanggan' => $request->namapelanggan,
            'nomorhp' => $request->nomorhp,
            'email' => $request->email,
            'tanggalbooking' => $selectedDate,
            'jam' => $selectedTime . ':00',
            'status' => 'pending',
            'idpayment' => $payment->id,
            'catatan' => $request->catatan,
        ]);

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $hargaAkhir,
            ],
            'customer_details' => [
                'first_name' => $request->namapelanggan,
                'email' => $request->email,
                'phone' => $request->nomorhp,
            ],
            'item_details' => [
                [
                    'id' => 'SRV-' . $service->id,
                    'price' => (int) $hargaAkhir,
                    'quantity' => 1,
                    'name' => 'Booking: ' . $service->namalayanan,
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'minute',
                'duration' => 15,
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            $payment->update(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Error (Booking): ' . $e->getMessage());
            $payment->update(['status' => 'gagal']);
            $newBooking->update(['status' => 'cancelled']);

            return back()->with('error', 'Gagal memproses pembayaran. Error: ' . $e->getMessage());
        }

        session()->forget('booking');
        return redirect()->route('customer.booking.payment', [$slug_usaha, $payment->id]);
    }

    public function showPayment(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        if ($payment->status === 'sukses') {
            return redirect()->route('customer.booking.invoice', [$slug_usaha, $payment->id]);
        }

        if ($payment->isExpired() && $payment->status === 'pending') {
            $payment->update(['status' => 'gagal']);
            Booking::where('idpayment', $payment->id)->update(['status' => 'cancelled']);
            return redirect()->route('customer.booking.program', $slug_usaha)
                ->with('error', 'Waktu pembayaran telah habis.');
        }

        return view('customer.booking.payment', [
            'tenant' => $tenant,
            'payment' => $payment,
            'snapToken' => $payment->snap_token,
            'clientKey' => config('midtrans.client_key'),
            'snapUrl' => config('midtrans.snap_url'),
        ]);
    }

    public function checkPaymentStatus(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant || $payment->idtenant !== $tenant->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $status = MidtransTransaction::status($payment->order_id);
            $transactionStatus = $status->transaction_status ?? null;
            $fraudStatus = $status->fraud_status ?? null;
            $paymentType = $status->payment_type ?? null;

            if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
                if ($transactionStatus === 'capture' && $fraudStatus === 'challenge') {
                    return response()->json(['status' => 'pending', 'message' => 'Pembayaran direview.']);
                }

                if ($payment->status !== 'sukses') {
                    DB::transaction(function () use ($payment, $paymentType) {
                        $payment->update(['status' => 'sukses', 'metode' => $paymentType ?? 'midtrans']);
                        Booking::where('idpayment', $payment->id)->update(['status' => 'paid']);
                    });
                }

                return response()->json([
                    'status' => 'sukses',
                    'message' => 'Pembayaran berhasil!',
                    'redirect' => route('customer.booking.invoice', [$slug_usaha, $payment->id]),
                ]);
            }

            if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                if ($payment->status !== 'gagal') {
                    DB::transaction(function () use ($payment, $paymentType) {
                        $payment->update(['status' => 'gagal', 'metode' => $paymentType ?? 'midtrans']);
                        Booking::where('idpayment', $payment->id)->update(['status' => 'cancelled']);
                    });
                }
                return response()->json(['status' => 'gagal', 'message' => 'Pembayaran gagal/dibatalkan.']);
            }

            return response()->json(['status' => 'pending', 'message' => 'Pembayaran belum diselesaikan (status: pending). Silakan selesaikan pembayaran sesuai instruksi.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal periksa status.']);
        }
    }

    public function handleCallback(string $slug_usaha, Payment $payment, Request $request)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant || $payment->idtenant !== $tenant->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $result = $request->input('result');
        if (!$result) return response()->json(['error' => 'No result'], 400);

        $transactionStatus = $result['transaction_status'] ?? null;
        if (in_array($transactionStatus, ['capture', 'settlement'])) {
            return response()->json(['status' => 'sukses', 'redirect' => route('customer.booking.invoice', [$slug_usaha, $payment->id])]);
        }
        if (in_array($transactionStatus, ['pending'])) {
            return response()->json(['status' => 'pending']);
        }
        return response()->json(['status' => 'gagal']);
    }

    public function showInvoice(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        $booking = Booking::with('layanan')->where('idpayment', $payment->id)->first();
        if (!$booking) abort(404);

        return view('customer.booking.invoice', compact('tenant', 'payment', 'booking'));
>>>>>>> 6a13f203747615a9225aa2afecb9fa6d553ad20e
    }

    private function resolveTenant(string $slug_usaha): ?Tenant
    {
        $tenantData = Cache::remember("tenant:slug:{$slug_usaha}", now()->addSeconds(3600), function () use ($slug_usaha) {
            return Tenant::query()->where('slug', $slug_usaha)->first()?->getAttributes();
        });

        if (!$tenantData) {
            return null;
        }

        return Tenant::hydrate([$tenantData])->first();
    }

    private function resolveService(int $tenantId, int $serviceId): ?Service
    {
        $serviceData = Cache::remember("tenant:{$tenantId}:service:{$serviceId}", now()->addSeconds(3600), function () use ($tenantId, $serviceId) {
            return Service::query()
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

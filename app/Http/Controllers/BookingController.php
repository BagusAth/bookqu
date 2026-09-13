<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Services\MidtransPaymentService;
use App\Traits\ClearsBookingCache;
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

        if (!$service || !$service->hasActiveFulfillment()) {
            return redirect()->route('customer.booking.program', $slug_usaha)
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

        if (!$service || !$service->hasActiveFulfillment()) {
            session()->forget('booking');
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        session()->put('booking.tenant_id', $tenant->id);

        $minDate = Carbon::today();
        $maxDate = Carbon::today()->addDays(30);

        $availabilityKey = $this->getAvailabilityCacheKey($tenant->id, $service->id);

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

        if (!$service || !$service->hasActiveFulfillment()) {
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

        if (!$service || !$service->hasActiveFulfillment()) {
            session()->forget('booking');
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        session()->put('booking.tenant_id', $tenant->id);

        $selectedDate = Carbon::parse($selectedDate)->toDateString();
        $scheduleCacheKey = $this->getSchedulesCacheKey($tenant->id, $service->id, $selectedDate);

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

        if (!$service || !$service->hasActiveFulfillment()) {
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
        session()->put('booking.schedule_id', $schedule->id);

        return redirect()->route('customer.booking.checkout', $slug_usaha);
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
        $selectedTime = $booking['jam'] ?? null;
        $scheduleId = $booking['schedule_id'] ?? null;

        if (!$serviceId || !$selectedDate || !$selectedTime) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);
        if (!$service || !$service->hasActiveFulfillment()) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $schedule = null;
        if ($scheduleId) {
            $schedule = Schedule::where('idtenant', $tenant->id)
                ->where('idlayanan', $service->id)
                ->find($scheduleId);
        }
        if (!$schedule) {
            $schedule = Schedule::where('idtenant', $tenant->id)
                ->where('idlayanan', $service->id)
                ->whereDate('tanggal', $selectedDate)
                ->where('jam_mulai', $selectedTime . ':00')
                ->first();
        }

        $hargaAkhir = $schedule && $schedule->harga_override ? $schedule->harga_override : $service->harga;

        $service->load([
            'additionalItems' => fn($q) => $q->where('is_active', true),
            'staff'           => fn($q) => $q->where('is_active', true),
        ]);
        $availableAddons = $service->additionalItems;
        $availableStaff  = $service->staff;

        return view('customer.booking.checkout', compact(
            'tenant',
            'service',
            'selectedDate',
            'selectedTime',
            'hargaAkhir',
            'schedule',
            'availableAddons',
            'availableStaff'
        ));
    }

    public function processCheckout(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant) {
            abort(404);
        }

        $booking = session('booking', []);
        $serviceId = $booking['service_id'] ?? null;
        $selectedDate = $booking['tanggal'] ?? null;
        $selectedTime = $booking['jam'] ?? null;
        $scheduleId = $booking['schedule_id'] ?? null;

        if (!$serviceId || !$selectedDate || !$selectedTime) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (!$service || !$service->hasActiveFulfillment()) {
            return redirect()->route('customer.booking.program', $slug_usaha);
        }

        $request->validate([
            'namapelanggan'     => 'required|string|max:150',
            'nomorhp'           => 'required|string|max:20',
            'email'             => 'required|email|max:100',
            'catatan'           => 'nullable|string|max:500',
            'selected_addons'   => 'nullable|array',
            'selected_addons.*' => 'integer',
            'staff_id'          => 'nullable|integer',
        ]);

        // Wrap slot availability check + booking creation in a transaction to prevent double-booking
        $result = DB::transaction(function () use ($tenant, $service, $selectedDate, $selectedTime, $scheduleId, $request) {
            // Lock the schedule row so concurrent requests cannot claim the same slot simultaneously
            $scheduleQuery = DB::table('schedules')
                ->where('idtenant', $tenant->id)
                ->where('idlayanan', $service->id)
                ->where('status', 'tersedia');

            if ($scheduleId) {
                $scheduleQuery->where('id', $scheduleId);
            } else {
                $scheduleQuery->whereDate('tanggal', $selectedDate)
                    ->where('jam_mulai', $selectedTime . ':00');
            }

            $schedule = $scheduleQuery->lockForUpdate()->first();

            if (!$schedule) {
                return ['error' => 'Jadwal tidak ditemukan atau tidak tersedia.'];
            }

            // Check if slot already has an active booking (pending/paid/completed)
            $isBooked = DB::table('bookings')
                ->where('idschedule', $schedule->id)
                ->whereIn('status', ['pending', 'paid', 'completed'])
                ->exists();

            if ($isBooked) {
                return ['error' => 'Slot waktu ini sudah dibooking oleh pelanggan lain. Silakan pilih waktu lain.'];
            }

            // Add-ons calculation
            $addonTotal = 0;
            $addonNames = [];
            if (!empty($request->selected_addons)) {
                $addons = \App\Models\AdditionalItem::where('idtenant', $tenant->id)
                    ->where('is_active', true)
                    ->whereIn('id', $request->selected_addons)
                    ->whereHas('services', fn($q) => $q->where('services.id', $service->id))
                    ->lockForUpdate()
                    ->get();

                foreach ($addons as $addon) {
                    $addonTotal += (float) $addon->price;
                    $addonNames[] = $addon->name . ' (+Rp ' . number_format($addon->price, 0, ',', '.') . ')';
                    if ($addon->stock !== null && $addon->stock > 0) {
                        $addon->decrement('stock');
                    }
                }
            }

            // Staff preference
            $staffPref = null;
            if ($request->filled('staff_id')) {
                $chosenStaff = \App\Models\Staff::where('idtenant', $tenant->id)
                    ->where('is_active', true)
                    ->where('id', $request->staff_id)
                    ->first();
                if ($chosenStaff) {
                    $staffPref = 'Staf: ' . $chosenStaff->name . ' (' . ($chosenStaff->role ?? 'Staf') . ')';
                }
            }

            $hargaAkhir = ($schedule->harga_override ? $schedule->harga_override : $service->harga) + $addonTotal;

            $fullCatatan = trim($request->catatan ?? '');
            if (!empty($addonNames)) {
                $fullCatatan .= ($fullCatatan ? ' | ' : '') . 'Add-ons: ' . implode(', ', $addonNames);
            }
            if ($staffPref) {
                $fullCatatan .= ($fullCatatan ? ' | ' : '') . $staffPref;
            }

            // Free booking path
            if ($hargaAkhir <= 0) {
                $booking = Booking::create([
                    'idtenant'       => $tenant->id,
                    'idlayanan'      => $service->id,
                    'idschedule'     => $schedule->id,
                    'namapelanggan'  => $request->namapelanggan,
                    'nomorhp'        => $request->nomorhp,
                    'email'          => $request->email,
                    'tanggalbooking' => $selectedDate,
                    'jam'            => $selectedTime . ':00',
                    'status'         => 'paid', // Langsung paid karena gratis
                    'catatan'        => $fullCatatan ?: null,
                ]);

                return ['free' => true, 'booking' => $booking, 'schedule' => $schedule, 'hargaAkhir' => 0];
            }

            // Paid booking path: create payment first, then booking
            $orderId  = 'BKG-' . $tenant->id . '-' . time() . '-' . rand(100, 999);

            $payment = Payment::create([
                'idtenant'       => $tenant->id,
                'tipe'           => 'booking',
                'jumlah'         => $hargaAkhir,
                'status'         => 'pending',
                'metode'         => 'midtrans',
                'order_id'       => $orderId,
                'expired_at'     => now()->addMinutes(15),
                'nama_pembayar'  => $request->namapelanggan,
                'email_pembayar' => $request->email,
                'hp_pembayar'    => $request->nomorhp,
                'catatan'        => $fullCatatan ?: null,
            ]);

            $newBooking = Booking::create([
                'idtenant'       => $tenant->id,
                'idlayanan'      => $service->id,
                'idschedule'     => $schedule->id,
                'namapelanggan'  => $request->namapelanggan,
                'nomorhp'        => $request->nomorhp,
                'email'          => $request->email,
                'tanggalbooking' => $selectedDate,
                'jam'            => $selectedTime . ':00',
                'status'         => 'pending',
                'idpayment'      => $payment->id,
                'catatan'        => $fullCatatan ?: null,
            ]);

            return [
                'free'       => false,
                'booking'    => $newBooking,
                'payment'    => $payment,
                'schedule'   => $schedule,
                'orderId'    => $orderId,
                'hargaAkhir' => $hargaAkhir,
            ];
        });

        // Handle slot conflict or schedule not found
        if (isset($result['error'])) {
            return redirect()->route('customer.booking.time', $slug_usaha)
                ->withErrors(['jam' => $result['error']]);
        }

        // Free booking: assign tokens, send email, clear cache and redirect
        if ($result['free']) {
            $freeBooking = $result['booking'];
            $freeBooking->assignManagementTokens();
            $freeBooking->load(['tenant', 'layanan', 'payment']);

            if ($freeBooking->email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($freeBooking->email)
                        ->send(new \App\Mail\BookingInvoiceMail($freeBooking));
                } catch (\Exception $e) {
                    Log::error('Gagal kirim email invoice free booking: ' . $e->getMessage());
                }
            }

            $this->clearBookingAvailabilityCache(
                $tenant->id,
                $service->id,
                Carbon::parse($selectedDate)->toDateString()
            );
            session()->forget('booking');
            return redirect()->route('customer.booking.program', $slug_usaha)->with('success', 'Booking berhasil!');
        }

        // Paid booking: get Midtrans snap token
        $payment    = $result['payment'];
        $newBooking = $result['booking'];
        $hargaAkhir = $result['hargaAkhir'];
        $orderId    = $result['orderId'];

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
                    'name'     => 'Booking: ' . $service->namalayanan,
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit'       => 'minute',
                'duration'   => 15,
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            $payment->update(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Error (Booking): ' . $e->getMessage());
            // Snap token failed — cancel both payment and booking, release the slot
            $payment->update(['status' => 'gagal']);
            $newBooking->update(['status' => 'cancelled']);

            // Invalidate cache so slot is free again
            $this->clearBookingAvailabilityCache(
                $tenant->id,
                $service->id,
                Carbon::parse($selectedDate)->toDateString()
            );

            return back()->with('error', 'Gagal memproses pembayaran. Error: ' . $e->getMessage());
        }

        session()->forget('booking');
        return redirect()->route('customer.booking.payment', [$slug_usaha, $payment]);
    }

    public function showPayment(string $slug_usaha, Payment $payment)
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (!$tenant || $payment->idtenant !== $tenant->id) {
            abort(404);
        }

        if ($payment->status === 'sukses') {
            return redirect()->route('customer.booking.invoice', [$slug_usaha, $payment]);
        }

        if ($payment->isExpired() && $payment->status === 'pending') {
            // Load booking before cancelling so we can get idlayanan + tanggalbooking for cache invalidation
            $expiredBooking = Booking::where('idpayment', $payment->id)->first();

            $payment->update(['status' => 'gagal']);
            Booking::where('idpayment', $payment->id)->update(['status' => 'cancelled']);

            // Invalidate availability cache so slot is immediately available to other customers
            if ($expiredBooking && $expiredBooking->idlayanan && $expiredBooking->tanggalbooking) {
                $tanggal = $expiredBooking->tanggalbooking instanceof \Carbon\Carbon
                    ? $expiredBooking->tanggalbooking->toDateString()
                    : Carbon::parse($expiredBooking->tanggalbooking)->toDateString();

                $this->clearBookingAvailabilityCache(
                    (int) $expiredBooking->idtenant,
                    (int) $expiredBooking->idlayanan,
                    $tanggal
                );
            }

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
                'redirect' => route('customer.booking.invoice', [$slug_usaha, $payment]),
            ]);
        }

        $syncResult = $paymentService->verifyAndSync($payment);

        if ($syncResult['status'] === 'sukses') {
            return response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => route('customer.booking.invoice', [$slug_usaha, $payment]),
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
                    'redirect' => route('customer.booking.invoice', [$slug_usaha, $payment]),
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
                'redirect' => route('customer.booking.invoice', [$slug_usaha, $payment]),
            ]);
        }

        // Lakukan server-side verification ke Midtrans
        $syncResult = $paymentService->verifyAndSync($payment);

        // Fallback jika API call gagal, periksa payload client dari Midtrans Snap
        if ($syncResult['status'] === 'error') {
            $result = $request->input('result');
            if ($result) {
                $syncResult = $paymentService->syncStatus($payment, $result);
            }
        }

        if ($syncResult['status'] === 'sukses') {
            return response()->json([
                'status' => 'sukses',
                'message' => 'Pembayaran berhasil dikonfirmasi!',
                'redirect' => route('customer.booking.invoice', [$slug_usaha, $payment]),
            ]);
        }

        if ($syncResult['status'] === 'pending') {
            return response()->json([
                'status' => 'pending',
                'message' => $syncResult['message'] ?? 'Pembayaran sedang diproses.',
            ]);
        }

        return response()->json([
            'status' => 'gagal',
            'message' => $syncResult['message'] ?? 'Pembayaran gagal.',
        ]);
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
        $serviceData = Cache::remember($this->getServiceCacheKey($tenantId, $serviceId), now()->addSeconds(3600), function () use ($tenantId, $serviceId) {
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

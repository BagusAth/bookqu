<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
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
        return response('Checkout is not implemented yet.', 200);
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

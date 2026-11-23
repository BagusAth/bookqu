<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Actions\Booking\GetBookingAddOnOptions;
use App\Actions\Schedule\PriceSlots;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\Booking\BookingTimeRange;
use App\Domain\Modules\ModuleRelease;
use App\Domain\Schedule\AvailabilityRules;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Team\TenantPermission;
use App\Models\Booking;
use App\Models\OwnerBlockedDate;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\Unit;
use App\Support\Rupiah;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final class GetOwnerCalendarData
{
    public function __construct(private ArrangeDayGrid $arrangeDayGrid) {}

    /** @return array<string, mixed> */
    public function execute(Tenant $tenant, Request $request): array
    {
        $views = ['day' => 'Hari', 'week' => 'Minggu', 'month' => 'Bulan'];
        $view = $request->string('view')->toString();
        $view = isset($views[$view]) ? $view : 'day';
        $now = CarbonImmutable::now('Asia/Jakarta');
        $rawDate = $request->string('date')->toString();
        $validDate = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $rawDate, $parts) === 1
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
        $date = $validDate ? CarbonImmutable::parse($rawDate, 'Asia/Jakarta') : $now->startOfDay();
        $date = $date->locale('id');

        [$start, $end, $previous, $next, $dateLabel] = match ($view) {
            'month' => [$date->startOfMonth(), $date->endOfMonth(), $date->startOfMonth()->subMonth(), $date->startOfMonth()->addMonth(), $date->translatedFormat('F Y')],
            'week' => [$date->startOfWeek(), $date->endOfWeek(), $date->startOfWeek()->subWeek(), $date->startOfWeek()->addWeek(), ($date->startOfWeek()->month === $date->endOfWeek()->month ? $date->startOfWeek()->format('j') : $date->startOfWeek()->translatedFormat('j M')).'–'.$date->endOfWeek()->translatedFormat('j M Y')],
            default => [$date, $date, $date->subDay(), $date->addDay(), $date->translatedFormat('l, j M Y')],
        };
        $bounds = [$start->toDateString(), $end->toDateString()];
        $queryBounds = [$bounds[0], $end->endOfDay()->toDateTimeString()];
        $today = $now->toDateString();
        $subscription = $tenant->subscription()->with('plan')->first();
        $member = $request->attributes->get('tenant_member');
        $can = fn (string $ability): bool => $member instanceof TenantMember && TenantPermission::allowsRole($member->role, $ability);
        $canEdit = $can('schedule.manage');
        $canWalkIn = $can('bookings.walkin') && EntitlementRules::hasEffectivePlan($subscription);

        $services = Service::where('idtenant', $tenant->id)->orderBy('namalayanan')->get();
        $canViewUnits = ModuleRelease::state('units') === 'released' && EntitlementRules::canAccessFeature($subscription, 'units')['allowed'];
        $units = $canViewUnits
            ? Unit::where('idtenant', $tenant->id)->orderBy('name')->get(['id', 'name', 'is_active']) : collect();
        $unitMode = $view === 'day' && $canViewUnits && $request->query('kolom') === 'unit';
        if ($unitMode) {
            $services->load(['units' => fn ($query) => $query->where('units.idtenant', $tenant->id)->where('units.is_active', true)]);
        }
        $serviceId = $services->firstWhere('id', $request->integer('service_id'))?->id;
        $unitId = $units->firstWhere('id', $request->integer('unit_id'))?->id;

        $statuses = ['available' => 'Tersedia', 'blocked' => 'Ditutup'];
        $bookingStatuses = [
            BookingState::STATUS_PAID, BookingState::STATUS_PENDING, BookingState::STATUS_COMPLETED,
            BookingState::STATUS_NO_SHOW, BookingState::STATUS_CANCELLED,
        ];
        foreach ($bookingStatuses as $status) {
            $statuses[$status] = BookingState::formatLabel($status);
        }
        $status = $request->string('status')->toString();
        $status = isset($statuses[$status]) ? $status : '';
        $filters = array_filter(['service_id' => $serviceId, 'unit_id' => $unitId, 'status' => $status, 'kolom' => $unitMode ? 'unit' : null]);

        $closedDates = OwnerBlockedDate::where('idtenant', $tenant->id)->whereBetween('tanggal', $queryBounds)->pluck('alasan', 'tanggal');
        [$taken, $bindings] = BookingRules::seatsTakenSql();
        $schedules = Schedule::where('idtenant', $tenant->id)
            ->whereBetween('tanggal', $queryBounds)
            ->when($serviceId !== null, fn ($query) => $query->where('idlayanan', $serviceId))
            ->when($unitId !== null, fn ($query) => $query->whereHas('layanan.units', fn ($unitsQuery) => $unitsQuery->whereKey($unitId)))
            ->with('layanan')->select('schedules.*')->selectRaw($taken.' AS seats_taken', $bindings)
            ->orderBy('tanggal')->orderBy('jam_mulai')->get();
        $bookings = Booking::where('idtenant', $tenant->id)->whereBetween('tanggalbooking', $queryBounds)
            ->when($serviceId !== null, fn ($query) => $query->where('idlayanan', $serviceId))
            ->when($unitId !== null, fn ($query) => $query->whereHas('layanan.units', fn ($unitsQuery) => $unitsQuery->whereKey($unitId)))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->with('layanan')->orderBy('jam')->get();

        $addOns = $canWalkIn ? app(GetBookingAddOnOptions::class)->execute($tenant, $schedules->pluck('idlayanan')->unique()->values()->all()) : [];
        $prices = app(PriceSlots::class)->execute($tenant, $schedules);
        $bySlot = $bookings->whereNotNull('idschedule')->groupBy('idschedule');
        $busyUnits = [];
        $eligible = [];
        $days = [];
        for ($day = $start->startOfDay(); $day->lte($end); $day = $day->addDay()) {
            $key = $day->toDateString();
            $days[$key] = ['label' => $day->translatedFormat('l, d M'), 'number' => $day->day, 'slots' => [], 'bookings' => [], 'weekBookings' => [], 'occupied' => 0, 'occupancy' => 0,
                'closed' => $closedDates->has($key) ? ($closedDates->get($key) ?: 'Tutup') : null];
        }

        foreach ($schedules as $slot) {
            $key = CarbonImmutable::parse($slot->tanggal)->toDateString();
            $service = $slot->layanan;
            $range = BookingTimeRange::forSlot($key, (string) $slot->jam_mulai, (string) $slot->jam_selesai);
            $seats = (int) $slot->getAttribute('seats_taken');
            $capacity = $service instanceof Service ? max(1, (int) $service->kapasitas) : 1;
            $serviceName = $service instanceof Service ? $service->namalayanan : 'Layanan';
            $past = AvailabilityRules::isSlotPast($key, (string) $slot->jam_mulai);
            $closed = $slot->status !== 'tersedia' || $closedDates->has($key);
            $available = false;
            if (! $closed && ! $past && $seats < $capacity && $service instanceof Service) {
                $eligible[$service->id] ??= $service->is_active && $service->hasActiveFulfillment();
                $busyUnits[$service->id] ??= BookingRules::unitBusyRanges((int) $tenant->id, (int) $service->id, ...$bounds);
                $available = $eligible[$service->id] && ! BookingRules::slotHitsBusyUnit(
                    $busyUnits[$service->id], (int) $slot->id, $key, (string) $slot->jam_mulai, (string) $slot->jam_selesai,
                );
            }
            $slotBookings = $bySlot->get($slot->id, collect());
            if (($status === 'available' && ! $available) || ($status === 'blocked' && ! $closed)
                || (in_array($status, $bookingStatuses, true) && $slotBookings->isEmpty())) {
                continue;
            }

            $days[$key]['occupied'] += (int) ($seats > 0);
            $days[$key]['slots'][] = [
                'id' => $slot->id,
                'date' => $key,
                'service' => $serviceName,
                'service_id' => $slot->idlayanan,
                'time' => $range->label(),
                'start' => $range->startTime(),
                'startMinute' => $range->start->hour * 60 + $range->start->minute,
                'endMinute' => $range->start->hour * 60 + $range->start->minute + ($range->durationMinutes() ?? 0),
                'state' => match (true) {
                    $closed => 'Ditutup',
                    $seats > 0 => 'Terisi',
                    $past => 'Terlewat',
                    $available => 'Tersedia',
                    default => 'Tidak tersedia',
                },
                'price' => isset($prices[$slot->id]) ? Rupiah::format($prices[$slot->id]) : null,
                'capacity' => $capacity > 1 ? $seats.'/'.$capacity : null,
                'seatsLeft' => max(0, $capacity - $seats),
                'bookings' => $slotBookings->map(fn (Booking $booking): array => $this->bookingCard($booking))->values()->all(),
                'walkIn' => $available && $canWalkIn,
                'addOns' => $addOns[$slot->idlayanan] ?? [],
                'walkInLabel' => $serviceName.' · '.$range->label().' · '.$key,
                'edit' => $canEdit && $seats === 0 && ! $past && ! $closedDates->has($key),
                'closed' => $closed,
                'override' => $slot->harga_override,
                'updateUrl' => route('owner.schedule.slots.update', $slot),
            ];
        }

        $shownSlotIds = array_fill_keys($schedules->modelKeys(), true);
        foreach ($bookings as $booking) {
            $key = CarbonImmutable::parse($booking->tanggalbooking)->toDateString();
            if (! isset($days[$key])) {
                continue;
            }
            $days[$key]['bookingCount'] = ($days[$key]['bookingCount'] ?? 0) + 1;
            $card = $this->bookingCard($booking);
            $days[$key]['weekBookings'][] = $card;
            if (! isset($shownSlotIds[$booking->idschedule])) {
                $days[$key]['bookings'][] = $card;
            }
        }

        foreach ($days as &$day) {
            if ($day['slots'] !== []) {
                $day['occupancy'] = (int) round(100 * $day['occupied'] / count($day['slots']));
            }
        }
        unset($day);

        $todaySlots = $days[$bounds[0]]['slots'];
        $todayTime = $now->format('H:i');
        $columnOptions = ($serviceId === null ? $services : $services->where('id', $serviceId))
            ->map(fn (Service $service): array => ['id' => (int) $service->id, 'name' => $service->namalayanan])->values()->all();
        if ($unitMode) {
            $columnOptions = $units->where('is_active', true)
                ->when($unitId !== null, fn ($options) => $options->where('id', $unitId))
                ->map(fn (Unit $unit): array => ['id' => $unit->id, 'name' => $unit->name])->values()->all();
            $unitsByService = $services->mapWithKeys(fn (Service $service): array => [$service->id => $service->units->modelKeys()]);
            $todaySlots = collect($todaySlots)->flatMap(static fn (array $slot): array => array_map(
                static fn (int $id): array => $slot + ['column_id' => $id],
                $unitsByService->get($slot['service_id'], []),
            ))->values()->all();
        }
        $chosenColumns = array_map('intval', array_filter($request->array('columns'), fn ($value): bool => is_scalar($value)));
        $dayGrid = $view === 'day' ? $this->arrangeDayGrid->execute($columnOptions, $todaySlots, $chosenColumns, $bounds[0] === $today ? $todayTime : null) : null;
        $hasEntries = array_any($days, fn (array $day): bool => $day['slots'] !== [] || $day['bookings'] !== [] || $day['closed'] !== null);
        $monthOffset = $start->dayOfWeekIso - 1;
        $scheduleUrl = $canEdit ? route('owner.schedule') : null;

        return compact('views', 'view', 'date', 'dateLabel', 'previous', 'next', 'today', 'todayTime', 'monthOffset',
            'filters', 'services', 'units', 'statuses', 'unitMode', 'canViewUnits', 'days', 'dayGrid', 'hasEntries', 'scheduleUrl');
    }

    /** @return array{url: string, customer: string, service: string, status: string, time: string} */
    private function bookingCard(Booking $booking): array
    {
        $service = $booking->layanan;

        return [
            'url' => route('owner.bookings.panel', $booking->id),
            'customer' => $booking->namapelanggan,
            'service' => $service instanceof Service ? $service->namalayanan : 'Layanan',
            'status' => $booking->status,
            'time' => substr((string) $booking->jam, 0, 5),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Actions\Booking\GetBookingAddOnOptions;
use App\Actions\Schedule\GetAvailableSchedules;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingTimeRange;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;

/**
 * Free slots of every active service on one date, for the "+ Booking" walk-in form (BQ-128,
 * owner-portal.md §4.2). Availability comes from the single availability rule (INV-SCH-01).
 */
final class ListWalkInSlots
{
    public function __construct(private GetAvailableSchedules $availability) {}

    /**
     * @return list<array{id: int, label: string, add_ons?: list<array{id: int, name: string}>}>
     */
    public function execute(Tenant $tenant, string $date): array
    {
        return collect($this->picker($tenant, $date)['slots'])
            ->where('available', true)
            ->map(fn (array $slot): array => ['id' => $slot['id'], 'label' => $slot['label']]
                + ($slot['add_ons'] === [] ? [] : ['add_ons' => $slot['add_ons']]))
            ->values()->all();
    }

    /** @return list<array{id: int, start: string}> */
    public function alternatives(Tenant $tenant, int $id, int $participants): array
    {
        $slot = Schedule::where('idtenant', $tenant->id)->find($id);
        if (! $slot) {
            return [];
        }

        $date = Carbon::parse($slot->tanggal)->toDateString();
        $start = substr((string) $slot->jam_mulai, 0, 5);
        $minutes = static fn (string $time): int => (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);

        return collect($this->picker($tenant, $date, (int) $slot->idlayanan)['slots'])
            ->filter(fn (array $option): bool => $option['available'] && $option['id'] !== $id && $option['seats_left'] >= $participants)
            ->sortBy(fn (array $option): int => abs($minutes($option['start']) - $minutes($start)))
            ->take(2)->map(fn (array $option): array => ['id' => $option['id'], 'start' => $option['start']])
            ->values()->all();
    }

    /**
     * @return array{
     *     services: list<array{id: int, name: string, capacity: int}>,
     *     slots: list<array{id: int, service: int, label: string, start: string, available: bool, seats_left: int, add_ons: list<array{id: int, name: string}>}>
     * }
     */
    public function picker(Tenant $tenant, string $date, ?int $serviceId = null): array
    {
        // scope-bypass: filtered by the server-resolved tenant of the signed-in member
        $services = Service::withoutGlobalScopes()->where('idtenant', $tenant->id)->where('is_active', true)
            ->when($serviceId !== null, fn ($query) => $query->whereKey($serviceId))
            ->orderBy('namalayanan')->get(['id', 'namalayanan', 'kapasitas']);

        $addOns = app(GetBookingAddOnOptions::class)->execute($tenant, $services->modelKeys());
        $slots = [];
        foreach ($services as $service) {
            foreach ($this->availability->getSlotsForReschedule($tenant, (int) $service->id, $date) as $slot) {
                $range = BookingTimeRange::forSlot($date, (string) $slot['schedule']->jam_mulai, (string) $slot['schedule']->jam_selesai);
                $slots[] = [
                    'id' => (int) $slot['id'],
                    'service' => (int) $service->id,
                    'label' => $service->namalayanan.' · '.$range->label(),
                    'start' => $range->startTime(),
                    'available' => $slot['is_available'],
                    'seats_left' => max(0, (int) $service->kapasitas - BookingRules::seatsTaken((int) $slot['id'])),
                    'add_ons' => $addOns[$service->id] ?? [],
                ];
            }
        }

        usort($slots, fn (array $a, array $b): int => [$a['start'], $a['label']] <=> [$b['start'], $b['label']]);

        return [
            'services' => $services->map(fn (Service $service): array => ['id' => (int) $service->id, 'name' => $service->namalayanan, 'capacity' => max(1, (int) $service->kapasitas)])->values()->all(),
            'slots' => $slots,
        ];
    }
}

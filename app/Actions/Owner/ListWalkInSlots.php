<?php

declare(strict_types=1);

namespace App\Actions\Owner;

use App\Actions\Schedule\GetAvailableSchedules;
use App\Domain\Booking\BookingTimeRange;
use App\Models\Service;
use App\Models\Tenant;

/**
 * Free slots of every active service on one date, for the "+ Booking" walk-in form (BQ-128,
 * owner-portal.md §4.2). Availability comes from the single availability rule (INV-SCH-01).
 */
final class ListWalkInSlots
{
    public function __construct(private GetAvailableSchedules $availability) {}

    /**
     * @return list<array{id: int, label: string}>
     */
    public function execute(Tenant $tenant, string $date): array
    {
        // scope-bypass: filtered by the server-resolved tenant of the signed-in member
        $services = Service::withoutGlobalScopes()->where('idtenant', $tenant->id)->where('is_active', true)->orderBy('namalayanan')->get(['id', 'namalayanan']);

        $slots = [];
        foreach ($services as $service) {
            foreach ($this->availability->getSlotsForReschedule($tenant, (int) $service->id, $date) as $slot) {
                if (! $slot['is_available']) {
                    continue;
                }
                $range = BookingTimeRange::forSlot($date, (string) $slot['schedule']->jam_mulai, (string) $slot['schedule']->jam_selesai);
                $slots[] = [
                    'id' => (int) $slot['id'],
                    'label' => $service->namalayanan.' · '.$range->label(),
                    'start' => $range->startTime(),
                ];
            }
        }

        usort($slots, fn (array $a, array $b): int => [$a['start'], $a['label']] <=> [$b['start'], $b['label']]);

        return array_map(fn (array $slot): array => ['id' => $slot['id'], 'label' => $slot['label']], $slots);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Schedule\ScheduleConflictRules;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use Carbon\Carbon;

class DeleteSchedule
{
    public function __construct(
        protected ScheduleAvailabilityCache $availabilityCache
    ) {}

    /**
     * Delete a schedule slot for a tenant.
     *
     * @param Tenant $tenant
     * @param Schedule|int|string $schedule
     * @return array{success: bool, error?: string, code?: int}
     */
    public function execute(Tenant $tenant, Schedule|int|string $schedule): array
    {
        $slot = $schedule instanceof Schedule
            ? $schedule
            : Schedule::withoutGlobalScopes()->where('idtenant', $tenant->id)->findOrFail((int) $schedule);

        if ((int) $slot->idtenant !== (int) $tenant->id) {
            return [
                'success' => false,
                'error' => 'Slot tidak ditemukan untuk tenant ini.',
                'code' => 404,
            ];
        }

        if (!ScheduleConflictRules::canDeleteSchedule($slot)) {
            return [
                'success' => false,
                'error' => 'Slot memiliki booking aktif.',
                'code' => 403,
            ];
        }

        $idlayanan = (int) $slot->idlayanan;
        $tanggal = $slot->tanggal instanceof Carbon
            ? $slot->tanggal->format('Y-m-d')
            : Carbon::parse($slot->tanggal)->format('Y-m-d');

        $slot->delete();

        $this->availabilityCache->clearScheduleCache((int) $tenant->id, $idlayanan, [$tanggal]);

        return [
            'success' => true,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Schedule\ScheduleConflictRules;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteSchedule
{
    public function __construct(
        protected ScheduleAvailabilityCache $availabilityCache
    ) {}

    /**
     * Delete a schedule slot for a tenant.
     *
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

        $idlayanan = (int) $slot->idlayanan;
        $tanggal = $slot->tanggal instanceof Carbon
            ? $slot->tanggal->format('Y-m-d')
            : Carbon::parse($slot->tanggal)->format('Y-m-d');

        // Check and delete under the tenant lock so a concurrent claim cannot slip in between
        $deleted = DB::transaction(function () use ($tenant, $slot) {
            ClaimSlots::lockTenant((int) $tenant->id);
            if (! ScheduleConflictRules::canDeleteSchedule($slot)) {
                return false;
            }
            $slot->delete();

            return true;
        });

        if (! $deleted) {
            return [
                'success' => false,
                'error' => 'Slot memiliki booking aktif.',
                'code' => 403,
            ];
        }

        $this->availabilityCache->clearScheduleCache((int) $tenant->id, $idlayanan, [$tanggal]);

        return [
            'success' => true,
        ];
    }
}

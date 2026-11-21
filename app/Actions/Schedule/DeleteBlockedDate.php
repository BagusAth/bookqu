<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Models\OwnerBlockedDate;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class DeleteBlockedDate
{
    public function __construct(
        protected ScheduleAvailabilityCache $availabilityCache
    ) {}

    /**
     * Delete an owner blocked date.
     *
     * @return array{success: bool, error?: string, code?: int}
     */
    public function execute(Tenant $tenant, OwnerBlockedDate|int|string $blockedDate): array
    {
        $target = $blockedDate instanceof OwnerBlockedDate
            ? $blockedDate
            : app(TenantContext::class)->run((int) $tenant->id, fn (): OwnerBlockedDate => OwnerBlockedDate::findOrFail((int) $blockedDate));

        if ((int) $target->idtenant !== (int) $tenant->id) {
            return [
                'success' => false,
                'error' => 'Tanggal blokir tidak ditemukan untuk tenant ini.',
                'code' => 404,
            ];
        }

        DB::transaction(function () use ($tenant, $target) {
            ClaimSlots::lockTenant((int) $tenant->id);
            $target->delete();
        });

        $this->availabilityCache->clearAllServicesAvailability((int) $tenant->id);

        return [
            'success' => true,
        ];
    }
}

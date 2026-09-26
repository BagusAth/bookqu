<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Models\OwnerBlockedDate;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use Illuminate\Support\Facades\Cache;

class UpdateScheduleAvailability
{
    public function __construct(
        protected ScheduleAvailabilityCache $availabilityCache
    ) {}

    /**
     * Update tenant weekend pricing and optionally add a blocked date.
     *
     * @param Tenant $tenant
     * @param array{
     *     tanggal_block?: string|null,
     *     alasan?: string|null,
     *     weekend_price_type: string,
     *     weekend_price_value?: float|numeric|null
     * } $data
     * @return array{success: bool, blocked_date?: OwnerBlockedDate|null}
     */
    public function execute(Tenant $tenant, array $data): array
    {
        $blockedDateRecord = null;
        if (!empty($data['tanggal_block'])) {
            $blockedDateRecord = OwnerBlockedDate::withoutGlobalScopes()->updateOrCreate(
                ['idtenant' => $tenant->id, 'tanggal' => $data['tanggal_block']],
                ['alasan' => $data['alasan'] ?? null]
            );
        }

        $tenant->weekend_price_type = $data['weekend_price_type'];
        $tenant->weekend_price_value = $data['weekend_price_type'] === 'none'
            ? null
            : (isset($data['weekend_price_value']) && $data['weekend_price_value'] !== '' ? (float) $data['weekend_price_value'] : null);
        $tenant->save();

        Cache::forget("tenant:slug:{$tenant->slug}");
        $this->availabilityCache->clearAllServicesAvailability((int) $tenant->id);

        return [
            'success' => true,
            'blocked_date' => $blockedDateRecord,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Schedule\ScheduleConflictRules;
use App\Models\Booking;
use App\Models\OwnerBlockedDate;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateScheduleAvailability
{
    public function __construct(
        protected ScheduleAvailabilityCache $availabilityCache
    ) {}

    /**
     * Update tenant weekend pricing and optionally add a blocked date.
     *
     * A date that still has active bookings is refused, and nothing is saved, until each of
     * those bookings is rescheduled or cancelled (BQ-108, AC-JBH-03).
     *
     * @param array{
     *     tanggal_block?: string|null,
     *     alasan?: string|null,
     *     weekend_price_type: string,
     *     weekend_price_value?: float|numeric|null
     * } $data
     * @return array{
     *     success: bool,
     *     blocked_date?: OwnerBlockedDate|null,
     *     error?: string,
     *     blocked_date_value?: string,
     *     conflicting_bookings?: Collection<int, Booking>
     * }
     */
    public function execute(Tenant $tenant, array $data): array
    {
        // Closing a date and claiming a slot share the tenant-first protocol (ADR-0005 decision 7)
        $result = DB::transaction(function () use ($tenant, $data): array {
            ClaimSlots::lockTenant((int) $tenant->id);
            $record = null;
            if (! empty($data['tanggal_block'])) {
                $date = Carbon::parse($data['tanggal_block'])->toDateString();
                $conflicts = ScheduleConflictRules::activeBookingsOnDate((int) $tenant->id, $date);

                if ($conflicts->isNotEmpty()) {
                    return ['success' => false, 'date' => $date, 'conflicts' => $conflicts];
                }

                // scope-bypass: the record is keyed by the server-resolved tenant.
                $record = OwnerBlockedDate::withoutGlobalScopes()->updateOrCreate(
                    ['idtenant' => $tenant->id, 'tanggal' => $data['tanggal_block']],
                    ['alasan' => $data['alasan'] ?? null]
                );
            }

            $tenant->weekend_price_type = $data['weekend_price_type'];
            $tenant->weekend_price_value = $data['weekend_price_type'] === 'none'
                ? null
                : (isset($data['weekend_price_value']) && $data['weekend_price_value'] !== '' ? (float) $data['weekend_price_value'] : null);
            $tenant->save();

            return ['success' => true, 'record' => $record];
        });

        if (! $result['success']) {
            $count = $result['conflicts']->count();

            return [
                'success' => false,
                'error' => 'Tanggal '.Carbon::parse($result['date'])->locale('id')->translatedFormat('j F Y')
                    .' masih punya '.$count.' booking aktif. Reschedule atau batalkan booking tersebut sebelum menutup tanggal ini.',
                'blocked_date_value' => $result['date'],
                'conflicting_bookings' => $result['conflicts'],
            ];
        }

        Cache::forget("tenant:slug:{$tenant->slug}");
        $this->availabilityCache->clearAllServicesAvailability((int) $tenant->id);

        return [
            'success' => true,
            'blocked_date' => $result['record'],
        ];
    }
}

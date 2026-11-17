<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Booking\BookingRules;
use App\Domain\Subscription\EntitlementRules;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;

/**
 * Opens the missing slots of every service that switched on automatic opening (FR-SCHEDULE-015).
 * Each opening interval of each day goes through BulkCreateSchedules, so the tenant lock, the
 * duplicate check, closed dates, the days-ahead limit and the plan check stay in one place.
 */
final class OpenAutomaticSlots
{
    /** Furthest day ahead the system opens slots (D-026). */
    public const HORIZON_DAYS = 30;

    public function __construct(private BulkCreateSchedules $bulkCreateSchedules) {}

    /** @return int number of slots created */
    public function execute(Tenant $tenant, ?Carbon $today = null): int
    {
        // BulkCreateSchedules reads tenant-scoped models; run inside this tenant like bookings:expire-payments.
        $context = app(TenantContext::class);
        $previous = $context->getTenantId();
        $context->setTenantId((int) $tenant->id);
        try {
            return $this->open($tenant, $today);
        } finally {
            $context->setTenantId($previous);
        }
    }

    private function open(Tenant $tenant, ?Carbon $today): int
    {
        $today = ($today ?? Carbon::today('Asia/Jakarta'))->copy()->startOfDay();
        // scope-bypass: the scheduler has no tenant context; filtered to this tenant.
        $subscription = Subscription::withoutGlobalScopes()->with('plan')->where('idtenant', $tenant->id)->latest('id')->first();
        if (! EntitlementRules::hasEffectivePlan($subscription)) {
            return 0;
        }

        // scope-bypass: the scheduler has no tenant context; every query is filtered to this tenant.
        $services = Service::withoutGlobalScopes()->where('idtenant', $tenant->id)
            ->where('auto_open_slots', true)->where('is_active', true)->get();
        if ($services->isEmpty()) {
            return 0;
        }

        // scope-bypass: filtered to this tenant.
        $hours = OpeningHour::withoutGlobalScopes()->where('idtenant', $tenant->id)
            ->orderBy('opens_at')->get()->groupBy('day_of_week');
        $horizon = min(self::HORIZON_DAYS, $tenant->slot_open_days_ahead ?? self::HORIZON_DAYS, BookingRules::maximumAdvanceDays($tenant));

        $created = 0;
        for ($offset = 1; $offset <= $horizon; $offset++) {
            $date = $today->copy()->addDays($offset);
            foreach ($hours->get($date->dayOfWeekIso, collect()) as $interval) {
                foreach ($services as $service) {
                    $result = $this->bulkCreateSchedules->execute($tenant, [
                        'jenisslot' => 'harian',
                        'idlayanan' => (int) $service->id,
                        'tanggal' => $date->toDateString(),
                        'jammulai' => substr((string) $interval->opens_at, 0, 5),
                        'jamselesai' => substr((string) $interval->closes_at, 0, 5),
                        'intervalslot' => max(5, (int) $service->durasi),
                    ]);
                    $created += $result['created_count'];
                }
            }
        }

        return $created;
    }
}

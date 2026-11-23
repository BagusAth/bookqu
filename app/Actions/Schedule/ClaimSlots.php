<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Actions\Booking\EvictStalePendingBookings;
use App\Domain\Booking\BookingRules;
use App\Domain\Schedule\AvailabilityRules;
use App\Domain\Schedule\SlotRefusal;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Support\TenantDiscovery;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * The locked claim of the Slot bookability module (ADR-0005). It joins the booking,
 * walk-in or reschedule Action's transaction and:
 *
 * 1. locks the tenant row first (decision 4), then the requested schedules;
 * 2. verifies every schedule belongs to the tenant and service before any cleanup (decision 5);
 * 3. reclaims stale pending bookings on those schedules through the Payment lifecycle (decision 6);
 * 4. re-evaluates each slot with AvailabilityRules::refusal(), the rule listings use,
 *    including room for the participants and unit conflicts (ADR-0017 decision 5).
 *
 * A refusal throws SlotClaimRefused, so the caller's whole transaction rolls back.
 */
class ClaimSlots
{
    public function __construct(
        protected EvictStalePendingBookings $evictStalePendingBookings
    ) {}

    /**
     * @param  list<int>  $scheduleIds
     * @return Collection<int, Schedule> the locked schedules, in the requested order
     *
     * @throws SlotClaimRefused
     */
    public function execute(
        Tenant $tenant,
        int $serviceId,
        array $scheduleIds,
        ?string $date = null,
        ?int $excludeBookingId = null,
        int $participants = 1,
    ): Collection {
        return app(TenantContext::class)->run((int) $tenant->id,
            fn (): Collection => $this->claim($tenant, $serviceId, $scheduleIds, $date, $excludeBookingId, $participants));
    }

    /**
     * @param  list<int|string>  $scheduleIds
     * @return Collection<int, Schedule>
     */
    private function claim(Tenant $tenant, int $serviceId, array $scheduleIds, ?string $date, ?int $excludeBookingId, int $participants): Collection
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('ClaimSlots must run inside the booking operation\'s transaction.');
        }

        $scheduleIds = array_values(array_unique(array_map('intval', $scheduleIds)));

        $tenant->refreshForUpdate();

        $schedules = Schedule::with('layanan')
            ->whereIn('id', $scheduleIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($schedules->count() !== count($scheduleIds) || $scheduleIds === []) {
            $reason = app(TenantDiscovery::class)->hasForeignSchedule($scheduleIds, (int) $tenant->id)
                ? SlotRefusal::ForeignTenant : SlotRefusal::NotFound;
            throw new SlotClaimRefused($reason);
        }

        foreach ($schedules as $schedule) {
            if ((int) $schedule->idlayanan !== $serviceId) {
                throw new SlotClaimRefused(SlotRefusal::ForeignService, (int) $schedule->id, substr((string) $schedule->jam_mulai, 0, 5));
            }
        }

        $this->evictStalePendingBookings->execute($scheduleIds);

        $ordered = collect($scheduleIds)->map(fn (int $id) => $schedules->get($id));

        $applyPolicy = $excludeBookingId === null && BookingRules::hasSlotPolicy($tenant);
        $now = Carbon::now('Asia/Jakarta');
        $dates = $ordered->map(fn (Schedule $slot) => Carbon::parse($slot->tanggal)->toDateString());
        $occupied = $applyPolicy && $tenant->booking_buffer_minutes > 0
            ? BookingRules::occupiedRanges($tenant, $serviceId, $dates->min(), $dates->max()) : [];

        foreach ($ordered as $schedule) {
            $refusal = AvailabilityRules::refusal(
                $schedule,
                excludeBookingId: $excludeBookingId,
                tenantId: (int) $tenant->id,
                serviceId: $serviceId,
                date: $date,
                participants: $participants,
            );

            if ($refusal === null && $applyPolicy
                && ! BookingRules::slotAllowedByPolicy($tenant, Carbon::parse($schedule->tanggal)->toDateString(), $schedule->jam_mulai, $schedule->jam_selesai, $now, $occupied)) {
                $refusal = SlotRefusal::BookingPolicy;
            }

            if ($refusal !== null) {
                throw new SlotClaimRefused($refusal, (int) $schedule->id, substr((string) $schedule->jam_mulai, 0, 5));
            }
        }

        return $ordered->values();
    }

    /**
     * Tenant-first synchronization point shared by every writer that changes what a
     * tenant's slots can accept (ADR-0005 decision 4).
     */
    public static function lockTenant(int $tenantId): void
    {
        Tenant::whereKey($tenantId)->lockForUpdate()->first();
    }
}

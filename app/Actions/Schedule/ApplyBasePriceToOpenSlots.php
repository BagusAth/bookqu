<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Booking\BookingRules;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * "Terapkan harga dasar ke slot terbuka" (FR-SCHEDULE-012, AC-JBH-04): future free slots of a
 * service drop their per-slot override, so their price is the base price with the price rules.
 * Past slots and slots a booking occupies keep their price. Replaces the old default-pricing
 * action (YATIM-1).
 */
class ApplyBasePriceToOpenSlots
{
    public function __construct(private ScheduleAvailabilityCache $availabilityCache) {}

    public function execute(Tenant $tenant, Service $service): int
    {
        if ((int) $service->idtenant !== (int) $tenant->id) {
            abort(404);
        }

        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();

        [$count, $dates] = app(TenantContext::class)->run((int) $tenant->id, fn (): array => DB::transaction(function () use ($tenant, $service, $now, $today): array {
            ClaimSlots::lockTenant((int) $tenant->id);

            $slots = Schedule::query()
                ->where('idtenant', $tenant->id)
                ->where('idlayanan', $service->id)
                ->where('status', 'tersedia')
                ->whereNotNull('harga_override')
                ->where(fn ($q) => $q->whereDate('tanggal', '>', $today)
                    ->orWhere(fn ($todayQ) => $todayQ->whereDate('tanggal', $today)->where('jam_mulai', '>', $now->format('H:i:s'))))
                ->whereDoesntHave('bookings', fn ($q) => BookingRules::applyOccupiesSlotCondition($q, 'bookings.'))
                ->get(['id', 'tanggal']);

            if ($slots->isEmpty()) {
                return [0, []];
            }

            Schedule::whereIn('id', $slots->pluck('id'))->update(['harga_override' => null]);

            return [$slots->count(), $slots->map(fn (Schedule $slot): string => Carbon::parse($slot->tanggal)->toDateString())->unique()->values()->all()];
        }));

        if ($dates !== []) {
            $this->availabilityCache->clearScheduleCache((int) $tenant->id, (int) $service->id, $dates);
        }

        return $count;
    }
}

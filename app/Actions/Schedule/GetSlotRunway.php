<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;

/**
 * Slot runway per active service for the Jam Buka & Harga header (owner-portal §8.8,
 * AC-JBH-05): the last date customers can still book, and a warning when that is less than
 * seven days away or no free slot exists in the next seven days. Free means bookable under the
 * single availability rule (GetAvailableSchedules, INV-SCH-01).
 */
class GetSlotRunway
{
    public const WARNING_DAYS = 7;

    public function __construct(private GetAvailableSchedules $availability) {}

    /**
     * @return list<array{service_id: int, service: string, open_until: ?string, warning: bool}>
     */
    public function execute(Tenant $tenant): array
    {
        $today = Carbon::today('Asia/Jakarta');
        $weekEnd = $today->copy()->addDays(self::WARNING_DAYS - 1)->toDateString();
        $warningDate = $today->copy()->addDays(self::WARNING_DAYS)->toDateString();
        $result = [];

        // scope-bypass: filtered by the authenticated tenant
        $services = Service::withoutGlobalScopes()->where('idtenant', $tenant->id)->where('is_active', true)->orderBy('namalayanan')->get();

        foreach ($services as $service) {
            $openDates = collect($this->availability->getDateAvailability($tenant, $service))
                ->filter(fn (array $day): bool => $day['available_slots'] > 0)
                ->pluck('date');

            $openUntil = $openDates->max();
            $hasSlotThisWeek = $openDates->contains(fn (string $date): bool => $date <= $weekEnd);

            $result[] = [
                'service_id' => (int) $service->id,
                'service' => (string) $service->namalayanan,
                'open_until' => $openUntil,
                'warning' => ! $hasSlotThisWeek || $openUntil === null || $openUntil < $warningDate,
            ];
        }

        return $result;
    }
}

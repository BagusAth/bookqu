<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Replace a tenant's weekly opening hours and how far ahead slots may be opened
 * (FR-SCHEDULE-011). Opening hours open slots only for services with automatic opening
 * (FR-SCHEDULE-015, D-026); the switch here sets it for every active service.
 */
class SaveOpeningHours
{
    /**
     * @param  array<int, list<array{opens_at: string, closes_at: string}>>  $days  intervals by ISO weekday (1 = Monday)
     * @param  ?bool  $autoOpenSlots  The page-level "Buka slot otomatis" switch (FR-SCHEDULE-015): on or off for every
     *                                active service of the tenant; null leaves each service's own choice.
     */
    public function execute(Tenant $tenant, array $days, ?int $slotOpenDaysAhead, ?bool $autoOpenSlots = null): void
    {
        $rows = [];
        foreach ($days as $day => $intervals) {
            $intervals = collect($intervals)->sortBy('opens_at')->values()->all();
            $previousClose = null;
            foreach ($intervals as $interval) {
                $opens = substr($interval['opens_at'], 0, 5);
                $closes = substr($interval['closes_at'], 0, 5);
                if ($opens >= $closes || ($previousClose !== null && $opens < $previousClose)) {
                    throw ValidationException::withMessages([
                        "days.$day" => 'Jam buka harus sebelum jam tutup, dan jam istirahat tidak boleh tumpang tindih.',
                    ]);
                }
                $previousClose = $closes;
                $rows[] = ['idtenant' => $tenant->id, 'day_of_week' => (int) $day, 'opens_at' => $opens.':00', 'closes_at' => $closes.':00'];
            }
        }

        DB::transaction(function () use ($tenant, $rows, $slotOpenDaysAhead, $autoOpenSlots): void {
            // scope-bypass: filtered by the authenticated tenant
            OpeningHour::withoutGlobalScopes()->where('idtenant', $tenant->id)->delete();
            foreach ($rows as $row) {
                OpeningHour::create($row);
            }
            $tenant->forceFill(['slot_open_days_ahead' => $slotOpenDaysAhead])->save();
            if ($autoOpenSlots !== null) {
                // scope-bypass: filtered by the authenticated tenant
                Service::withoutGlobalScopes()->where('idtenant', $tenant->id)->where('is_active', true)
                    ->update(['auto_open_slots' => $autoOpenSlots]);
            }
        });
    }
}

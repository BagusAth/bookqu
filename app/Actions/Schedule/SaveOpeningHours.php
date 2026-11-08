<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Models\OpeningHour;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Replace a tenant's weekly opening hours and how far ahead slots may be opened
 * (FR-SCHEDULE-011). Opening hours never open slots by themselves (D-021).
 */
class SaveOpeningHours
{
    /**
     * @param  array<int, list<array{opens_at: string, closes_at: string}>>  $days  intervals by ISO weekday (1 = Monday)
     */
    public function execute(Tenant $tenant, array $days, ?int $slotOpenDaysAhead): void
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

        DB::transaction(function () use ($tenant, $rows, $slotOpenDaysAhead): void {
            // scope-bypass: filtered by the authenticated tenant
            OpeningHour::withoutGlobalScopes()->where('idtenant', $tenant->id)->delete();
            foreach ($rows as $row) {
                OpeningHour::create($row);
            }
            $tenant->forceFill(['slot_open_days_ahead' => $slotOpenDaysAhead])->save();
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Models\Tenant;
use Carbon\Carbon;

/**
 * Summary above the Jam Buka & Harga tabs (BQ-162, Papan Jadwal prototype): the date most active
 * services stay open until, how many days ahead that is, how many services open slots
 * automatically (FR-SCHEDULE-015) and which services are open for less than seven days. Built
 * from GetSlotRunway, so it follows the single availability rule.
 */
class GetScheduleOverview
{
    /**
     * @param  list<array{service_id: int, service: string, open_until: ?string, warning: bool, auto_open: bool}>  $runway
     * @return array{open_until: ?string, open_until_label: ?string, days_ahead: ?int, services: int, auto_open: int, short: list<array{service_id: int, service: string, open_until: ?string, open_until_label: ?string, auto_open: bool}>}
     */
    public function execute(Tenant $tenant, array $runway): array
    {
        $today = Carbon::today('Asia/Jakarta');
        $dates = collect($runway)->pluck('open_until')->filter()->countBy();
        // The most common open-until date; ties go to the later date.
        $openUntil = $dates->keys()->sortByDesc(fn (string $date): string => sprintf('%05d %s', $dates[$date], $date))->first();

        $short = collect($runway)->where('warning', true)->map(fn (array $service): array => [
            'service_id' => $service['service_id'],
            'service' => $service['service'],
            'open_until' => $service['open_until'],
            'open_until_label' => $service['open_until'] ? self::label($service['open_until']) : null,
            'auto_open' => $service['auto_open'],
        ])->values()->all();

        return [
            'open_until' => $openUntil,
            'open_until_label' => $openUntil ? self::label($openUntil) : null,
            'days_ahead' => $openUntil ? (int) $today->diffInDays(Carbon::parse($openUntil, 'Asia/Jakarta')) : null,
            'services' => count($runway),
            'auto_open' => collect($runway)->where('auto_open', true)->count(),
            'short' => $short,
        ];
    }

    public static function label(string $date): string
    {
        return Carbon::parse($date)->locale('id')->translatedFormat('j M');
    }
}

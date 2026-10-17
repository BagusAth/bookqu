<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Schedule\ScheduleConflictRules;
use App\Models\OwnerBlockedDate;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BulkCreateSchedules
{
    public function __construct(
        protected ScheduleAvailabilityCache $availabilityCache
    ) {}

    /**
     * Bulk create schedule slots for a tenant service.
     *
     * @param Tenant $tenant
     * @param array{
     *     jenisslot: string,
     *     idlayanan: int,
     *     tanggal?: string|null,
     *     tanggalmulai?: string|null,
     *     tanggalselesai?: string|null,
     *     jammulai: string,
     *     jamselesai: string,
     *     intervalslot: int
     * } $data
     * @return array{
     *     success: bool,
     *     created_count: int,
     *     days_count: int,
     *     error?: string,
     *     error_field?: string
     * }
     */
    public function execute(Tenant $tenant, array $data): array
    {
        $service = Service::withoutGlobalScopes()->where('idtenant', $tenant->id)->find($data['idlayanan']);

        if (!$service) {
            return [
                'success' => false,
                'created_count' => 0,
                'days_count' => 0,
                'error' => 'Program tidak ditemukan.',
                'error_field' => 'idlayanan',
            ];
        }

        $intervalSlot = (int) $data['intervalslot'];
        if ($intervalSlot !== (int) $service->durasi) {
            return [
                'success' => false,
                'created_count' => 0,
                'days_count' => 0,
                'error' => 'Durasi slot harus sesuai dengan durasi program (' . $service->durasi . ' menit).',
                'error_field' => 'intervalslot',
            ];
        }

        // Generate date list
        $dates = [];
        if ($data['jenisslot'] === 'harian') {
            if (!empty($data['tanggal'])) {
                $dates[] = Carbon::parse($data['tanggal'])->format('Y-m-d');
            }
        } else {
            $cursor = Carbon::parse($data['tanggalmulai']);
            $end = Carbon::parse($data['tanggalselesai']);
            while ($cursor->lte($end)) {
                $dates[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }
        }

        // Exclude owner blocked dates
        $blocked = OwnerBlockedDate::withoutGlobalScopes()->where('idtenant', $tenant->id)
            ->whereIn('tanggal', $dates)
            ->pluck('tanggal')
            ->map(fn($t) => Carbon::parse($t)->format('Y-m-d'))
            ->toArray();

        $activeDates = array_values(array_filter($dates, fn($t) => !in_array($t, $blocked, true)));

        $createdCount = DB::transaction(function () use ($activeDates, $data, $service, $tenant, $intervalSlot) {
            $count = 0;

            foreach ($activeDates as $dateStr) {
                $cursor = Carbon::parse($dateStr . ' ' . $data['jammulai']);
                $end = Carbon::parse($dateStr . ' ' . $data['jamselesai']);

                $hargaOverride = null;
                if (Carbon::parse($dateStr)->isWeekend()) {
                    $hargaOverride = ScheduleConflictRules::calculateWeekendPrice(
                        $service,
                        $tenant->weekend_price_type,
                        $tenant->weekend_price_value !== null ? (float) $tenant->weekend_price_value : null
                    );
                }

                while ($cursor->copy()->addMinutes($intervalSlot)->lte($end)) {
                    $jamMulai = $cursor->format('H:i:s');
                    // Subtract 1 minute per specification requirement (e.g. 11:00:00 - 11:59:00 instead of 12:00:00)
                    $jamSelesai = $cursor->copy()->addMinutes($intervalSlot)->subMinute()->format('H:i:s');

                    $hasConflict = ScheduleConflictRules::hasScheduleConflict(
                        (int) $tenant->id,
                        (int) $data['idlayanan'],
                        $dateStr,
                        $jamMulai,
                        $jamSelesai
                    );

                    if (!$hasConflict) {
                        Schedule::create([
                            'idtenant' => $tenant->id,
                            'idlayanan' => $data['idlayanan'],
                            'tanggal' => $dateStr,
                            'jam_mulai' => $jamMulai,
                            'jam_selesai' => $jamSelesai,
                            'harga_override' => $hargaOverride,
                            'status' => 'tersedia',
                        ]);
                        $count++;
                    }

                    $cursor->addMinutes($intervalSlot);
                }
            }

            return $count;
        });

        if ($createdCount > 0) {
            $this->availabilityCache->clearScheduleCache($tenant->id, (int) $data['idlayanan'], $activeDates);
        }

        return [
            'success' => true,
            'created_count' => $createdCount,
            'days_count' => count($activeDates),
        ];
    }
}

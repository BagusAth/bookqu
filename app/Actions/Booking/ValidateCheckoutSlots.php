<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingRules;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ValidateCheckoutSlots
{
    /**
     * @param  list<string>  $jamArray
     * @param  list<int|string>  $scheduleIdArray
     * @return array{ids: list<int>, times: list<string>}
     */
    public function execute(Tenant $tenant, Service $service, string $selectedDate, array $jamArray, array $scheduleIdArray): array
    {
        $selectedDate = Carbon::parse($selectedDate)->toDateString();
        $wib = 'Asia/Jakarta';
        $nowWib = Carbon::now($wib);

        $validatedSchedules = [];

        foreach ($jamArray as $index => $jamValue) {
            $scheduleId = (int) $scheduleIdArray[$index];

            $schedule = DB::transaction(function () use ($tenant, $service, $scheduleId, $selectedDate) {
                $row = DB::table('schedules')
                    ->where('id', $scheduleId)
                    ->where('idtenant', $tenant->id)
                    ->where('idlayanan', $service->id)
                    ->whereDate('tanggal', $selectedDate)
                    ->where('status', 'tersedia')
                    ->lockForUpdate()
                    ->first();

                if (! $row) {
                    return null;
                }

                $isBooked = BookingRules::isSlotOccupied((int) $scheduleId);

                return $isBooked ? null : $row;
            });

            if (! $schedule) {
                throw ValidationException::withMessages(['jam' => "Jam {$jamValue} sudah tidak tersedia. Silakan pilih jam lain."]);
            }

            $slotDateTime = Carbon::parse($selectedDate.' '.$schedule->jam_mulai, $wib);
            $resolvedTime = $slotDateTime->format('H:i');

            if ($jamValue !== $resolvedTime) {
                throw ValidationException::withMessages(['jam' => "Jam {$jamValue} tidak sesuai dengan jadwal."]);
            }

            if ($slotDateTime->lessThanOrEqualTo($nowWib)) {
                throw ValidationException::withMessages(['jam' => "Waktu {$jamValue} sudah terlewat (WIB). Silakan pilih jam lain."]);
            }

            $validatedSchedules[] = $schedule;
        }

        // Contiguous sequence check based on service duration
        usort($validatedSchedules, fn ($a, $b) => strcmp($a->jam_mulai, $b->jam_mulai));
        if (! BookingRules::validateContiguousSlots($validatedSchedules, (int) $service->durasi, $selectedDate, $wib)) {
            throw ValidationException::withMessages(['jam' => 'Slot harus berurutan tanpa jeda.']);
        }

        $validatedScheduleIds = array_map(fn ($s) => $s->id, $validatedSchedules);
        $validatedTimes = array_map(fn ($s) => Carbon::parse($selectedDate.' '.$s->jam_mulai, $wib)->format('H:i'), $validatedSchedules);

        return ['ids' => $validatedScheduleIds, 'times' => $validatedTimes];
    }
}

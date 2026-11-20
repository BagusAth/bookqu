<?php

namespace App\Actions\Booking;

use App\Actions\Schedule\GetAvailableSchedules;
use App\Domain\Booking\BookingRules;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\Rupiah;
use Carbon\Carbon;

final class GetScheduleSelection
{
    public function __construct(private GetAvailableSchedules $availability) {}

    /** @return array<string, mixed> */
    public function execute(Tenant $tenant, Service $service, ?string $date = null): array
    {
        $min = Carbon::today('Asia/Jakarta');
        $max = $min->copy()->addDays(BookingRules::maximumAdvanceDays($tenant));
        $availability = collect($this->availability->getDateAvailability($tenant, $service, $min, $max))->keyBy('date');
        $dates = [];
        for ($day = $min->copy(); $day->lte($max); $day->addDay()) {
            $iso = $day->toDateString();
            $entry = $availability->get($iso);
            $dates[] = [
                'date' => $iso,
                'label' => $day->locale('id')->translatedFormat('D, d M'),
                'is_available' => ($entry['available_slots'] ?? 0) > 0,
                'is_blocked' => $entry['is_blocked'] ?? false,
            ];
        }
        $selected = collect($dates)->firstWhere('date', $date);
        if ($selected === null) {
            $selected = collect($dates)->firstWhere('is_available', true) ?? $dates[0];
        }
        $date = $selected['date'];
        $slots = $selected['is_blocked'] ? [] : $this->availability->getTimeSlots($tenant, $service, $date);
        $groups = collect($slots)->groupBy('session');
        $label = Carbon::parse($date, 'Asia/Jakarta')->locale('id')->translatedFormat('l, d M Y');

        return [
            'dates' => $dates,
            'selectedDate' => $date,
            'isBlocked' => $selected['is_blocked'],
            'selectedDateFormatted' => $label,
            'selectedDateLabel' => $label,
            'morningSlots' => $groups->get('morning', collect())->values()->all(),
            'afternoonSlots' => $groups->get('afternoon', collect())->values()->all(),
            'eveningSlots' => $groups->get('evening', collect())->values()->all(),
            'servicePayload' => [
                'id' => $service->id,
                'name' => $service->namalayanan,
                'price' => (float) $service->harga,
                'price_label' => Rupiah::format($service->harga),
                'duration' => (int) $service->durasi,
                'duration_unit' => $service->satuan_durasi ?: 'menit',
                'image_url' => $service->cover_image_url,
            ],
        ];
    }
}

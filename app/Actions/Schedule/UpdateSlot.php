<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Booking\BookingRules;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateSlot
{
    public function __construct(private ScheduleAvailabilityCache $availabilityCache) {}

    public function execute(Tenant $tenant, int $slotId, string $operation, ?int $price): void
    {
        DB::transaction(function () use ($tenant, $slotId, $operation, $price): void {
            ClaimSlots::lockTenant((int) $tenant->id);
            $slot = Schedule::where('idtenant', $tenant->id)->lockForUpdate()->findOrFail($slotId);
            if (BookingRules::isSlotOccupied($slotId)) {
                throw ValidationException::withMessages(['slot' => 'Slot memiliki booking aktif.']);
            }

            $slot->update(match ($operation) {
                'open' => ['status' => 'tersedia'],
                'close' => ['status' => 'tidak_tersedia'],
                'price' => ['harga_override' => $price],
                default => throw ValidationException::withMessages(['operation' => 'Perubahan slot tidak valid.']),
            });

            DB::afterCommit(fn () => $this->availabilityCache->clearScheduleCache(
                (int) $tenant->id,
                (int) $slot->idlayanan,
                [Carbon::parse($slot->tanggal)->toDateString()],
            ));
        });
    }
}

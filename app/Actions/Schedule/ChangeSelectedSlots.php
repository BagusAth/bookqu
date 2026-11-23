<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Booking\BookingRules;
use App\Models\Schedule;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ChangeSelectedSlots
{
    public function __construct(private UpdateSlot $update, private DeleteSchedule $delete) {}

    /** @param list<int|string> $ids */
    public function execute(Tenant $tenant, array $ids, string $operation): void
    {
        DB::transaction(function () use ($tenant, $ids, $operation): void {
            ClaimSlots::lockTenant((int) $tenant->id);
            $slots = Schedule::where('idtenant', $tenant->id)->whereKey($ids)->orderBy('id')->lockForUpdate()->get();
            abort_unless($slots->count() === count($ids), 404);
            $occupied = Schedule::where('idtenant', $tenant->id)->whereKey($ids)
                ->whereHas('bookings', fn (Builder $query) => BookingRules::applyOccupiesSlotCondition($query))->exists();
            if ($occupied) {
                throw ValidationException::withMessages(['slot_ids' => 'Pilihan berisi slot yang memiliki booking aktif.']);
            }
            foreach ($slots as $slot) {
                if ($operation === 'close') {
                    $this->update->execute($tenant, (int) $slot->id, $operation, null);
                } elseif (! $this->delete->execute($tenant, $slot)['success']) {
                    throw ValidationException::withMessages(['slot_ids' => 'Salah satu slot tidak dapat dihapus.']);
                }
            }
        });
    }
}

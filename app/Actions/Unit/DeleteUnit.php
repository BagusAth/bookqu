<?php

declare(strict_types=1);

namespace App\Actions\Unit;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Booking\BookingRules;
use App\Models\Tenant;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Deletes a unit that no future booking uses (FR-UNIT-001).
 */
class DeleteUnit
{
    /** @throws ValidationException */
    public function execute(Tenant $tenant, Unit $unit): void
    {
        if ((int) $unit->idtenant !== (int) $tenant->id) {
            abort(404);
        }

        DB::transaction(function () use ($tenant, $unit): void {
            ClaimSlots::lockTenant((int) $tenant->id);

            $serviceIds = $unit->services()->pluck('services.id')->all();
            $query = DB::table('bookings')
                ->where('idtenant', $tenant->id)
                ->whereIn('idlayanan', $serviceIds)
                ->where('tanggalbooking', '>=', Carbon::today('Asia/Jakarta')->toDateString());

            if ($serviceIds !== [] && BookingRules::applyOccupiesSlotCondition($query, '')->exists()) {
                throw ValidationException::withMessages(['unit' => 'Unit masih dipakai booking yang akan datang dan tidak bisa dihapus.']);
            }

            $unit->delete();
        });
    }
}

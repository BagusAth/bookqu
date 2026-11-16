<?php

declare(strict_types=1);

namespace App\Actions\Unit;

use App\Actions\Booking\GetBookingTimeRanges;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\UnitUtilisation;
use App\Domain\Modules\ModuleRelease;
use App\Models\Asset;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Models\Unit;
use Carbon\Carbon;

/**
 * Units of a tenant with the services that use them, and the existing bookings that already
 * overlap on a unit (FR-UNIT-004, AC-UNT-02). Nothing is repaired automatically.
 */
class GetUnitsOverview
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Tenant $tenant): array
    {
        // scope-bypass: filtered by the authenticated tenant
        $units = Unit::withoutGlobalScopes()->where('idtenant', $tenant->id)
            ->with(['services' => fn ($query) => $query->where('services.idtenant', $tenant->id)->select('services.id', 'namalayanan')])
            ->orderBy('name')->get();
        [$seats, $bindings] = BookingRules::seatsTakenSql();
        $slotsByService = Schedule::where('idtenant', $tenant->id)
            ->whereIn('idlayanan', $units->flatMap(fn (Unit $unit) => $unit->services->modelKeys())->unique()->all())
            ->whereDate('tanggal', today())->select('schedules.*')->selectRaw($seats.' AS seats_taken', $bindings)
            ->get()->groupBy('idlayanan');

        $assets = Asset::where('idtenant', $tenant->id)->latest('id')->get()->keyBy('file_path');
        $list = [];
        $conflicts = [];
        foreach ($units as $unit) {
            $serviceIds = $unit->services->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
            $image = $assets->get((string) $unit->image_path);
            $list[] = [
                'image_asset_id' => $image?->id,
                'image_url' => $image?->url,
                'id' => (int) $unit->id,
                'name' => $unit->name,
                'type' => $unit->type,
                'kapasitas' => $unit->kapasitas,
                'lokasi' => $unit->lokasi,
                'is_active' => $unit->is_active,
                'service_ids' => $serviceIds,
                'service_names' => $unit->services->pluck('namalayanan')->all(),
                'usage_today' => $unit->is_active
                    ? UnitUtilisation::forSlots($unit->services->flatMap(fn ($service) => $slotsByService->get($service->getKey(), [])))
                    : null,
            ];
            if ($unit->is_active) {
                foreach (BookingRules::unitOverlaps((int) $tenant->id, $serviceIds) as $pair) {
                    $conflicts[] = ['unit_id' => (int) $unit->id, 'booking_ids' => $pair];
                }
            }
        }

        $bookings = Booking::where('idtenant', $tenant->id)->whereKey(array_merge(...array_column($conflicts, 'booking_ids')))->get()->keyBy('id');
        $timeRanges = $bookings->isEmpty() ? [] : app(GetBookingTimeRanges::class)->execute($bookings)['sessionRanges'];
        $bookingDetails = [];
        foreach ($bookings as $booking) {
            $bookingDetails[$booking->id] = [
                'url' => route('owner.bookings.panel', $booking->id),
                'code' => $booking->booking_code ?: 'BKQ-'.$booking->id,
                'customer' => $booking->namapelanggan,
                'session' => Carbon::parse($booking->tanggalbooking)->format('d/m/Y').' · '.$timeRanges[$booking->id]->label().' WIB',
            ];
        }

        return [
            'units' => $list,
            'conflicts' => $conflicts,
            'bookingDetails' => $bookingDetails,
            'images' => $assets->map(fn (Asset $asset): array => [
                'id' => (int) $asset->id, 'url' => $asset->url, 'alt' => (string) $asset->title,
            ])->values()->all(),
            'galleryUrl' => ModuleRelease::state('booking-page') === 'released'
                ? route('owner.booking-page', ['section' => 'galeri']) : null,
        ];
    }
}

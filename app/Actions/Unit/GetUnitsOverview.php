<?php

declare(strict_types=1);

namespace App\Actions\Unit;

use App\Domain\Booking\BookingRules;
use App\Domain\Modules\ModuleRelease;
use App\Models\Asset;
use App\Models\Tenant;
use App\Models\Unit;

/**
 * Units of a tenant with the services that use them, and the existing bookings that already
 * overlap on a unit (FR-UNIT-004, AC-UNT-02). Nothing is repaired automatically.
 */
class GetUnitsOverview
{
    /**
     * @return array{units: list<array{id: int, name: string, type: string, kapasitas: int, lokasi: ?string, is_active: bool, service_ids: list<int>, image_asset_id: ?int, image_url: ?string}>, images: list<array{id: int, url: string, alt: string}>, galleryUrl: ?string, conflicts: list<array{unit_id: int, booking_ids: array{0: int, 1: int}}>}
     */
    public function execute(Tenant $tenant): array
    {
        // scope-bypass: filtered by the authenticated tenant
        $units = Unit::withoutGlobalScopes()->where('idtenant', $tenant->id)->with('services:id')->orderBy('name')->get();

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
            ];
            if ($unit->is_active) {
                foreach (BookingRules::unitOverlaps((int) $tenant->id, $serviceIds) as $pair) {
                    $conflicts[] = ['unit_id' => (int) $unit->id, 'booking_ids' => $pair];
                }
            }
        }

        return [
            'units' => $list,
            'conflicts' => $conflicts,
            'images' => $assets->map(fn (Asset $asset): array => [
                'id' => (int) $asset->id, 'url' => $asset->url, 'alt' => (string) $asset->title,
            ])->values()->all(),
            'galleryUrl' => ModuleRelease::state('booking-page') === 'released'
                ? route('owner.booking-page', ['section' => 'galeri']) : null,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Service;

use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Validation\ValidationException;

class DeleteService
{
    use ClearsBookingCache;

    /**
     * Delete a service with active booking protection.
     *
     * @param Service|int|string $service
     * @return string Name of the deleted service
     * @throws ValidationException
     */
    public function execute(Tenant $tenant, Service|int|string $service): string
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $targetId = $service instanceof Service ? $service->id : (int) $service;
        $layanan = Service::withoutGlobalScopes()
            ->where('idtenant', $tenant->id)
            ->findOrFail($targetId);
        $namalayanan = $layanan->namalayanan;

        $hasActiveBookings = $layanan->bookings()->whereIn('status', ['pending', 'paid'])->exists();
        if ($hasActiveBookings) {
            throw ValidationException::withMessages([
                'error' => 'Layanan "' . $namalayanan . '" memiliki jadwal booking aktif (pending/confirmed). Anda tidak dapat menghapusnya. Silakan nonaktifkan status layanan ini agar tidak dapat dipesan lagi.',
            ]);
        }

        $layanan->staff()->detach();
        $layanan->resources()->detach();
        $layanan->additionalItems()->detach();
        $layanan->delete();

        $this->clearServiceCache($tenant->id, $targetId);

        return $namalayanan;
    }
}

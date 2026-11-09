<?php

declare(strict_types=1);

namespace App\Actions\Service;

use App\Actions\Schedule\ClaimSlots;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteService
{
    use ClearsBookingCache;

    /**
     * Delete a service that has never been booked.
     *
     * @return string Name of the deleted service
     *
     * @throws ValidationException
     */
    public function execute(Tenant $tenant, Service|int|string $service): string
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $targetId = $service instanceof Service ? $service->id : (int) $service;
        // scope-bypass: filtered by the authenticated tenant
        $layanan = Service::withoutGlobalScopes()
            ->where('idtenant', $tenant->id)
            ->findOrFail($targetId);
        $namalayanan = $layanan->namalayanan;

        // FR-SERVICE-006, AC-LYN-02: any booking, cancelled ones included, is history the service must keep.
        $hasBookingHistory = $layanan->bookings()->exists();
        if ($hasBookingHistory) {
            throw ValidationException::withMessages([
                'error' => 'Layanan "'.$namalayanan.'" sudah pernah dibooking, jadi tidak bisa dihapus. Nonaktifkan layanan ini agar tidak bisa dibooking lagi; riwayatnya tetap tersimpan.',
            ]);
        }

        DB::transaction(function () use ($tenant, $layanan) {
            ClaimSlots::lockTenant((int) $tenant->id);
            $layanan->resources()->detach();
            $layanan->additionalItems()->detach();
            $layanan->delete();
        });

        $this->clearServiceCache($tenant->id, $targetId);

        return $namalayanan;
    }
}

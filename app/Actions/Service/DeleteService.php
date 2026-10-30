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
     * Delete a service with active booking protection.
     *
     * @return string Name of the deleted service
     *
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

        $hasActiveBookings = $layanan->bookings()->whereIn('status', ['pending', 'paid', 'completed'])->exists();
        if ($hasActiveBookings) {
            throw ValidationException::withMessages([
                'error' => 'Layanan "'.$namalayanan.'" memiliki riwayat transaksi atau jadwal booking aktif. Untuk menjaga integritas data riwayat keuangan, layanan tidak dapat dihapus. Silakan nonaktifkan status layanan ini.',
            ]);
        }

        DB::transaction(function () use ($tenant, $layanan) {
            ClaimSlots::lockTenant((int) $tenant->id);
            $layanan->staff()->detach();
            $layanan->resources()->detach();
            $layanan->additionalItems()->detach();
            $layanan->delete();
        });

        $this->clearServiceCache($tenant->id, $targetId);

        return $namalayanan;
    }
}

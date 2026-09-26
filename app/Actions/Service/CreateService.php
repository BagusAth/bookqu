<?php

declare(strict_types=1);

namespace App\Actions\Service;

use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\UsageLog;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateService
{
    use ClearsBookingCache;

    /**
     * Create a new service under tenant with limits, relationships, and cache invalidation.
     *
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    public function execute(Tenant $tenant, array $data, ?UploadedFile $coverImage = null): Service
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $subscription = Subscription::with('plan')->where('idtenant', $tenant->id)->latest()->first();
        if ($subscription && $subscription->plan && !$subscription->plan->isunlimited && $subscription->plan->maxlayanan > 0) {
            $currentServices = Service::where('idtenant', $tenant->id)->count();
            if ($currentServices >= $subscription->plan->maxlayanan) {
                throw ValidationException::withMessages([
                    'namalayanan' => 'Batas maksimum layanan (' . $subscription->plan->maxlayanan . ') telah tercapai. Silakan upgrade paket Anda.',
                ]);
            }
        }

        $imageUrl = null;
        if ($coverImage) {
            $imageUrl = $coverImage->store('programs', 'public');
        }

        $service = Service::create([
            'idtenant'    => $tenant->id,
            'namalayanan' => $data['namalayanan'],
            'harga'       => $data['harga'],
            'durasi'      => $data['durasi'],
            'idcategory'  => $data['idcategory'] ?? null,
            'deskripsi'   => $data['deskripsi'] ?? null,
            'image_url'   => $imageUrl,
        ]);

        if (!empty($data['staff_ids'])) {
            $service->staff()->sync($data['staff_ids']);
        }
        if (!empty($data['resource_ids'])) {
            $service->resources()->sync($data['resource_ids']);
        }
        if (!empty($data['additional_item_ids'])) {
            $service->additionalItems()->sync($data['additional_item_ids']);
        }

        try {
            UsageLog::record($tenant->id, 'layanan');
        } catch (\Exception $e) {
            Log::error('Gagal catat usage log layanan: ' . $e->getMessage());
        }

        $this->clearServiceCache($tenant->id, $service->id);

        return $service;
    }
}

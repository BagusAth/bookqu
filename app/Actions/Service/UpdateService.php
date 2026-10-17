<?php

declare(strict_types=1);

namespace App\Actions\Service;

use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateService
{
    use ClearsBookingCache;

    /**
     * Update an existing service and its relationships.
     *
     * @param Service|int|string $service
     * @param array<string, mixed> $data
     */
    public function execute(
        Tenant $tenant,
        Service|int|string $service,
        array $data,
        ?UploadedFile $coverImage = null,
        bool $removeImage = false
    ): Service {
        app(TenantContext::class)->setTenantId($tenant->id);

        $targetId = $service instanceof Service ? $service->id : (int) $service;
        $layanan = Service::withoutGlobalScopes()
            ->where('idtenant', $tenant->id)
            ->findOrFail($targetId);

        $imageUrl = $layanan->image_url;

        if ($coverImage) {
            if ($layanan->image_url && !str_starts_with($layanan->image_url, 'http') && Storage::disk('public')->exists($layanan->image_url)) {
                Storage::disk('public')->delete($layanan->image_url);
            }
            $imageUrl = $coverImage->store('programs', 'public');
        } elseif ($removeImage) {
            if ($layanan->image_url && !str_starts_with($layanan->image_url, 'http') && Storage::disk('public')->exists($layanan->image_url)) {
                Storage::disk('public')->delete($layanan->image_url);
            }
            $imageUrl = null;
        }

        $layanan->update([
            'namalayanan' => $data['namalayanan'],
            'harga'       => $data['harga'],
            'durasi'      => $data['durasi'],
            'idcategory'  => $data['idcategory'] ?? null,
            'deskripsi'   => $data['deskripsi'] ?? null,
            'is_active'   => (bool) ($data['is_active'] ?? true),
            'image_url'   => $imageUrl,
        ]);

        if (array_key_exists('staff_ids', $data)) {
            $layanan->staff()->sync($data['staff_ids'] ?? []);
        }
        if (array_key_exists('resource_ids', $data)) {
            $layanan->resources()->sync($data['resource_ids'] ?? []);
        }
        if (array_key_exists('additional_item_ids', $data)) {
            $layanan->additionalItems()->sync($data['additional_item_ids'] ?? []);
        }

        $this->clearServiceCache($tenant->id, $layanan->id);

        return $layanan;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Service;

use App\Actions\Schedule\ClaimSlots;
use App\Actions\Unit\AssignServiceUnits;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantActor;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateService
{
    use ClearsBookingCache;

    /**
     * Update an existing service and its relationships.
     *
     * @param  array<string, mixed>  $data
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
        // scope-bypass: filtered by the authenticated tenant
        $layanan = Service::withoutGlobalScopes()
            ->where('idtenant', $tenant->id)
            ->findOrFail($targetId);

        $imageUrl = $layanan->image_url;
        $resolveImage = app(ResolveServiceImage::class);

        // BQ-142 (FR-ASSET-005): a gallery pick or a new upload into the gallery replaces the image.
        // Gallery files belong to the gallery; only a pre-gallery file of this service is removed.
        if ($coverImage || ! empty($data['image_asset_id'])) {
            $resolveImage->discardLegacyFile($tenant, $layanan->image_url);
            $imageUrl = $resolveImage->execute($tenant, $data['image_asset_id'] ?? null, $coverImage);
        } elseif ($removeImage) {
            $resolveImage->discardLegacyFile($tenant, $layanan->image_url);
            $imageUrl = null;
        }

        // Eligibility changes share the tenant-first protocol with slot claims (ADR-0005 decision 4)
        DB::transaction(function () use ($tenant, $layanan, $data, $imageUrl) {
            ClaimSlots::lockTenant((int) $tenant->id);
            $layanan->update([
                'namalayanan' => $data['namalayanan'],
                'harga' => $data['harga'],
                'durasi' => $data['durasi'],
                'idcategory' => $data['idcategory'] ?? null,
                'deskripsi' => $data['deskripsi'] ?? null,
                'satuan_harga' => ($data['satuan_harga'] ?? null) ?: $layanan->satuan_harga,
                'satuan_durasi' => ($data['satuan_durasi'] ?? null) ?: $layanan->satuan_durasi,
                'is_popular' => array_key_exists('is_popular', $data) ? (bool) $data['is_popular'] : $layanan->is_popular,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'image_url' => $imageUrl,
                'kapasitas' => array_key_exists('kapasitas', $data) && $data['kapasitas'] !== null ? (int) $data['kapasitas'] : $layanan->kapasitas,
            ]);

            if (array_key_exists('unit_ids', $data)) {
                app(AssignServiceUnits::class)->execute($tenant, $layanan, $data['unit_ids'] ?? []);
            }

            $resources = $layanan->resources()->sync($data['resource_ids'] ?? []);
            $items = [];
            if (array_key_exists('additional_item_ids', $data)) {
                $items = $layanan->additionalItems()->sync($data['additional_item_ids'] ?? []);
            }
            if (array_filter($resources) || array_filter($items)) {
                app(TenantActor::class)->record($layanan, 'assignments_updated');
            }
        });

        $this->clearServiceCache($tenant->id, $layanan->id);

        return $layanan;
    }
}

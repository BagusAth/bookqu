<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Actions\Tenant\GetStorefrontTenant;
use App\Models\Asset;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Traits\ClearsBookingCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Deletes an image from the business's gallery (FR-ASSET-005). An image in use (logo, cover or a
 * service image) is deleted only after the owner confirms; its references are then cleared.
 */
class DeleteMediaAsset
{
    use ClearsBookingCache;

    /**
     * @throws ValidationException when the image is in use and the deletion is not confirmed
     */
    public function execute(Tenant $tenant, int $assetId, bool $confirmed): string
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $asset = Asset::where('idtenant', $tenant->id)->findOrFail($assetId);
        $usage = $this->usage($tenant, $asset);

        if ($usage !== [] && ! $confirmed) {
            throw ValidationException::withMessages([
                'asset' => 'Gambar "'.$asset->title.'" masih dipakai sebagai '.implode(', ', $usage).'. Konfirmasi untuk menghapus dan melepasnya.',
            ]);
        }

        DB::transaction(function () use ($tenant, $asset): void {
            $tenant->forceFill([
                'logo_path' => $tenant->logo_path === $asset->file_path ? null : $tenant->logo_path,
                'banner_path' => $tenant->banner_path === $asset->file_path ? null : $tenant->banner_path,
            ])->save();
            Service::where('idtenant', $tenant->id)->where('image_url', $asset->file_path)->update(['image_url' => null]);
            $asset->delete();
        });

        Storage::disk('public')->delete($asset->file_path);
        app(GetStorefrontTenant::class)->forget((int) $tenant->id);
        $this->clearActiveServicesCache((int) $tenant->id);

        return (string) $asset->title;
    }

    /**
     * Where the image is used, in words for the confirmation.
     *
     * @return list<string>
     */
    public function usage(Tenant $tenant, Asset $asset): array
    {
        $usage = [];
        if ($tenant->logo_path === $asset->file_path) {
            $usage[] = 'logo';
        }
        if ($tenant->banner_path === $asset->file_path) {
            $usage[] = 'sampul';
        }
        $services = Service::where('idtenant', $tenant->id)->where('image_url', $asset->file_path)->count();
        if ($services > 0) {
            $usage[] = $services.' gambar layanan';
        }

        return $usage;
    }
}

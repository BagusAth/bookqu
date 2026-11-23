<?php

declare(strict_types=1);

namespace App\Actions\Service;

use App\Actions\Media\StoreMediaAsset;
use App\Models\Asset;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The service image through the media gallery (BQ-142, FR-ASSET-005, AC-HBK-05): a picked image must
 * be this business's own; an uploaded image is stored in the gallery (category `service`) first.
 */
class ResolveServiceImage
{
    public function __construct(private StoreMediaAsset $storeMediaAsset) {}

    /**
     * @return string|null the public-disk path of the image to use
     */
    public function execute(Tenant $tenant, mixed $assetId, ?UploadedFile $upload): ?string
    {
        if ($upload !== null) {
            return $this->storeMediaAsset->execute($tenant, $upload, null, 'service')->file_path;
        }
        if ($assetId !== null && $assetId !== '') {
            return Asset::where('idtenant', $tenant->id)->findOrFail((int) $assetId)->file_path;
        }

        return null;
    }

    /**
     * Removes an image file uploaded before the gallery existed. Files of the gallery stay.
     */
    public function discardLegacyFile(Tenant $tenant, ?string $path): void
    {
        if ($path === null || $path === '' || str_starts_with($path, 'http')) {
            return;
        }
        if (Asset::where('idtenant', $tenant->id)->where('file_path', $path)->exists()) {
            return;
        }
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

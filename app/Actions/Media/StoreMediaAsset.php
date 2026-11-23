<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Asset;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;

/**
 * Puts an image into the business's media gallery (FR-ASSET-005, AC-HBK-05). Files are stored under
 * the business's own folder on the public disk; every image field of the owner portal picks from or
 * uploads into this gallery.
 */
class StoreMediaAsset
{
    public const CATEGORIES = ['logo' => 'Logo', 'cover' => 'Sampul', 'service' => 'Layanan', 'gallery' => 'Galeri', 'other' => 'Lainnya'];

    public const MAX_KILOBYTES = 10240;

    public function execute(Tenant $tenant, UploadedFile $file, ?string $title = null, ?string $category = null): Asset
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $category = array_key_exists((string) $category, self::CATEGORIES) ? (string) $category : 'other';
        $title = trim((string) $title) !== '' ? trim((string) $title) : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $dimensions = @getimagesize($file->getRealPath());

        return Asset::create([
            'idtenant' => $tenant->id,
            'title' => mb_substr($title, 0, 150),
            'category' => $category,
            'file_path' => $file->store("tenants/{$tenant->id}/assets", 'public'),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'dimensions' => is_array($dimensions) ? $dimensions[0].'x'.$dimensions[1] : null,
        ]);
    }
}

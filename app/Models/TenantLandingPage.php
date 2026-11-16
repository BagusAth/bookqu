<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Database\Factories\TenantLandingPageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The landing page of a Pro business (BQ-154, FR-PUBLIC-007): shown on the storefront home once
 * published, while the business stays on Pro.
 *
 * @property int $id
 * @property int $idtenant
 * @property string $template
 * @property string|null $headline
 * @property list<string>|null $sections
 * @property int|null $banner_asset_id
 * @property Carbon|null $published_at
 */
class TenantLandingPage extends Model
{
    /** @use HasFactory<TenantLandingPageFactory> */
    use BelongsToTenant, HasFactory;

    public const TEMPLATES = [
        'modern' => 'Modern Business',
        'creative' => 'Creative Studio',
        'clinic' => 'Minimalist Clinic',
    ];

    public const SECTIONS = [
        'tentang' => 'Tentang kami',
        'layanan' => 'Layanan unggulan',
        'kontak' => 'Lokasi & kontak',
    ];

    protected $fillable = [
        'idtenant',
        'template',
        'headline',
        'sections',
        'banner_asset_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'idtenant');
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'banner_asset_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Asset;
use App\Models\Tenant;
use App\Support\TenantContext;

class UpdateAppearanceSettings
{
    /**
     * Update the public look of the booking page (owner-portal.md §8.9 Tampilan, D-009).
     * Logo and cover come from the business's own gallery (FR-ASSET-005, AC-HBK-05); the caller has
     * validated the asset ids against this tenant.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Tenant $tenant, array $data): Tenant
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $attributes = [
            'theme_color' => $data['theme_color'] ?? $tenant->theme_color,
            'button_style' => $data['button_style'] ?? $tenant->button_style,
            'font_family' => $data['font_family'] ?? $tenant->font_family,
            'card_style' => $data['card_style'] ?? $tenant->card_style,
            'deskripsi' => array_key_exists('deskripsi', $data) ? $data['deskripsi'] : $tenant->deskripsi,
        ];

        foreach (['logo_asset_id' => 'logo_path', 'cover_asset_id' => 'banner_path'] as $input => $column) {
            if (! array_key_exists($input, $data)) {
                continue;
            }
            $attributes[$column] = $data[$input] === null || $data[$input] === ''
                ? null
                : Asset::where('idtenant', $tenant->id)->findOrFail((int) $data[$input])->file_path;
        }

        $tenant->forceFill($attributes)->save();

        app(GetStorefrontTenant::class)->forget((int) $tenant->id);

        return $tenant;
    }
}

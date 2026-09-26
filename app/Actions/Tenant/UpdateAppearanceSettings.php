<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Support\TenantContext;

class UpdateAppearanceSettings
{
    /**
     * Update tenant public appearance and theme preferences.
     *
     * @param array<string, mixed> $data
     */
    public function execute(Tenant $tenant, array $data): Tenant
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $tenant->update([
            'theme_color'  => $data['theme_color'] ?? $tenant->theme_color,
            'button_style' => $data['button_style'] ?? $tenant->button_style,
            'font_family'  => $data['font_family'] ?? $tenant->font_family,
            'card_style'   => $data['card_style'] ?? $tenant->card_style,
            'deskripsi'    => $data['deskripsi'] ?? $tenant->deskripsi,
        ]);

        return $tenant;
    }
}

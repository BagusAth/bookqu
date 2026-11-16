<?php

declare(strict_types=1);

namespace App\Actions\Integrations;

use App\Models\OwnerIntegration;
use App\Models\Tenant;

final class SaveStorefrontTracking
{
    /** @param array{google_analytics_id?: ?string, meta_pixel_id?: ?string} $settings */
    public function execute(Tenant $tenant, array $settings): void
    {
        OwnerIntegration::firstOrCreate(['idtenant' => $tenant->id])->update($settings);
    }
}

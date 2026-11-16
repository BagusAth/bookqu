<?php

declare(strict_types=1);

namespace App\Actions\Integrations;

use App\Domain\Modules\ModuleRelease;
use App\Domain\Subscription\EntitlementRules;
use App\Infrastructure\Integrations\GoogleCalendar;
use App\Models\OwnerIntegration;
use App\Models\Tenant;

final class GetOwnerIntegrations
{
    /** @return array{connected: bool, configured: bool, googleAnalytics: ?string, metaPixel: ?string, apiUrl: ?string} */
    public function execute(Tenant $tenant, GoogleCalendar $calendar): array
    {
        $settings = OwnerIntegration::where('idtenant', $tenant->id)->first();

        return [
            'connected' => $settings?->getRawOriginal('google_refresh_token') !== null,
            'configured' => $calendar->configured(),
            'googleAnalytics' => $settings?->google_analytics_id,
            'metaPixel' => $settings?->meta_pixel_id,
            'apiUrl' => ModuleRelease::state('api') === 'released'
                && EntitlementRules::canAccessFeature($tenant->subscription()->with('plan')->first(), 'api')['allowed']
                ? route('owner.api.index') : null,
        ];
    }

    /** @return array{googleAnalytics: ?string, metaPixel: ?string}|null */
    public function storefront(Tenant $tenant): ?array
    {
        if (ModuleRelease::state('integrations') !== 'released'
            || ! EntitlementRules::canAccessFeature($tenant->subscription()->with('plan')->first(), 'integrations')['allowed']) {
            return null;
        }

        $settings = OwnerIntegration::where('idtenant', $tenant->id)->first(['google_analytics_id', 'meta_pixel_id']);

        return $settings ? ['googleAnalytics' => $settings->google_analytics_id, 'metaPixel' => $settings->meta_pixel_id] : null;
    }
}

<?php

namespace App\Actions\Tenant;

use App\Domain\Subscription\EntitlementRules;
use App\Domain\Subscription\PlanCapability;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Cache;
use PDOException;

final class GetStorefrontTenant
{
    private const FIELDS = [
        'id', 'iduser', 'namabisnis', 'slug', 'custom_domain', 'jenisbisnis', 'alamat',
        'deskripsi', 'logo_path', 'banner_path', 'nomorhp', 'weekend_price_type',
        'weekend_price_value', 'cancel_before_hours', 'reschedule_before_hours',
        'refunds_enabled', 'booking_min_lead_hours', 'booking_max_days_ahead', 'booking_buffer_minutes',
        'theme_color', 'button_style', 'font_family', 'card_style',
    ];

    public function execute(int $tenantId): ?Tenant
    {
        $data = Cache::remember($this->key($tenantId), 3600, fn () => Tenant::query()->select(self::FIELDS)->find($tenantId)?->getAttributes()
        );

        return is_array($data) ? Tenant::hydrate([$data])->first() : null;
    }

    public function forget(int $tenantId): void
    {
        Cache::forget($this->key($tenantId));
    }

    public function knownSlug(string $candidate): ?string
    {
        return Tenant::query()->where('slug', $candidate)->value('slug');
    }

    public function canBrandError(Tenant $tenant, bool $customDomain): bool
    {
        try {
            // Errors may render after TenantContext clears; the middleware-resolved tenant is explicit.
            $subscription = app(TenantContext::class)->run((int) $tenant->id, fn (): ?Subscription => Subscription::query()
                ->where('idtenant', $tenant->id)->with('plan')->latest('id')->first());
        } catch (PDOException) {
            return false;
        }

        return $customDomain
            ? EntitlementRules::canAccessFeature($subscription, PlanCapability::FEATURE_LANDING_PAGE)['allowed']
            : EntitlementRules::hasEffectivePlan($subscription);
    }

    private function key(int $tenantId): string
    {
        return 'tenant:public:v2:'.$tenantId;
    }
}

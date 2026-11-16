<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Domain\Subscription\EntitlementRules;
use App\Domain\Tenant\CustomDomain;
use App\Infrastructure\Dns\DnsLookup;
use App\Models\Subscription;
use App\Models\Tenant;

/**
 * Checks a business's own domain and records the result (BQ-154, AC-HBK-04). It connects only when
 * the domain points at the platform, its `_bookqu` TXT record carries the business's token, and the
 * business still has the Pro capability. The storefront answers only while the status is `connected`.
 */
class CheckCustomDomain
{
    public function __construct(
        private readonly DnsLookup $dns,
        private readonly GetStorefrontTenant $storefrontTenant,
    ) {}

    /**
     * @return array{status: ?string, cnames: list<string>, ipv4: list<string>, configured: bool, points: bool, owned: bool, pro: bool}
     */
    public function execute(Tenant $tenant): array
    {
        $domain = (string) $tenant->custom_domain;
        if ($domain === '') {
            return ['status' => null, 'cnames' => [], 'ipv4' => [], 'configured' => $this->configured(), 'points' => false, 'owned' => false, 'pro' => false];
        }

        $cnames = $this->dns->cnameTargets($domain);
        $ipv4 = $this->dns->ipv4($domain);
        $points = CustomDomain::pointsToPlatform($cnames, $ipv4, $this->target(), $this->addresses());
        $owned = CustomDomain::ownershipProven($this->dns->txt(CustomDomain::verificationHost($domain)), $tenant->custom_domain_token);
        // A business below Pro keeps its domain saved but it stops resolving (Tech Lead 2026-11-13).
        $subscription = Subscription::with('plan')->where('idtenant', $tenant->id)->latest('id')->first();
        $pro = EntitlementRules::canAccessFeature($subscription, 'landing-page')['allowed'];
        $connected = $points && $owned && $pro;
        $status = $connected ? CustomDomain::STATUS_CONNECTED : CustomDomain::STATUS_NOT_CONNECTED;

        // The domain may have changed since this check was queued; only its own row is updated.
        Tenant::query()->whereKey($tenant->id)->where('custom_domain', $domain)->update([
            'custom_domain_status' => $status,
            'custom_domain_checked_at' => now(),
            'custom_domain_verified_at' => $connected ? ($tenant->custom_domain_verified_at ?? now()) : null,
        ]);
        $this->storefrontTenant->forget((int) $tenant->id);

        return ['status' => $status, 'cnames' => $cnames, 'ipv4' => $ipv4, 'configured' => $this->configured(), 'points' => $points, 'owned' => $owned, 'pro' => $pro];
    }

    public function configured(): bool
    {
        return $this->target() !== '' || $this->addresses() !== [];
    }

    public function target(): string
    {
        return CustomDomain::normalize((string) config('custom-domains.cname_target', ''));
    }

    /**
     * @return list<string>
     */
    public function addresses(): array
    {
        $addresses = config('custom-domains.a_records', []);

        return is_array($addresses) ? array_values(array_filter($addresses, 'is_string')) : [];
    }
}

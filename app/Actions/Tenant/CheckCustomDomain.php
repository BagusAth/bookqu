<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Domain\Tenant\CustomDomain;
use App\Infrastructure\Dns\DnsLookup;
use App\Models\Tenant;

/**
 * Checks where a business's own domain points and records the result (BQ-154, AC-HBK-04). The
 * storefront answers on the domain only while the recorded status is `connected`.
 */
class CheckCustomDomain
{
    public function __construct(
        private readonly DnsLookup $dns,
        private readonly GetStorefrontTenant $storefrontTenant,
    ) {}

    /**
     * @return array{status: ?string, cnames: list<string>, ipv4: list<string>, configured: bool}
     */
    public function execute(Tenant $tenant): array
    {
        $domain = (string) $tenant->custom_domain;
        if ($domain === '') {
            return ['status' => null, 'cnames' => [], 'ipv4' => [], 'configured' => $this->configured()];
        }

        $cnames = $this->dns->cnameTargets($domain);
        $ipv4 = $this->dns->ipv4($domain);
        $connected = CustomDomain::pointsToPlatform($cnames, $ipv4, $this->target(), $this->addresses());
        $status = $connected ? CustomDomain::STATUS_CONNECTED : CustomDomain::STATUS_NOT_CONNECTED;

        // The domain may have changed since this check was queued; only its own row is updated.
        Tenant::query()->whereKey($tenant->id)->where('custom_domain', $domain)->update([
            'custom_domain_status' => $status,
            'custom_domain_checked_at' => now(),
            'custom_domain_verified_at' => $connected ? ($tenant->custom_domain_verified_at ?? now()) : null,
        ]);
        $this->storefrontTenant->forget((int) $tenant->id);

        return ['status' => $status, 'cnames' => $cnames, 'ipv4' => $ipv4, 'configured' => $this->configured()];
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

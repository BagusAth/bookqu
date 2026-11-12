<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Domain\Tenant\CustomDomain;
use App\Jobs\CheckCustomDomainJob;
use App\Models\Tenant;

/**
 * Saves or removes a business's own domain (BQ-154). A new domain starts as `pending`, so it does
 * not resolve until its DNS check connects it; the check is queued right away.
 */
class UpdateCustomDomain
{
    public function __construct(private readonly GetStorefrontTenant $storefrontTenant) {}

    /**
     * @param  string|null  $domain  already normalised and validated
     */
    public function execute(Tenant $tenant, ?string $domain): void
    {
        $domain = $domain === null || $domain === '' ? null : $domain;

        if ($domain !== $tenant->custom_domain) {
            $tenant->forceFill([
                'custom_domain' => $domain,
                'custom_domain_status' => $domain === null ? null : CustomDomain::STATUS_PENDING,
                'custom_domain_checked_at' => null,
                'custom_domain_verified_at' => null,
            ])->save();
            $this->storefrontTenant->forget((int) $tenant->id);
        }

        if ($domain !== null) {
            CheckCustomDomainJob::dispatch((int) $tenant->id, $domain)->afterCommit();
        }
    }
}

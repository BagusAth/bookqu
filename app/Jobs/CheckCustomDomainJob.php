<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Tenant\CheckCustomDomain;
use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs the DNS check of a business's own domain after it is saved (BQ-154).
 */
class CheckCustomDomainJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $tenantId, public string $domain) {}

    public function handle(CheckCustomDomain $checkCustomDomain): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if ($tenant instanceof Tenant && $tenant->custom_domain === $this->domain) {
            $checkCustomDomain->execute($tenant);
        }
    }
}

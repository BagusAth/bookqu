<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use Closure;

class TenantContext
{
    private ?int $tenantId = null;

    public function setTenantId(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }

    public function clear(): void
    {
        $this->tenantId = null;
    }

    /**
     * Run work for a tenant already verified by the caller.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $operation
     * @return TResult
     */
    public function run(int $tenantId, Closure $operation): mixed
    {
        $previous = $this->tenantId;
        $this->tenantId = $tenantId;

        try {
            return $operation();
        } finally {
            $this->tenantId = $previous;
        }
    }

    public function tenant(): ?Tenant
    {
        if ($this->tenantId === null) {
            return null;
        }

        return Tenant::find($this->tenantId);
    }
}

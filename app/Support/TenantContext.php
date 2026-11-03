<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;

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

    public function tenant(): ?Tenant
    {
        if ($this->tenantId === null) {
            return null;
        }

        // scope-bypass: resolves the context tenant itself by id
        return Tenant::withoutGlobalScopes()->find($this->tenantId);
    }
}

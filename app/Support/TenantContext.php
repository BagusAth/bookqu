<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;

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

    public function tenant(): ?\App\Models\Tenant
    {
        if ($this->tenantId === null) {
            return null;
        }

        return \App\Models\Tenant::withoutGlobalScopes()->find($this->tenantId);
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;

class TenantContext
{
    /**
     * @var int|null
     */
    private static ?int $tenantId = null;

    /**
     * Set the current tenant context.
     *
     * @param int|null $tenantId
     */
    public static function setTenantId(?int $tenantId): void
    {
        self::$tenantId = $tenantId;
    }

    /**
     * Get the current tenant context.
     *
     * @return int|null
     */
    public static function getTenantId(): ?int
    {
        return self::$tenantId;
    }

    /**
     * Check if a tenant context is active.
     *
     * @return bool
     */
    public static function hasTenant(): bool
    {
        return self::$tenantId !== null;
    }

    /**
     * Clear the current tenant context.
     */
    public static function clear(): void
    {
        self::$tenantId = null;
    }
}

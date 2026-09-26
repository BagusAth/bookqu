<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\Schedule\ScheduleAvailabilityCache;

trait ClearsBookingCache
{
    protected function getAvailabilityCacheService(): ScheduleAvailabilityCache
    {
        return app(ScheduleAvailabilityCache::class);
    }

    /**
     * Get the cache key for active services of a tenant.
     */
    protected function getActiveServicesCacheKey(int $tenantId): string
    {
        return $this->getAvailabilityCacheService()->getActiveServicesCacheKey($tenantId);
    }

    /**
     * Get the cache key for a specific service.
     */
    protected function getServiceCacheKey(int $tenantId, int $serviceId): string
    {
        return $this->getAvailabilityCacheService()->getServiceCacheKey($tenantId, $serviceId);
    }

    /**
     * Get the cache key for availability of a service (today → +30 days window).
     */
    protected function getAvailabilityCacheKey(int $tenantId, int $serviceId): string
    {
        return $this->getAvailabilityCacheService()->getAvailabilityCacheKey($tenantId, $serviceId);
    }

    /**
     * Get the cache key for schedules of a specific date.
     */
    protected function getSchedulesCacheKey(int $tenantId, int $serviceId, string $date): string
    {
        return $this->getAvailabilityCacheService()->getSchedulesCacheKey($tenantId, $serviceId, $date);
    }

    /**
     * Clear all cache related to a service.
     */
    protected function clearServiceCache(int $tenantId, int $serviceId): void
    {
        $this->getAvailabilityCacheService()->clearServiceCache($tenantId, $serviceId);
    }

    /**
     * Clear the broad availability cache for a service (date-range level).
     */
    protected function clearAvailabilityCache(int $tenantId, int $serviceId): void
    {
        $this->getAvailabilityCacheService()->clearAvailabilityCache($tenantId, $serviceId);
    }

    /**
     * Clear cache when schedules are updated (created/deleted/modified).
     */
    protected function clearScheduleCache(int $tenantId, int $serviceId, array $dates): void
    {
        $this->getAvailabilityCacheService()->clearScheduleCache($tenantId, $serviceId, $dates);
    }

    /**
     * Clear active services cache for a tenant.
     */
    protected function clearActiveServicesCache(int $tenantId): void
    {
        $this->getAvailabilityCacheService()->clearActiveServicesCache($tenantId);
    }

    /**
     * Clear availability cache for all services of a tenant.
     */
    protected function clearAllServicesAvailability(int $tenantId): void
    {
        $this->getAvailabilityCacheService()->clearAllServicesAvailability($tenantId);
    }

    /**
     * Clear both the per-date schedule cache and the broad availability cache.
     */
    protected function clearBookingAvailabilityCache(int $tenantId, int $serviceId, ?string $date = null): void
    {
        $this->getAvailabilityCacheService()->clearBookingAvailabilityCache($tenantId, $serviceId, $date);
    }
}

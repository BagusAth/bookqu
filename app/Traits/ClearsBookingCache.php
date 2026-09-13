<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

trait ClearsBookingCache
{
    /**
     * Get the cache key for active services of a tenant.
     */
    protected function getActiveServicesCacheKey(int $tenantId): string
    {
        return "tenant:{$tenantId}:services:active";
    }

    /**
     * Get the cache key for a specific service.
     */
    protected function getServiceCacheKey(int $tenantId, int $serviceId): string
    {
        return "tenant:{$tenantId}:service:{$serviceId}";
    }

    /**
     * Get the cache key for availability of a service.
     * NOTE: The key is computed with today's date as the base window (today → +30 days),
     * matching exactly how showDateSelection builds it.
     */
    protected function getAvailabilityCacheKey(int $tenantId, int $serviceId): string
    {
        $minDate = Carbon::today()->toDateString();
        $maxDate = Carbon::today()->addDays(30)->toDateString();
        return "tenant:{$tenantId}:service:{$serviceId}:availability:{$minDate}:{$maxDate}";
    }

    /**
     * Get the cache key for schedules of a specific date.
     */
    protected function getSchedulesCacheKey(int $tenantId, int $serviceId, string $date): string
    {
        return "tenant:{$tenantId}:service:{$serviceId}:schedules:{$date}";
    }

    /**
     * Clear all cache related to a service (when updating/deleting service).
     */
    protected function clearServiceCache(int $tenantId, int $serviceId): void
    {
        Cache::forget($this->getActiveServicesCacheKey($tenantId));
        Cache::forget($this->getServiceCacheKey($tenantId, $serviceId));
        // We might also want to clear availability just in case, though usually
        // availability changes when schedules change.
        Cache::forget($this->getAvailabilityCacheKey($tenantId, $serviceId));
    }

    /**
     * Clear the broad availability cache for a service (date-range level).
     * Call this whenever a booking status changes and affects slot availability.
     */
    protected function clearAvailabilityCache(int $tenantId, int $serviceId): void
    {
        Cache::forget($this->getAvailabilityCacheKey($tenantId, $serviceId));
    }

    /**
     * Clear cache when schedules are updated (created/deleted/modified).
     */
    protected function clearScheduleCache(int $tenantId, int $serviceId, array $dates): void
    {
        Cache::forget($this->getAvailabilityCacheKey($tenantId, $serviceId));

        foreach (array_unique($dates) as $date) {
            Cache::forget($this->getSchedulesCacheKey($tenantId, $serviceId, $date));
        }
    }

    /**
     * Convenience: clear both the per-date schedule cache and the broad availability cache.
     * Use this after any booking status transition that affects slot availability
     * (pending→paid, pending→cancelled, paid→completed, etc.)
     *
     * @param int    $tenantId
     * @param int    $serviceId
     * @param string $date      Y-m-d
     */
    protected function clearBookingAvailabilityCache(int $tenantId, int $serviceId, string $date): void
    {
        Cache::forget($this->getSchedulesCacheKey($tenantId, $serviceId, $date));
        Cache::forget($this->getAvailabilityCacheKey($tenantId, $serviceId));
    }
}


<?php

declare(strict_types=1);

namespace App\Services\Schedule;

use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ScheduleAvailabilityCache
{
    /**
     * Get the cache key for active services of a tenant.
     */
    public function getActiveServicesCacheKey(int $tenantId): string
    {
        return "tenant:{$tenantId}:services:active";
    }

    /**
     * Get the cache key for a specific service.
     */
    public function getServiceCacheKey(int $tenantId, int $serviceId): string
    {
        return "tenant:{$tenantId}:service:{$serviceId}";
    }

    /**
     * Get the cache key for availability of a service (today → +30 days window).
     */
    public function getAvailabilityCacheKey(int $tenantId, int $serviceId): string
    {
        $minDate = Carbon::today('Asia/Jakarta')->toDateString();
        $maxDate = Carbon::today('Asia/Jakarta')->addDays(30)->toDateString();

        return "tenant:{$tenantId}:service:{$serviceId}:availability:{$minDate}:{$maxDate}";
    }

    /**
     * Get the cache key for schedules of a specific date.
     */
    public function getSchedulesCacheKey(int $tenantId, int $serviceId, string $date): string
    {
        return "tenant:{$tenantId}:service:{$serviceId}:schedules:{$date}";
    }

    /**
     * Clear all cache related to a service.
     */
    public function clearServiceCache(int $tenantId, int $serviceId): void
    {
        Cache::forget($this->getActiveServicesCacheKey($tenantId));
        Cache::forget($this->getServiceCacheKey($tenantId, $serviceId));
        Cache::forget($this->getAvailabilityCacheKey($tenantId, $serviceId));
    }

    /**
     * Clear the broad availability cache for a service (date-range level).
     */
    public function clearAvailabilityCache(int $tenantId, int $serviceId): void
    {
        Cache::forget($this->getAvailabilityCacheKey($tenantId, $serviceId));
    }

    /**
     * Clear cache when schedules are updated (created/deleted/modified).
     */
    public function clearScheduleCache(int $tenantId, int $serviceId, array $dates): void
    {
        Cache::forget($this->getAvailabilityCacheKey($tenantId, $serviceId));

        foreach (array_unique($dates) as $date) {
            Cache::forget($this->getSchedulesCacheKey($tenantId, $serviceId, $date));
        }
    }

    /**
     * Clear active services cache for a tenant.
     */
    public function clearActiveServicesCache(int $tenantId): void
    {
        Cache::forget($this->getActiveServicesCacheKey($tenantId));
    }

    /**
     * Clear availability cache for all services of a tenant.
     */
    public function clearAllServicesAvailability(int $tenantId): void
    {
        $serviceIds = Service::withoutGlobalScopes()
            ->where('idtenant', $tenantId)
            ->pluck('id');

        foreach ($serviceIds as $sId) {
            Cache::forget($this->getAvailabilityCacheKey($tenantId, (int) $sId));
        }
    }

    /**
     * Clear both the per-date schedule cache and the broad availability cache.
     */
    public function clearBookingAvailabilityCache(int $tenantId, int $serviceId, ?string $date = null): void
    {
        if ($date) {
            Cache::forget($this->getSchedulesCacheKey($tenantId, $serviceId, $date));
        }
        Cache::forget($this->getAvailabilityCacheKey($tenantId, $serviceId));
    }
}

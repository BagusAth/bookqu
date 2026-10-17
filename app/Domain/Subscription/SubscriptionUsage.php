<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

use App\Domain\Booking\BookingState;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;

final class SubscriptionUsage
{
    /**
     * Calculate percentage safely (0 - 100).
     */
    public static function calculatePercentage(int $used, int $limit, bool $isUnlimited = false): int
    {
        if ($isUnlimited || $limit <= 0) {
            return 0;
        }

        return (int) min(100, round(($used / $limit) * 100));
    }

    /**
     * Count services currently active or belonging to the tenant.
     */
    public static function countServices(int $tenantId): int
    {
        return Service::withoutGlobalScopes()->where('idtenant', $tenantId)->count();
    }

    /**
     * Count staff currently configured for the tenant.
     */
    public static function countStaff(int $tenantId): int
    {
        return Staff::withoutGlobalScopes()->where('idtenant', $tenantId)->count();
    }

    /**
     * Count non-cancelled bookings for tenant within the specified month/year.
     */
    public static function countMonthlyBookings(int $tenantId, ?Carbon $date = null): int
    {
        $date = $date ?? Carbon::now();

        return Booking::withoutGlobalScopes()
            ->where('idtenant', $tenantId)
            ->whereYear('tanggalbooking', $date->year)
            ->whereMonth('tanggalbooking', $date->month)
            ->whereIn('status', [BookingState::STATUS_PENDING, BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED])
            ->count();
    }
}

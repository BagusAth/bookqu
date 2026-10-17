<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

use App\Models\Plan;

final class PlanCapability
{
    public const PLAN_SMALL  = 'small';
    public const PLAN_MEDIUM = 'medium';
    public const PLAN_PRO    = 'pro';

    public const LEVEL_SMALL  = 1;
    public const LEVEL_MEDIUM = 2;
    public const LEVEL_PRO    = 3;

    public const FEATURE_ANALYTICS        = 'analytics';
    public const FEATURE_ANALYTICS_EXPORT = 'analytics.export';
    public const FEATURE_LANDING_PAGE     = 'landing-page';

    /**
     * Map plan string identifier to numeric level.
     */
    public static function getPlanLevel(string $planName): int
    {
        return match (strtolower(trim($planName))) {
            self::PLAN_SMALL  => self::LEVEL_SMALL,
            self::PLAN_MEDIUM => self::LEVEL_MEDIUM,
            self::PLAN_PRO    => self::LEVEL_PRO,
            default           => self::LEVEL_SMALL,
        };
    }

    /**
     * Determine staff limit based on plan name, trial state, or unlimited flag.
     * Returns 0 for unlimited staff.
     */
    public static function getStaffLimit(string $planName, bool $isTrial = false, bool $isUnlimited = false): int
    {
        $normalized = strtolower(trim($planName));
        if ($isTrial || $isUnlimited || $normalized === self::PLAN_PRO) {
            return 0;
        }

        return match ($normalized) {
            self::PLAN_SMALL  => 2,
            self::PLAN_MEDIUM => 15,
            self::PLAN_PRO    => 0,
            default           => 2,
        };
    }

    /**
     * Determine service limit for a plan.
     * Returns 0 for unlimited services.
     */
    public static function getServiceLimit(?Plan $plan, bool $isTrial = false): int
    {
        if ($isTrial || $plan === null) {
            return 0;
        }

        $planName = strtolower(trim((string) $plan->namapaket));
        if ($plan->isunlimited || $planName === self::PLAN_PRO) {
            return 0;
        }

        return (int) ($plan->maxlayanan ?? 0);
    }

    /**
     * Determine monthly booking limit for a plan.
     * Returns 0 for unlimited bookings.
     */
    public static function getBookingLimit(?Plan $plan, bool $isTrial = false): int
    {
        if ($isTrial || $plan === null) {
            return 0;
        }

        $planName = strtolower(trim((string) $plan->namapaket));
        if ($plan->isunlimited || $planName === self::PLAN_PRO) {
            return 0;
        }

        return (int) ($plan->maxbooking ?? 0);
    }

    /**
     * Determine if a given feature is allowed for a plan.
     */
    public static function isFeatureAllowed(string $planName, string $feature, bool $isTrial = false): bool
    {
        if ($isTrial) {
            return true;
        }

        $level = self::getPlanLevel($planName);

        return match (strtolower(trim($feature))) {
            self::FEATURE_ANALYTICS,
            self::FEATURE_ANALYTICS_EXPORT,
            'medium' => $level >= self::LEVEL_MEDIUM,

            self::FEATURE_LANDING_PAGE,
            'pro'    => $level >= self::LEVEL_PRO,

            default  => true,
        };
    }
}

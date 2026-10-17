<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

use App\Models\Subscription;
use Carbon\Carbon;

final class SubscriptionState
{
    public const STATUS_TRIAL     = 'trial';
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Determine if a subscription has expired or is cancelled.
     * Evaluates explicit status as well as elapsed period dates.
     */
    public static function isExpired(?Subscription $subscription, ?Carbon $now = null): bool
    {
        if ($subscription === null) {
            return true;
        }

        $now = $now ?? Carbon::now();

        if (in_array($subscription->status, [self::STATUS_EXPIRED, self::STATUS_CANCELLED], true)) {
            return true;
        }

        if ($subscription->status === self::STATUS_TRIAL) {
            if ($subscription->trial_berakhir !== null && $subscription->trial_berakhir->lessThan($now)) {
                return true;
            }
        }

        if ($subscription->status === self::STATUS_ACTIVE) {
            if ($subscription->langganan_berakhir !== null && $subscription->langganan_berakhir->lessThan($now)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a subscription is currently active (either valid trial or active paid period).
     */
    public static function isActive(?Subscription $subscription, ?Carbon $now = null): bool
    {
        if ($subscription === null) {
            return false;
        }

        if (self::isExpired($subscription, $now)) {
            return false;
        }

        return in_array($subscription->status, [self::STATUS_TRIAL, self::STATUS_ACTIVE], true);
    }

    /**
     * Determine remaining trial days.
     */
    public static function getRemainingTrialDays(?Subscription $subscription, ?Carbon $now = null): int
    {
        if ($subscription === null || $subscription->status !== self::STATUS_TRIAL || $subscription->trial_berakhir === null) {
            return 0;
        }

        $now = $now ?? Carbon::now();
        if ($subscription->trial_berakhir->lessThan($now)) {
            return 0;
        }

        return (int) max(0, ceil($now->diffInDays($subscription->trial_berakhir, false)));
    }

    /**
     * Validate whether a status transition is permitted.
     */
    public static function canTransition(string $fromStatus, string $toStatus): bool
    {
        if ($fromStatus === $toStatus) {
            return true;
        }

        return match ($fromStatus) {
            self::STATUS_TRIAL     => in_array($toStatus, [self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_CANCELLED], true),
            self::STATUS_ACTIVE    => in_array($toStatus, [self::STATUS_EXPIRED, self::STATUS_CANCELLED, self::STATUS_ACTIVE], true),
            self::STATUS_EXPIRED   => in_array($toStatus, [self::STATUS_ACTIVE], true), // re-activation / renewal
            self::STATUS_CANCELLED => in_array($toStatus, [self::STATUS_ACTIVE], true), // re-activation
            default                => false,
        };
    }
}

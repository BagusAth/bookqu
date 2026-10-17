<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

use App\Models\Subscription;

final class EntitlementRules
{
    /**
     * Determine if a tenant's subscription allows access to a specific feature.
     *
     * @return array{allowed: bool, reason: string, message: string}
     */
    public static function canAccessFeature(?Subscription $subscription, string $feature): array
    {
        if ($subscription === null) {
            return [
                'allowed' => false,
                'reason'  => 'no_subscription',
                'message' => 'Silakan berlangganan untuk mengakses fitur ini.',
            ];
        }

        if (SubscriptionState::isExpired($subscription)) {
            return [
                'allowed' => false,
                'reason'  => 'expired',
                'message' => 'Langganan Anda telah habis, mohon perpanjang.',
            ];
        }

        // Active trial grants full access to all features
        if ($subscription->status === SubscriptionState::STATUS_TRIAL) {
            return [
                'allowed' => true,
                'reason'  => 'trial_active',
                'message' => '',
            ];
        }

        $planName = strtolower(trim((string) ($subscription->plan?->namapaket ?? PlanCapability::PLAN_SMALL)));
        $isAllowed = PlanCapability::isFeatureAllowed($planName, $feature, false);

        if (!$isAllowed) {
            $requiredFeature = strtolower(trim($feature));
            $msg = match ($requiredFeature) {
                'pro', PlanCapability::FEATURE_LANDING_PAGE       => 'Fitur ini membutuhkan paket Pro.',
                'medium', PlanCapability::FEATURE_ANALYTICS,
                PlanCapability::FEATURE_ANALYTICS_EXPORT          => 'Fitur ini membutuhkan minimal paket Medium.',
                default                                            => 'Fitur ini tidak tersedia untuk paket Anda.',
            };

            return [
                'allowed' => false,
                'reason'  => 'insufficient_plan',
                'message' => $msg,
            ];
        }

        return [
            'allowed' => true,
            'reason'  => 'entitled',
            'message' => '',
        ];
    }

    /**
     * Check if a tenant can add another staff member given current staff count.
     *
     * @return array{allowed: bool, maxStaff: int, isUnlimited: bool, message: ?string}
     */
    public static function canCreateStaff(?Subscription $subscription, int $currentStaffCount): array
    {
        if ($subscription === null || $subscription->plan === null) {
            return [
                'allowed'     => true,
                'maxStaff'    => 0,
                'isUnlimited' => true,
                'message'     => null,
            ];
        }

        $isTrial = $subscription->status === SubscriptionState::STATUS_TRIAL && !SubscriptionState::isExpired($subscription);
        $planName = strtolower(trim((string) ($subscription->plan->namapaket ?? PlanCapability::PLAN_SMALL)));
        $isUnlimited = (bool) ($subscription->plan->isunlimited ?? false) || $planName === PlanCapability::PLAN_PRO || $isTrial;

        $maxStaff = PlanCapability::getStaffLimit($planName, $isTrial, $isUnlimited);

        if (!$isUnlimited && $maxStaff > 0 && $currentStaffCount >= $maxStaff) {
            return [
                'allowed'     => false,
                'maxStaff'    => $maxStaff,
                'isUnlimited' => false,
                'message'     => 'Batas maksimal staf untuk paket ' . ucfirst($planName) . ' (' . $maxStaff . ' staf) telah tercapai. Silakan upgrade paket Anda.',
            ];
        }

        return [
            'allowed'     => true,
            'maxStaff'    => $maxStaff,
            'isUnlimited' => $isUnlimited,
            'message'     => null,
        ];
    }

    /**
     * Check if a tenant can create another service given current services count.
     *
     * @return array{allowed: bool, maxServices: int, isUnlimited: bool, message: ?string}
     */
    public static function canCreateService(?Subscription $subscription, int $currentServicesCount): array
    {
        if ($subscription === null || $subscription->plan === null) {
            return [
                'allowed'     => true,
                'maxServices' => 0,
                'isUnlimited' => true,
                'message'     => null,
            ];
        }

        $isTrial = $subscription->status === SubscriptionState::STATUS_TRIAL && !SubscriptionState::isExpired($subscription);
        $maxServices = PlanCapability::getServiceLimit($subscription->plan, $isTrial);
        $isUnlimited = $maxServices <= 0;

        if (!$isUnlimited && $currentServicesCount >= $maxServices) {
            return [
                'allowed'     => false,
                'maxServices' => $maxServices,
                'isUnlimited' => false,
                'message'     => 'Batas maksimum layanan (' . $maxServices . ') telah tercapai. Silakan upgrade paket Anda.',
            ];
        }

        return [
            'allowed'     => true,
            'maxServices' => $maxServices,
            'isUnlimited' => $isUnlimited,
            'message'     => null,
        ];
    }

    /**
     * Check if a tenant can accept a new booking without exceeding monthly quota.
     *
     * @return array{allowed: bool, maxBooking: int, remaining: int, isUnlimited: bool, message: ?string}
     */
    public static function canCreateBooking(?Subscription $subscription, int $totalMonthlyBookings, int $requestedSlots = 1): array
    {
        if ($subscription === null || $subscription->plan === null) {
            return [
                'allowed'     => true,
                'maxBooking'  => 0,
                'remaining'   => 999999,
                'isUnlimited' => true,
                'message'     => null,
            ];
        }

        $isTrial = $subscription->status === SubscriptionState::STATUS_TRIAL && !SubscriptionState::isExpired($subscription);
        $maxBooking = PlanCapability::getBookingLimit($subscription->plan, $isTrial);
        $isUnlimited = $maxBooking <= 0;

        if (!$isUnlimited) {
            $remaining = max(0, $maxBooking - $totalMonthlyBookings);
            if (($totalMonthlyBookings + $requestedSlots) > $maxBooking) {
                return [
                    'allowed'     => false,
                    'maxBooking'  => $maxBooking,
                    'remaining'   => $remaining,
                    'isUnlimited' => false,
                    'message'     => 'Kapasitas kuota booking bulanan bisnis ini tidak mencukupi (tersisa ' . $remaining . ' dari ' . $maxBooking . '). Silakan kurangi jumlah slot atau hubungi pemilik bisnis.',
                ];
            }

            return [
                'allowed'     => true,
                'maxBooking'  => $maxBooking,
                'remaining'   => $remaining,
                'isUnlimited' => false,
                'message'     => null,
            ];
        }

        return [
            'allowed'     => true,
            'maxBooking'  => 0,
            'remaining'   => 999999,
            'isUnlimited' => true,
            'message'     => null,
        ];
    }

    public static function isUnlimitedBooking(?Subscription $subscription): bool
    {
        if ($subscription === null || $subscription->plan === null) {
            return true;
        }

        $isTrial = $subscription->status === SubscriptionState::STATUS_TRIAL && !SubscriptionState::isExpired($subscription);
        return PlanCapability::getBookingLimit($subscription->plan, $isTrial) <= 0;
    }

    public static function isUnlimitedStaff(?Subscription $subscription): bool
    {
        if ($subscription === null || $subscription->plan === null) {
            return true;
        }

        $isTrial = $subscription->status === SubscriptionState::STATUS_TRIAL && !SubscriptionState::isExpired($subscription);
        $planName = strtolower(trim((string) ($subscription->plan->namapaket ?? PlanCapability::PLAN_SMALL)));
        $isUnlimited = (bool) ($subscription->plan->isunlimited ?? false);

        return PlanCapability::getStaffLimit($planName, $isTrial, $isUnlimited) <= 0;
    }

    public static function isUnlimitedServices(?Subscription $subscription): bool
    {
        if ($subscription === null || $subscription->plan === null) {
            return true;
        }

        $isTrial = $subscription->status === SubscriptionState::STATUS_TRIAL && !SubscriptionState::isExpired($subscription);
        return PlanCapability::getServiceLimit($subscription->plan, $isTrial) <= 0;
    }
}

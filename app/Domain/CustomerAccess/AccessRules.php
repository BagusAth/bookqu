<?php

namespace App\Domain\CustomerAccess;

use Carbon\CarbonImmutable;

final class AccessRules
{
    public static function permits(AccessProfile $profile, AccessOperation $operation): bool
    {
        return $profile === AccessProfile::Management || in_array($operation, [
            AccessOperation::Payment, AccessOperation::Invoice, AccessOperation::CancelPayment,
        ], true);
    }

    public static function expiry(AccessProfile $profile, CarbonImmutable $issued, ?CarbonImmutable $deadline, CarbonImmutable $latestEnd): CarbonImmutable
    {
        if ($profile === AccessProfile::Checkout) {
            if ($deadline === null) {
                throw new \LogicException('Checkout requires a captured payment deadline.');
            }

            return $issued->max($deadline)->addDay();
        }

        return $issued->max($latestEnd)->addDays(30)->min($issued->addDays(180));
    }
}

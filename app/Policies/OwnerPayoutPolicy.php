<?php

namespace App\Policies;

final class OwnerPayoutPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'payouts.manage';
}

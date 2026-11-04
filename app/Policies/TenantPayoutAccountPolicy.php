<?php

namespace App\Policies;

final class TenantPayoutAccountPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'payouts.manage';
}

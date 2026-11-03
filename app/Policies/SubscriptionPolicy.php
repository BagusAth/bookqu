<?php

namespace App\Policies;

final class SubscriptionPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'subscription.manage';
}

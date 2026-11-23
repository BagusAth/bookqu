<?php

namespace App\Policies;

final class PaymentPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'subscription.manage';
}

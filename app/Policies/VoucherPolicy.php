<?php

namespace App\Policies;

final class VoucherPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'promos.manage';
}

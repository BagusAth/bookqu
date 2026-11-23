<?php

namespace App\Policies;

final class AdditionalItemPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'services.manage';
}

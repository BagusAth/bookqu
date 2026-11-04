<?php

namespace App\Policies;

final class ResourcePolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'services.manage';
}

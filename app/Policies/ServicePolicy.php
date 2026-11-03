<?php

namespace App\Policies;

final class ServicePolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'services.manage';
}

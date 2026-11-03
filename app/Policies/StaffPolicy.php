<?php

namespace App\Policies;

final class StaffPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'services.manage';
}

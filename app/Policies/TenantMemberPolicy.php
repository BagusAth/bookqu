<?php

namespace App\Policies;

final class TenantMemberPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'team.manage';
}

<?php

namespace App\Policies;

final class OwnerBlockedDatePolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'schedule.manage';
}

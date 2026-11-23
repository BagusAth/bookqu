<?php

namespace App\Policies;

final class SchedulePolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'schedule.manage';
}

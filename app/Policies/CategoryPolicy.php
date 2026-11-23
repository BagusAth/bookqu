<?php

namespace App\Policies;

final class CategoryPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'services.manage';
}

<?php

namespace App\Policies;

final class AssetPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'settings.manage';
}

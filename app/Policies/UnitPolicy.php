<?php

namespace App\Policies;

/** Units are managed with the units.manage ability (FR-UNIT-001, ADR-0016). */
final class UnitPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'units.manage';
}

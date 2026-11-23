<?php

namespace App\Policies;

final class CustomerNotePolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'customers.manage';
}

<?php

namespace App\Policies;

final class ReviewPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'reviews.manage';
}

<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

final class TenantPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'settings.manage';

    public function delete(User $user, Model $record): Response
    {
        return $this->operate($user, $record, 'business.delete');
    }
}

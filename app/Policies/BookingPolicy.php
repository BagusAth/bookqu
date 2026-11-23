<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

final class BookingPolicy extends TenantResourcePolicy
{
    protected const string ABILITY = 'bookings.view';

    public function delete(User $user, Model $record): Response
    {
        return $this->operate($user, $record, 'bookings.cancel');
    }
}

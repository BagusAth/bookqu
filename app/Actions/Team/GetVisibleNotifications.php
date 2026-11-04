<?php

namespace App\Actions\Team;

use App\Domain\Team\TenantPermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\DatabaseNotification;

final class GetVisibleNotifications
{
    /** @return MorphMany<DatabaseNotification, User> */
    public function execute(User $user): MorphMany
    {
        $query = $user->notifications();
        $member = app(ResolveTenantMembership::class)->execute($user);
        if ($member === null) {
            return $query->whereRaw('1 = 0');
        }
        if (! TenantPermission::allowsRole((string) $member->role, 'payouts.manage')) {
            $query->whereIn('type', TenantPermission::notificationTypes((string) $member->role));
        }

        return $query;
    }
}

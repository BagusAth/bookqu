<?php

namespace App\Actions\Team;

use App\Domain\Team\TenantPermission;
use App\Models\User;
use App\Notifications\RefundTeamNotification;
use App\Policies\TenantResourcePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

final class TeamNotificationAccess
{
    public function allows(object $recipient, Notification $notification): bool
    {
        if (! str_starts_with($notification::class, 'App\\Notifications\\') || ! $recipient instanceof User || $recipient->isAdmin()) {
            return true;
        }
        $member = app(ResolveTenantMembership::class)->execute($recipient);
        $ability = TenantPermission::notificationMap()[$notification::class] ?? null;
        if ($member === null || $ability === null || ! TenantPermission::allowsRole((string) $member->role, $ability)) {
            return false;
        }
        if ($notification instanceof RefundTeamNotification
            && $notification->idtenant !== (int) $member->idtenant) {
            return false;
        }
        foreach (get_object_vars($notification) as $subject) {
            if ($subject instanceof Model && $subject->getAttribute('idtenant') !== null
                && (int) $subject->getAttribute('idtenant') !== (int) $member->idtenant) {
                return false;
            }
        }

        return true;
    }

    public function maySeeRevenue(object $recipient): bool
    {
        return $recipient instanceof User && app(TenantResourcePolicy::class)->ability($recipient, 'revenue.view');
    }
}

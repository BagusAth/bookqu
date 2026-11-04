<?php

namespace App\Actions\Team;

use App\Domain\Team\TenantPermission;
use App\Models\TenantMember;
use App\Models\User;

final class ResolveTenantMembership
{
    public function execute(User $user): ?TenantMember
    {
        // scope-bypass: authentication resolves tenancy from the trusted signed-in user, before tenant context exists.
        $members = TenantMember::withoutGlobalScopes()->with('tenant')->where('iduser', $user->id)
            ->whereNotNull('accepted_at')->whereNull('revoked_at')->limit(2)->get();

        $member = $members->count() === 1 ? $members->first() : null;
        if ($member && TenantPermission::isOwnerRole((string) $member->role)
            && (int) $member->tenant?->iduser !== (int) $user->id) {
            return null;
        }

        return $member;
    }

    public function hasMembership(User $user): bool
    {
        // scope-bypass: authentication checks only memberships belonging to this signed-in user.
        return TenantMember::withoutGlobalScopes()->where('iduser', $user->id)->exists();
    }
}

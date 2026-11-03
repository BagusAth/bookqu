<?php

namespace App\Policies;

use App\Actions\Team\ResolveTenantMembership;
use App\Domain\Team\TenantPermission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class TenantResourcePolicy
{
    protected const string ABILITY = '';

    public function ability(User $user, ?string $ability): bool
    {
        if (! $user->isOwner()) {
            return false;
        }
        $member = app(ResolveTenantMembership::class)->execute($user);

        return $member !== null && $ability !== null && TenantPermission::allowsRole((string) $member->role, $ability);
    }

    public function operate(User $user, Model $record, string $ability): Response
    {
        if (! $user->isOwner()) {
            return Response::denyAsNotFound();
        }
        $member = app(ResolveTenantMembership::class)->execute($user);
        $tenantId = $record instanceof Tenant ? $record->id : $record->getAttribute('idtenant');
        if ($member === null || (int) $member->idtenant !== (int) $tenantId) {
            return Response::denyAsNotFound();
        }

        return TenantPermission::allowsRole((string) $member->role, $ability) ? Response::allow() : Response::deny();
    }

    public function viewAny(User $user): bool
    {
        return $this->ability($user, static::ABILITY);
    }

    public function create(User $user): bool
    {
        return $this->ability($user, static::ABILITY);
    }

    public function view(User $user, Model $record): Response
    {
        return $this->operate($user, $record, static::ABILITY);
    }

    public function update(User $user, Model $record): Response
    {
        return $this->operate($user, $record, static::ABILITY);
    }

    public function delete(User $user, Model $record): Response
    {
        return $this->operate($user, $record, static::ABILITY);
    }
}

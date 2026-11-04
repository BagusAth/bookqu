<?php

namespace App\Http\Controllers;

use App\Models\TenantMember;
use App\Support\TenantActor;

abstract class Controller
{
    /** @param array<int|string, mixed> $parameters */
    public function callAction(string $method, array $parameters): mixed
    {
        $request = request();
        $member = $request->attributes->get('tenant_member');
        $actor = $member instanceof TenantMember && (int) $member->iduser === (int) $request->user()?->getAuthIdentifier()
            ? $member : null;

        return app(TenantActor::class)->run($actor, fn () => $this->{$method}(...array_values($parameters)));
    }
}

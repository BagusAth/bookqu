<?php

namespace App\Http\Middleware;

use App\Domain\Team\TenantPermission;
use App\Models\Plan;
use App\Models\TenantMember;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->attributes->get('tenant_member');
        $name = (string) $request->route()?->getName();
        if (! $member instanceof TenantMember) {
            // A new owner without any business may enter only the existing onboarding flow.
            abort_unless(in_array($name, ['owner.dashboard', 'owner.profile.complete'], true), 403);

            return $next($request);
        }
        $ability = TenantPermission::routeAbility($name, $request->method(), $request->input('status'));
        Gate::authorize('tenant-ability', [$ability]);
        foreach ($request->route()->parameters() as $record) {
            if ($record instanceof Model && ! $record instanceof Plan) {
                Gate::authorize('operate', [$record, $ability]);
            }
        }
        $request->attributes->set('can_view_revenue', TenantPermission::allowsRole((string) $member->role, 'revenue.view'));

        return $next($request);
    }
}

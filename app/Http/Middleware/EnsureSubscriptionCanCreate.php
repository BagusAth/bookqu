<?php

namespace App\Http\Middleware;

use App\Domain\Subscription\EntitlementRules;
use App\Models\Subscription;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSubscriptionCanCreate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (EntitlementRules::requiresActiveCreation((string) $request->route()?->getName())) {
            $tenantId = app(TenantContext::class)->getTenantId();
            $subscription = $tenantId ? Subscription::with('plan')->where('idtenant', $tenantId)->latest('id')->first() : null;
            if (! EntitlementRules::hasEffectivePlan($subscription)) {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Paket berakhir. Perpanjang paket untuk menambahkan data baru.', 'code' => 'subscription-ended'], 403)
                    : response()->view('owner.subscription-ended', [], 403);
            }
        }

        return $next($request);
    }
}

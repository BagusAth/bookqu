<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Subscription\EntitlementRules;
use App\Domain\Subscription\SubscriptionState;
use App\Models\Subscription;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$features): Response
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        if (!$tenantId) {
            $tenantId = auth()->user()?->tenant?->id;
        }

        if (!$tenantId) {
            return $next($request);
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::with('plan')->where('idtenant', $tenantId)->latest()->first();

        if (!$subscription) {
            return redirect()->route('owner.subscription')->with('error', 'Silakan berlangganan untuk mengakses fitur ini.');
        }

        if (SubscriptionState::isExpired($subscription)) {
            return redirect()->route('owner.subscription')->with('error', 'Langganan Anda telah habis, mohon perpanjang.');
        }

        // Feature gating via centralized EntitlementRules
        if (!empty($features)) {
            foreach ($features as $feature) {
                $decision = EntitlementRules::canAccessFeature($subscription, (string) $feature);
                if (!$decision['allowed']) {
                    return redirect()->route('owner.subscription')->with('error', $decision['message']);
                }
            }
        }

        return $next($request);
    }
}


<?php

namespace App\Http\Middleware;

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
        $tenantId = app(\App\Support\TenantContext::class)->getTenantId();
        if (!$tenantId) {
            $tenantId = auth()->user()?->tenant?->id;
        }

        if (!$tenantId) {
            return $next($request);
        }

        $subscription = \App\Models\Subscription::with('plan')->where('idtenant', $tenantId)->latest()->first();

        if (!$subscription) {
            return redirect()->route('owner.subscription')->with('error', 'Silakan berlangganan untuk mengakses fitur ini.');
        }

        if (in_array($subscription->status, ['expired', 'cancelled'])) {
            return redirect()->route('owner.subscription')->with('error', 'Langganan Anda telah habis, mohon perpanjang.');
        }

        // Feature gating
        if (!empty($features)) {
            $planName = $subscription->plan->namapaket ?? '';
            
            // Allow all for trial
            if ($subscription->status === 'trial') {
                return $next($request);
            }

            // Simple check: if feature is 'pro', require pro plan
            if (in_array('pro', $features) && $planName !== 'pro') {
                return redirect()->route('owner.subscription')->with('error', 'Fitur ini membutuhkan paket Pro.');
            }
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\CustomerAccess\AccessContext;
use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        // First check if this is an owner route (prefix /owner or explicitly targeting owner)
        $isOwnerRoute = $request->is('owner/*') || $request->is('owner');

        // P0-01: Customer context (based on slug or custom domain)
        $slug = $request->route('slug') ?? $request->route('slug_usaha');
        $host = $request->getHost();
        $isCustomDomain = $host !== '127.0.0.1' && $host !== 'localhost' && ! str_contains($host, 'bookqu.test');

        $resolvedTenant = null;

        if (is_string($slug) && $slug !== '') {
            $resolvedTenant = Tenant::where('slug', $slug)->first();
        } elseif ($isCustomDomain) {
            $resolvedTenant = Tenant::where('custom_domain', $host)->first();
            if ($resolvedTenant) {
                $request->route()->setParameter('slug_usaha', $resolvedTenant->slug);
                $request->route()->forgetParameter('custom_domain');
            }
        }

        try {
            $access = $request->attributes->get('customer_access');
            if ($access instanceof AccessContext) {
                abort_if($resolvedTenant && (int) $resolvedTenant->id !== (int) $access->grant->idtenant, 404);
                abort_if(is_string($slug) && $slug !== '' && ! $resolvedTenant, 404);
                app(TenantContext::class)->setTenantId((int) $access->grant->idtenant);

                return $next($request);
            }
            if ($resolvedTenant && ! $isOwnerRoute) {
                app(TenantContext::class)->setTenantId($resolvedTenant->id);

                return $next($request);
            }

            if (is_string($slug) && $slug !== '' && ! $resolvedTenant) {
                abort(404, 'Bisnis tidak ditemukan');
            }

            // P0-01: Owner context (based on authenticated user)
            if ($isOwnerRoute) {
                $user = auth()->user();
                if ($user) {
                    $ownerTenant = Tenant::where('iduser', $user->id)->first();
                    if ($ownerTenant) {
                        app(TenantContext::class)->setTenantId($ownerTenant->id);
                    }
                }
            }

            return $next($request);
        } finally {
            app(TenantContext::class)->clear();
        }
    }
}

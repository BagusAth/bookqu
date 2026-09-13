<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        // First check if this is an owner route (prefix /owner or explicitly targeting owner)
        $isOwnerRoute = $request->is('owner/*') || $request->is('owner');
        
        // P0-01: Customer context (based on slug or custom domain)
        $slug = $request->route('slug') ?? $request->route('slug_usaha');
        $host = $request->getHost();
        $isCustomDomain = $host !== '127.0.0.1' && $host !== 'localhost' && !str_contains($host, 'bookqu.test');
        
        $resolvedTenant = null;

        if (is_string($slug) && $slug !== '') {
            $resolvedTenant = Tenant::where('slug', $slug)->first();
        } elseif ($isCustomDomain) {
            $resolvedTenant = Tenant::where('custom_domain', $host)->first();
            if ($resolvedTenant) {
                $request->route()->setParameter('slug_usaha', $resolvedTenant->slug);
            }
        }

        try {
            if ($resolvedTenant && !$isOwnerRoute) {
                app(\App\Support\TenantContext::class)->setTenantId($resolvedTenant->id);
                return $next($request);
            }

            if (is_string($slug) && $slug !== '' && !$resolvedTenant) {
                abort(404, 'Bisnis tidak ditemukan');
            }

            // P0-01: Owner context (based on authenticated user)
            if ($isOwnerRoute) {
                $user = auth()->user();
                if ($user) {
                    $ownerTenant = \App\Models\Tenant::where('iduser', $user->id)->first();
                    if ($ownerTenant) {
                        app(\App\Support\TenantContext::class)->setTenantId($ownerTenant->id);
                    }
                }
            }

            return $next($request);
        } finally {
            app(\App\Support\TenantContext::class)->clear();
        }
    }
}

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

        if ($resolvedTenant && !$isOwnerRoute) {
            \App\Support\TenantContext::setTenantId($resolvedTenant->id);
            session()->put('current_tenant_id', $resolvedTenant->id); // keep for backward compatibility
            return $next($request);
        }

        if (is_string($slug) && $slug !== '' && !$resolvedTenant) {
            abort(404, 'Bisnis tidak ditemukan');
        }

        // P0-01: Owner context (based on authenticated user)
        if (Auth::check()) {
            $tenant = Tenant::where('iduser', Auth::id())->first();

            if ($tenant) {
                \App\Support\TenantContext::setTenantId($tenant->id);
                session()->put('current_tenant_id', $tenant->id); // keep for backward compatibility
                return $next($request);
            }

            session()->forget('current_tenant_id');
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;

class EnsureOwnerProfileComplete
{
    public function handle(Request $request, Closure $next): mixed
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        $tenant = null;

        if (is_numeric($tenantId)) {
            $tenant = Tenant::find($tenantId);
        }

        if (! $tenant && $request->user()) {
            $tenant = Tenant::where('iduser', $request->user()->id)->first();
        }

        if (! $tenant) {
            return redirect()->route('owner.dashboard')
                ->with('pesan', 'Lengkapi profil bisnis terlebih dahulu.');
        }

        $isProfileComplete = (bool) ($tenant->namabisnis && $tenant->slug && $tenant->jenisbisnis && $tenant->nomorhp);

        if (! $isProfileComplete) {
            return redirect()->route('owner.dashboard')
                ->with('pesan', 'Lengkapi profil bisnis terlebih dahulu.');
        }

        return $next($request);
    }
}

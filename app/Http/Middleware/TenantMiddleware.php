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
        $tenant = null;
        $slug = $request->route('slug');

        if (is_string($slug) && $slug !== '') {
            $tenant = Tenant::where('slug', $slug)->first();
        }

        if (!$tenant && Auth::check()) {
            $tenant = Tenant::where('iduser', Auth::id())->first();
        }

        if ($tenant) {
            session()->put('current_tenant_id', $tenant->id);
        }

        return $next($request);
    }
}

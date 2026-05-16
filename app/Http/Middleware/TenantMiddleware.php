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
        if (Auth::check()) {
            $tenant = Tenant::where('iduser', Auth::id())->first();

            if ($tenant) {
                session()->put('current_tenant_id', $tenant->id);
                return $next($request);
            }
        }

        $slug = $request->route('slug') ?? $request->route('slug_usaha');

        if (is_string($slug) && $slug !== '') {
            $tenant = Tenant::where('slug', $slug)->first();

            if ($tenant) {
                session()->put('current_tenant_id', $tenant->id);
                return $next($request);
            }

            abort(404, 'Bisnis tidak ditemukan');
        }

        return $next($request);
    }
}

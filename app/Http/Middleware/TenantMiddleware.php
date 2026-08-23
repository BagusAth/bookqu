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

            session()->forget('current_tenant_id');
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

        // Cek custom domain jika tidak ada slug
        $host = $request->getHost();
        if ($host !== '127.0.0.1' && $host !== 'localhost' && !str_contains($host, 'bookqu.test')) {
            $tenant = Tenant::where('custom_domain', $host)->first();
            if ($tenant) {
                session()->put('current_tenant_id', $tenant->id);
                // Agar controller yang membutuhkan route('slug_usaha') tidak error, kita bisa mensetnya
                $request->route()->setParameter('slug_usaha', $tenant->slug);
                return $next($request);
            }
        }

        return $next($request);
    }
}

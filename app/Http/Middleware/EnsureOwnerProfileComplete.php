<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class EnsureOwnerProfileComplete
{
    public function handle(Request $request, Closure $next): mixed
    {
        $tenantId = app(\App\Support\TenantContext::class)->getTenantId();
        $tenant = null;

        if (is_numeric($tenantId)) {
            $tenant = Tenant::find($tenantId);
        }

        if (!$tenant && $request->user()) {
            $tenant = Tenant::where('iduser', $request->user()->id)->first();
        }

        if (!$tenant) {
            return redirect()->route('owner.dashboard')
                ->with('pesan', 'Lengkapi profil bisnis terlebih dahulu.');
        }

        $isProfileComplete = (bool) ($tenant->namabisnis && $tenant->slug && $tenant->jenisbisnis && $tenant->nomorhp);

        if (!$isProfileComplete) {
            return redirect()->route('owner.dashboard')
                ->with('pesan', 'Lengkapi profil bisnis terlebih dahulu.');
        }

        $paymentMode = $tenant->payment_mode ?? 'platform';
        if ($paymentMode === 'owner') {
            $status = $tenant->midtrans_status ?? 'pending';
            $environment = $tenant->midtrans_environment ?? 'sandbox';

            $hasKeys = $environment === 'production'
                ? ($tenant->midtrans_prod_merchant_id && $tenant->midtrans_prod_client_key && $tenant->midtrans_prod_server_key)
                : ($tenant->midtrans_sandbox_merchant_id && $tenant->midtrans_sandbox_client_key && $tenant->midtrans_sandbox_server_key);

            if ($status !== 'approved' || !$hasKeys) {
                return redirect()->route('owner.settings')
                    ->with('pesan', 'Pengaturan pembayaran belum lengkap atau belum terverifikasi.');
            }
        }

        return $next($request);
    }
}

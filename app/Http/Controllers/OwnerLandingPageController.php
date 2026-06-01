<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;

class OwnerLandingPageController extends Controller
{
    private function resolveTenant(): ?Tenant
    {
        $tenantId = session('current_tenant_id');

        if (is_numeric($tenantId)) {
            return Tenant::with('user')->find($tenantId);
        }

        $userId = auth()->id();

        if ($userId) {
            return Tenant::with('user')->where('iduser', $userId)->first();
        }

        return null;
    }

    /**
     * Halaman Landing Page builder — hanya untuk paket Pro.
     */
    public function index()
    {
        $tenant = $this->resolveTenant();

        if (!$tenant) {
            return redirect()->route('owner.dashboard')
                ->with('pesan', 'Lengkapi profil bisnis terlebih dahulu.');
        }

        // Cek apakah tenant berlangganan paket Pro
        $langganan = Subscription::where('idtenant', $tenant->id)
            ->with('plan')
            ->where('status', '!=', 'expired')
            ->latest()
            ->first();

        $adalahpro = $langganan?->plan?->namapaket === 'pro';

        if (!$adalahpro) {
            return redirect('/owner/subscription')
                ->with('pesan', 'Fitur Landing Page hanya tersedia untuk paket Pro. Silakan upgrade paket Anda.');
        }

        return view('owner.owner-landing-page', compact('tenant'));
    }
}

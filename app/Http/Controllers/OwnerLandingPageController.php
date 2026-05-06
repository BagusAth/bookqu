<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;

class OwnerLandingPageController extends Controller
{
    /**
     * Halaman Landing Page builder — hanya untuk paket Pro.
     */
    public function index()
    {
        $tenant = Tenant::with('user')->first();

        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
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

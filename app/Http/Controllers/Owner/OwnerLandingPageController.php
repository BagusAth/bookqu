<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;

class OwnerLandingPageController extends Controller
{
    use \App\Traits\ResolvesOwnerTenant;

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

        return view('owner.landing-page', compact('tenant'));
    }

    public function store(Request $request)
    {
        $tenant = $this->resolveTenant();

        if (!$tenant) {
            return redirect()->route('owner.dashboard')->with('pesan', 'Lengkapi profil bisnis terlebih dahulu.');
        }

        // Cek apakah tenant berlangganan paket Pro
        $langganan = Subscription::where('idtenant', $tenant->id)
            ->with('plan')
            ->where('status', '!=', 'expired')
            ->latest()
            ->first();

        $adalahpro = $langganan?->plan?->namapaket === 'pro';

        if (!$adalahpro) {
            return redirect('/owner/subscription')->with('pesan', 'Fitur Landing Page hanya tersedia untuk paket Pro.');
        }

        $validated = $request->validate([
            'custom_domain' => 'nullable|string|max:255|unique:tenants,custom_domain,' . $tenant->id,
            'theme_color'   => 'nullable|string|max:50',
            'banner'        => 'nullable|image|mimes:jpeg,png,jpg,webp,gif,svg|max:10240',
        ]);

        if ($request->hasFile('banner')) {
            if ($tenant->banner_path && !str_starts_with($tenant->banner_path, 'http') && \Illuminate\Support\Facades\Storage::disk('public')->exists($tenant->banner_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($tenant->banner_path);
            }
            $bannerPath = $request->file('banner')->store('banners', 'public');
            $tenant->banner_path = $bannerPath;
        }

        $tenant->custom_domain = $validated['custom_domain'] ?? null;
        $tenant->theme_color = $validated['theme_color'] ?? null;
        $tenant->save();

        return redirect()->back()->with('pesan', 'Pengaturan Landing Page berhasil disimpan!');
    }
}


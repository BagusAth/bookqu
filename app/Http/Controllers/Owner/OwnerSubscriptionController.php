<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OwnerSubscriptionController extends Controller
{
    use \App\Traits\ResolvesOwnerTenant;

    /**
     * Halaman subscription management.
     */
    public function index()
    {
        $tenant = $this->resolveTenant();
        $user = auth()->user();
        if (!$tenant) {
            $tenant = new Tenant();
            if ($user) {
                $tenant->setRelation('user', $user);
            }

            return view('owner.subscription', [
                'tenant' => $tenant,
                'langgananaktif' => null,
                'semuapaket' => Plan::all(),
                'jumlahlayanan' => 0,
                'jumlahbookingbulanini' => 0,
                'maxlayanan' => 0,
                'maxbooking' => 0,
                'isunlimited' => false,
                'persenlayanan' => 0,
                'persenbooking' => 0,
                'statustrial' => false,
                'sisahari' => 0,
                'riwayatpembayaran' => collect(),
            ]);
        }

        $idtenant = $tenant->id;

        // Langganan saat ini
        $langgananaktif = Subscription::where('idtenant', $idtenant)
            ->with('plan')
            ->latest()
            ->first();

        // Semua paket
        $semuapaket = Plan::all();

        // Usage stats
        $jumlahlayanan = Service::where('idtenant', $idtenant)->count();
        $jumlahbookingbulanini = Booking::where('idtenant', $idtenant)
            ->whereMonth('tanggalbooking', Carbon::now()->month)
            ->whereYear('tanggalbooking', Carbon::now()->year)
            ->count();

        $maxlayanan = $langgananaktif?->plan?->maxlayanan ?? 0;
        $maxbooking = $langgananaktif?->plan?->maxbooking ?? 0;
        $isunlimited = $langgananaktif?->plan?->isunlimited ?? false;
        $planName = strtolower($langgananaktif?->plan?->namapaket ?? 'small');

        // Staff usage & limit
        $jumlahstaff = Staff::where('idtenant', $idtenant)->count();
        $isunlimitedstaff = $isunlimited || $planName === 'pro';
        $maxstaff = match ($planName) {
            'small'  => 2,
            'medium' => 15,
            'pro'    => 0,
            default  => 2,
        };

        $persenlayanan = $maxlayanan > 0 ? min(100, round(($jumlahlayanan / $maxlayanan) * 100)) : 0;
        $persenbooking = ($maxbooking > 0 && !$isunlimited) ? min(100, round(($jumlahbookingbulanini / $maxbooking) * 100)) : 0;
        $persenstaff = ($maxstaff > 0 && !$isunlimitedstaff) ? min(100, round(($jumlahstaff / $maxstaff) * 100)) : 0;

        // Status trial
        $sisahari = 0;
        $statustrial = false;
        if ($langgananaktif && $langgananaktif->status === 'trial' && $langgananaktif->trial_berakhir) {
            $statustrial = true;
            $sisahari = (int) max(0, ceil(Carbon::now()->diffInDays($langgananaktif->trial_berakhir, false)));
        }

        // Riwayat pembayaran subscription
        $riwayatpembayaran = Payment::where('idtenant', $idtenant)
            ->where('tipe', 'subscription')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('owner.subscription', compact(
            'tenant',
            'langgananaktif',
            'semuapaket',
            'jumlahlayanan',
            'jumlahbookingbulanini',
            'jumlahstaff',
            'maxlayanan',
            'maxbooking',
            'maxstaff',
            'isunlimited',
            'isunlimitedstaff',
            'persenlayanan',
            'persenbooking',
            'persenstaff',
            'statustrial',
            'sisahari',
            'riwayatpembayaran',
        ));
    }
}


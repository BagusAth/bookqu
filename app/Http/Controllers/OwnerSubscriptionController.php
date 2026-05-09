<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerSubscriptionController extends Controller
{
    /**
     * Halaman subscription management.
     */
    public function index()
    {
        $iduser = Auth::id();
        $tenant = Tenant::with('user')->where('iduser', $iduser)->first();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
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

        $persenlayanan = $maxlayanan > 0 ? min(100, round(($jumlahlayanan / $maxlayanan) * 100)) : 0;
        $persenbooking = ($maxbooking > 0 && !$isunlimited) ? min(100, round(($jumlahbookingbulanini / $maxbooking) * 100)) : 0;

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

        return view('owner.owner-subscription', compact(
            'tenant',
            'langgananaktif',
            'semuapaket',
            'jumlahlayanan',
            'jumlahbookingbulanini',
            'maxlayanan',
            'maxbooking',
            'isunlimited',
            'persenlayanan',
            'persenbooking',
            'statustrial',
            'sisahari',
            'riwayatpembayaran',
        ));
    }
}

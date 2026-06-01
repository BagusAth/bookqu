<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OwnerBookingController extends Controller
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
     * Halaman daftar booking.
     */
    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        $user = auth()->user();
        if (!$tenant) {
            $tenant = new Tenant();
            if ($user) {
                $tenant->setRelation('user', $user);
            }

            $filterstatus = $request->input('status', 'semua');
            $katakunci = $request->input('katakunci', '');
            $daftarbooking = Booking::whereRaw('1 = 0')->paginate(15);

            return view('owner.owner-bookings', [
                'tenant' => $tenant,
                'daftarbooking' => $daftarbooking,
                'totalbooking' => 0,
                'bookingpending' => 0,
                'bookingkonfirmasi' => 0,
                'bookingselesai' => 0,
                'bookingbatal' => 0,
                'bookinghariini' => 0,
                'filterstatus' => $filterstatus,
                'katakunci' => $katakunci,
            ]);
        }

        $idtenant = $tenant->id;
        $filterstatus = $request->input('status', 'semua');
        $katakunci = $request->input('katakunci', '');

        $daftarbooking = Booking::where('bookings.idtenant', $idtenant)
            ->with(['layanan', 'payment'])
            ->when($filterstatus !== 'semua', function ($query) use ($filterstatus) {
                $query->where('status', $filterstatus);
            })
            ->when($katakunci, function ($query) use ($katakunci) {
                $query->where(function ($q) use ($katakunci) {
                    $q->where('namapelanggan', 'like', '%' . $katakunci . '%')
                      ->orWhere('email', 'like', '%' . $katakunci . '%')
                      ->orWhere('nomorhp', 'like', '%' . $katakunci . '%');
                });
            })
            ->orderByDesc('bookings.created_at')
            ->paginate(15);

        // Statistik
        $totalbooking = Booking::where('idtenant', $idtenant)->count();
        $bookingpending = Booking::where('idtenant', $idtenant)->where('status', 'pending')->count();
        $bookingkonfirmasi = Booking::where('idtenant', $idtenant)->where('status', 'paid')->count();
        $bookingselesai = Booking::where('idtenant', $idtenant)->where('status', 'completed')->count();
        $bookingbatal = Booking::where('idtenant', $idtenant)->where('status', 'cancelled')->count();

        // Booking hari ini
        $bookinghariini = Booking::where('idtenant', $idtenant)
            ->whereDate('tanggalbooking', Carbon::today())
            ->count();

        return view('owner.owner-bookings', compact(
            'tenant',
            'daftarbooking',
            'totalbooking',
            'bookingpending',
            'bookingkonfirmasi',
            'bookingselesai',
            'bookingbatal',
            'bookinghariini',
            'filterstatus',
            'katakunci',
        ));
    }
}

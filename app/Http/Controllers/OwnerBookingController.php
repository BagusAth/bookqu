<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerBookingController extends Controller
{
    /**
     * Halaman daftar booking.
     */
    public function index(Request $request)
    {
        $iduser = Auth::id();
        $tenant = Tenant::with('user')->where('iduser', $iduser)->first();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
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

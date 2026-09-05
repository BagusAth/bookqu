<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Tenant;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OwnerBookingController extends Controller
{
    use ClearsBookingCache;

    use \App\Traits\ResolvesOwnerTenant;

    /**
     * Halaman daftar booking.
     */
    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        $user   = auth()->user();
        if (!$tenant) {
            $tenant = new Tenant();
            if ($user) {
                $tenant->setRelation('user', $user);
            }

            $filterstatus  = $request->input('status', 'semua');
            $katakunci     = $request->input('katakunci', '');
            $daftarbooking = Booking::whereRaw('1 = 0')->paginate(15);

            return view('owner.owner-bookings', [
                'tenant'            => $tenant,
                'daftarbooking'     => $daftarbooking,
                'totalbooking'      => 0,
                'bookingpending'    => 0,
                'bookingkonfirmasi' => 0,
                'bookingselesai'    => 0,
                'bookingbatal'      => 0,
                'bookinghariini'    => 0,
                'filterstatus'      => $filterstatus,
                'katakunci'         => $katakunci,
            ]);
        }

        $idtenant     = $tenant->id;
        $filterstatus = $request->input('status', 'semua');
        $katakunci    = $request->input('katakunci', '');

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
        $totalbooking      = Booking::where('idtenant', $idtenant)->count();
        $bookingpending    = Booking::where('idtenant', $idtenant)->where('status', 'pending')->count();
        $bookingkonfirmasi = Booking::where('idtenant', $idtenant)->where('status', 'paid')->count();
        $bookingselesai    = Booking::where('idtenant', $idtenant)->where('status', 'completed')->count();
        $bookingbatal      = Booking::where('idtenant', $idtenant)->where('status', 'cancelled')->count();

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

    /**
     * FS-010: Update status booking oleh owner.
     * Transisi status yang diizinkan:
     *   paid    → completed | cancelled
     *   pending → cancelled
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        $tenant = $this->resolveTenant();

        // Pastikan booking milik tenant yang sedang login
        if (!$tenant || $booking->idtenant !== $tenant->id) {
            abort(404, 'Booking tidak ditemukan.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:completed,cancelled'],
        ]);

        $statusLama = $booking->status;
        $statusBaru = $validated['status'];

        $transisi = [
            'paid'    => ['completed', 'cancelled'],
            'pending' => ['cancelled'],
        ];

        if (!in_array($statusBaru, $transisi[$statusLama] ?? [])) {
            return back()->withErrors([
                'error' => "Status tidak dapat diubah dari '{$statusLama}' ke '{$statusBaru}'.",
            ]);
        }

        $booking->update(['status' => $statusBaru]);

        // Invalidate availability cache when a cancellation or completion frees/affects a slot
        if (in_array($statusBaru, ['cancelled', 'completed']) && $booking->idlayanan && $booking->tanggalbooking) {
            $tanggal = $booking->tanggalbooking instanceof Carbon
                ? $booking->tanggalbooking->toDateString()
                : Carbon::parse($booking->tanggalbooking)->toDateString();

            $this->clearBookingAvailabilityCache(
                (int) $booking->idtenant,
                (int) $booking->idlayanan,
                $tanggal
            );
        }

        $label = match ($statusBaru) {
            'completed' => 'selesai',
            'cancelled'  => 'dibatalkan',
            default      => $statusBaru,
        };

        return back()->with('sukses', "Booking atas nama {$booking->namapelanggan} berhasil ditandai sebagai {$label}.");
    }
}

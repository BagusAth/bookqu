<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Traits\ClearsBookingCache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    /**
     * Walk-in booking creation by owner (directly from Calendar / Booking module)
     */
    public function walkinStore(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $data = $request->all();
        if (!isset($data['idschedule']) && isset($data['schedule_id'])) {
            $data['idschedule'] = $data['schedule_id'];
        }

        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'idschedule'    => ['required', 'integer', Rule::exists('schedules', 'id')->where('idtenant', $tenant->id)],
            'namapelanggan' => ['required', 'string', 'max:150'],
            'nomorhp'       => ['required', 'string', 'max:30'],
            'email'         => ['nullable', 'email', 'max:100'],
            'catatan'       => ['nullable', 'string', 'max:500'],
            'metode'        => ['nullable', 'string', 'in:cash,transfer,manual,qris'],
        ]);

        $validated = $validator->validate();

        $booking = DB::transaction(function () use ($tenant, $validated) {
            $schedule = Schedule::where('id', $validated['idschedule'])
                ->where('idtenant', $tenant->id)
                ->where('status', 'tersedia')
                ->lockForUpdate()
                ->first();

            if (!$schedule) {
                return null;
            }

            // Check if slot already has an active booking
            $isBooked = Booking::where('idschedule', $schedule->id)
                ->whereIn('status', ['pending', 'paid', 'completed'])
                ->exists();

            if ($isBooked) {
                return null;
            }

            $service = $schedule->layanan;
            $amount = $schedule->harga_override ?? ($service ? $service->harga : 0);

            // Create Payment record for Walk-in
            $orderId = 'WLK-' . strtoupper(Str::random(10));
            $payment = Payment::create([
                'idtenant'       => $tenant->id,
                'tipe'           => 'booking',
                'jumlah'         => $amount,
                'status'         => 'sukses',
                'metode'         => $validated['metode'] ?? 'cash',
                'order_id'       => $orderId,
                'nama_pembayar'  => $validated['namapelanggan'],
                'email_pembayar' => $validated['email'] ?? ($tenant->user->email ?? 'walkin@example.com'),
                'hp_pembayar'    => $validated['nomorhp'],
            ]);

            // Create Booking record
            $booking = Booking::create([
                'idtenant'       => $tenant->id,
                'idlayanan'      => $schedule->idlayanan,
                'idschedule'     => $schedule->id,
                'idpayment'      => $payment->id,
                'namapelanggan'  => $validated['namapelanggan'],
                'nomorhp'        => $validated['nomorhp'],
                'email'          => $validated['email'] ?? null,
                'tanggalbooking' => $schedule->tanggal,
                'jam'            => $schedule->jam_mulai,
                'status'         => 'paid',
                'catatan'        => $validated['catatan'] ?? 'Walk-in booking via Owner Calendar',
            ]);

            $booking->assignManagementTokens();

            return $booking;
        });

        if (!$booking) {
            return back()->withErrors(['error' => 'Slot waktu yang dipilih tidak tersedia atau sudah terisi.']);
        }

        $this->clearBookingAvailabilityCache(
            (int) $booking->idtenant,
            (int) $booking->idlayanan,
            $booking->tanggalbooking instanceof Carbon ? $booking->tanggalbooking->toDateString() : (string) $booking->tanggalbooking
        );

        return back()->with('sukses', "Walk-in booking atas nama {$booking->namapelanggan} berhasil dibuat!");
    }
}

<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Traits\ClearsBookingCache;
use App\Actions\Booking\CreateWalkInBooking;
use App\Actions\Booking\UpdateBookingStatus;
use App\Actions\Booking\RescheduleBooking;
use App\Http\Requests\Booking\UpdateBookingStatusRequest;
use App\Http\Requests\Booking\RescheduleBookingRequest;
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

            return view('owner.bookings', [
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
            ->with(['layanan.staff', 'layanan.resources', 'payment'])
            ->when($filterstatus !== 'semua', function ($query) use ($filterstatus) {
                if ($filterstatus === 'today') {
                    $query->whereDate('tanggalbooking', Carbon::today());
                } else {
                    $query->where('status', $filterstatus);
                }
            })
            ->when($katakunci, function ($query) use ($katakunci) {
                $query->where(function ($q) use ($katakunci) {
                    $q->where('namapelanggan', 'like', '%' . $katakunci . '%')
                      ->orWhere('email', 'like', '%' . $katakunci . '%')
                      ->orWhere('nomorhp', 'like', '%' . $katakunci . '%')
                      ->orWhere('booking_code', 'like', '%' . $katakunci . '%')
                      ->orWhere('bookings.id', 'like', '%' . $katakunci . '%');
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

        return view('owner.bookings', compact(
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
     *   pending → paid | completed | cancelled
     */
    public function updateStatus(UpdateBookingStatusRequest $request, $booking, UpdateBookingStatus $updateBookingStatus)
    {
        $tenant = $this->resolveTenant();

        if (!$booking instanceof Booking) {
            $booking = Booking::findOrFail($booking);
        }

        // Pastikan booking milik tenant yang sedang login
        if (!$tenant || $booking->idtenant !== $tenant->id) {
            abort(404, 'Booking tidak ditemukan.');
        }

        $validated = $request->validated();
        $statusBaru = $validated['status'];

        $result = $updateBookingStatus->execute($booking, $statusBaru, 'owner');

        if (!$result['success']) {
            return back()->withErrors([
                'error' => $result['error'],
            ]);
        }

        $label = match ($statusBaru) {
            'paid'       => 'lunas / dikonfirmasi',
            'completed'  => 'selesai',
            'cancelled'  => 'dibatalkan',
            default      => $statusBaru,
        };

        return back()->with('sukses', "Booking atas nama {$booking->namapelanggan} berhasil ditandai sebagai {$label}.");
    }

    /**
     * Walk-in booking creation by owner (directly from Calendar / Booking module)
     */
    public function walkinStore(Request $request, CreateWalkInBooking $createWalkInBooking)
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

        $booking = $createWalkInBooking->execute($tenant, $validated);

        if (!$booking) {
            return back()->withErrors(['error' => 'Slot waktu yang dipilih tidak tersedia atau sudah terisi.']);
        }

        return back()->with('sukses', "Walk-in booking atas nama {$booking->namapelanggan} berhasil dibuat!");
    }

    /**
     * Dapatkan daftar slot jadwal yang tersedia untuk reschedule booking ini oleh Owner.
     */
    public function getAvailableSlots(Request $request, $booking)
    {
        $tenant = $this->resolveTenant();
        if (!$booking instanceof Booking) {
            $booking = Booking::findOrFail($booking);
        }

        if (!$tenant || $booking->idtenant !== $tenant->id) {
            return response()->json(['success' => false, 'message' => 'Booking tidak ditemukan.'], 404);
        }

        $tanggal = $request->query('tanggal');
        if (!$tanggal) {
            $tanggal = Carbon::today()->toDateString();
        } else {
            $tanggal = Carbon::parse($tanggal)->toDateString();
        }

        $service = $booking->layanan;
        $slots = app(\App\Actions\Schedule\GetAvailableSchedules::class)
            ->getSlotsForReschedule($tenant, (int) $booking->idlayanan, $tanggal, (int) $booking->id);

        $mappedSlots = $slots->map(function ($item) use ($service, $booking) {
            $slot = $item['schedule'];
            $price = $slot->harga_override ?? ($service ? $service->harga : 0);

            return [
                'id'              => $slot->id,
                'jam_mulai'       => $item['jam_mulai'],
                'jam_selesai'     => $item['jam_selesai'],
                'formatted_time'  => $item['jam_mulai'] . ' - ' . $item['jam_selesai'],
                'price'           => $price,
                'formatted_price' => 'Rp ' . number_format($price, 0, ',', '.'),
                'is_available'    => $item['is_available'],
                'is_current'      => $item['is_current'],
            ];
        });

        return response()->json([
            'success'      => true,
            'tanggal'      => $tanggal,
            'service_name' => $service?->namalayanan ?? 'Layanan',
            'current_slot_id' => $booking->idschedule,
            'slots'        => $mappedSlots,
        ]);
    }

    /**
     * Reschedule / Ubah Jadwal Booking oleh Owner (Walk-in & On-site Direct Reschedule)
     * Tidak memerlukan token dari pelanggan.
     */
    public function reschedule(RescheduleBookingRequest $request, $booking, RescheduleBooking $rescheduleBooking)
    {
        $tenant = $this->resolveTenant();
        if (!$booking instanceof Booking) {
            $booking = Booking::findOrFail($booking);
        }

        if (!$tenant || $booking->idtenant !== $tenant->id) {
            abort(404, 'Booking tidak ditemukan.');
        }

        $validated = $request->validated();
        $newScheduleId = (int) $validated['schedule_id'];
        $alasan = $validated['alasan'] ?? 'Permintaan langsung customer walk-in di studio';

        $result = $rescheduleBooking->execute($booking, $newScheduleId, 'owner', $alasan);

        if (!$result['success']) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['error']], 422);
            }
            return back()->withErrors(['error' => $result['error']]);
        }

        $tglFormatted = Carbon::parse($booking->tanggalbooking)->translatedFormat('l, d F Y');
        $jamFormatted = substr($booking->jam, 0, 5) . ' WIB';
        $successMsg = "Jadwal booking #{$booking->booking_code} ({$booking->namapelanggan}) berhasil diubah ke {$tglFormatted} pukul {$jamFormatted}!";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'booking' => $booking->fresh()->load('layanan'),
            ]);
        }

        return back()->with('sukses', $successMsg);
    }
}


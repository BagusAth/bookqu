<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

use App\Models\Booking;
use App\Models\BookingLog;
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
    public function updateStatus(Request $request, $booking)
    {
        $tenant = $this->resolveTenant();

        if (!$booking instanceof Booking) {
            $booking = Booking::findOrFail($booking);
        }

        // Pastikan booking milik tenant yang sedang login
        if (!$tenant || $booking->idtenant !== $tenant->id) {
            abort(404, 'Booking tidak ditemukan.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:paid,completed,cancelled'],
        ]);

        $statusLama = $booking->status;
        $statusBaru = $validated['status'];

        $transisi = [
            'paid'    => ['completed', 'cancelled'],
            'pending' => ['paid', 'completed', 'cancelled'],
        ];

        if (!in_array($statusBaru, $transisi[$statusLama] ?? [])) {
            return back()->withErrors([
                'error' => "Status tidak dapat diubah dari '{$statusLama}' ke '{$statusBaru}'.",
            ]);
        }

        $booking->update(['status' => $statusBaru]);

        // If marked as paid or completed, ensure management tokens exist and payment status is updated
        if ($statusBaru === 'paid' || $statusBaru === 'completed') {
            if (!$booking->booking_code) {
                $booking->assignManagementTokens();
            }
            if ($booking->payment && $booking->payment->status !== 'sukses') {
                $booking->payment->update(['status' => 'sukses']);
            }
        }

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

        // Notifikasi perubahan status
        try {
            $booking->load(['tenant.user', 'layanan']);
            $owner = $booking->tenant?->user;
            if ($owner) {
                $owner->notify(new \App\Notifications\BookingStatusChangedOwnerNotification(
                    $booking,
                    $statusBaru,
                    ['updated_by' => 'owner']
                ));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Owner update status notification failed: ' . $e->getMessage());
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

        // Cari slot jadwal pada tanggal tersebut untuk layanan yang sama
        $slots = Schedule::where('idtenant', $tenant->id)
            ->where('idlayanan', $booking->idlayanan)
            ->whereDate('tanggal', $tanggal)
            ->where('status', 'tersedia')
            ->orderBy('jam_mulai')
            ->get();

        // Cari slot ID yang sedang terisi oleh booking aktif lain (selain booking ini)
        $takenSlotIds = Booking::where('idtenant', $tenant->id)
            ->whereDate('tanggalbooking', $tanggal)
            ->whereIn('status', ['pending', 'paid', 'completed'])
            ->where('id', '!=', $booking->id)
            ->pluck('idschedule')
            ->filter()
            ->toArray();

        $service = $booking->layanan;

        $mappedSlots = $slots->map(function ($slot) use ($takenSlotIds, $service, $booking) {
            $isOccupied = in_array($slot->id, $takenSlotIds);
            $isCurrentSlot = ($slot->id === $booking->idschedule);
            $price = $slot->harga_override ?? ($service ? $service->harga : 0);

            return [
                'id'              => $slot->id,
                'jam_mulai'       => substr($slot->jam_mulai, 0, 5),
                'jam_selesai'     => substr($slot->jam_selesai, 0, 5),
                'formatted_time'  => substr($slot->jam_mulai, 0, 5) . ' - ' . substr($slot->jam_selesai, 0, 5),
                'price'           => $price,
                'formatted_price' => 'Rp ' . number_format($price, 0, ',', '.'),
                'is_available'    => !$isOccupied,
                'is_current'      => $isCurrentSlot,
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
    public function reschedule(Request $request, $booking)
    {
        $tenant = $this->resolveTenant();
        if (!$booking instanceof Booking) {
            $booking = Booking::findOrFail($booking);
        }

        if (!$tenant || $booking->idtenant !== $tenant->id) {
            abort(404, 'Booking tidak ditemukan.');
        }

        // Booking yang sudah dibatalkan atau selesai tidak dapat diubah jadwalnya
        if (in_array($booking->status, ['cancelled', 'refunded'])) {
            $msg = 'Booking dengan status "' . $booking->status . '" tidak dapat dijadwalkan ulang.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['error' => $msg]);
        }

        $validated = $request->validate([
            'schedule_id' => ['required', 'integer'],
            'alasan'      => ['nullable', 'string', 'max:255'],
        ]);

        $newScheduleId = (int) $validated['schedule_id'];
        $alasan = $validated['alasan'] ?? 'Permintaan langsung customer walk-in di studio';

        $oldDate       = $booking->tanggalbooking instanceof Carbon
            ? $booking->tanggalbooking->toDateString()
            : Carbon::parse($booking->tanggalbooking)->toDateString();
        $oldTime       = substr($booking->jam, 0, 5);
        $oldScheduleId = $booking->idschedule;

        if ($oldScheduleId === $newScheduleId) {
            $msg = 'Slot jadwal yang dipilih sama dengan jadwal booking saat ini.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['error' => $msg]);
        }

        try {
            DB::transaction(function () use ($booking, $newScheduleId, $tenant, $oldDate, $oldTime, $oldScheduleId, $alasan) {
                // Lock and validate new schedule slot
                $newSchedule = Schedule::where('id', $newScheduleId)
                    ->where('idtenant', $tenant->id)
                    ->where('status', 'tersedia')
                    ->lockForUpdate()
                    ->first();

                if (!$newSchedule) {
                    throw new \Exception('Slot jadwal baru tidak ditemukan atau statusnya tidak tersedia.');
                }

                // Check conflict with other active bookings
                $slotTaken = Booking::where('idschedule', $newScheduleId)
                    ->whereIn('status', ['pending', 'paid', 'completed'])
                    ->where('id', '!=', $booking->id)
                    ->exists();

                if ($slotTaken) {
                    throw new \Exception('Slot jadwal yang dipilih sudah terisi oleh pelanggan lain.');
                }

                $newDate = $newSchedule->tanggal instanceof Carbon
                    ? $newSchedule->tanggal->toDateString()
                    : Carbon::parse($newSchedule->tanggal)->toDateString();
                $newTime = substr($newSchedule->jam_mulai, 0, 5);

                // Update booking
                $booking->update([
                    'idschedule'               => $newScheduleId,
                    'tanggalbooking'           => $newDate,
                    'jam'                      => $newSchedule->jam_mulai,
                    'rescheduled_from_date'    => $oldDate,
                    'rescheduled_from_time'    => $oldTime,
                    'rescheduled_from_schedule'=> $oldScheduleId,
                ]);

                // Record BookingLog
                BookingLog::record(
                    $booking->id,
                    'rescheduled',
                    'Jadwal diubah oleh Owner (Walk-in / On-site): ' . $alasan,
                    [
                        'from_date'     => $oldDate,
                        'from_time'     => $oldTime,
                        'from_schedule' => $oldScheduleId,
                        'to_date'       => $newDate,
                        'to_time'       => $newSchedule->jam_mulai,
                        'to_schedule'   => $newScheduleId,
                        'actor'         => auth()->user()->namalengkap ?? 'Owner',
                        'role'          => 'owner',
                    ]
                );

                // Clear caches for old and new dates
                $this->clearBookingAvailabilityCache((int) $tenant->id, (int) $booking->idlayanan, $oldDate);
                $this->clearBookingAvailabilityCache((int) $tenant->id, (int) $booking->idlayanan, $newDate);
            });

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

        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}


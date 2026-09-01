<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\OwnerBlockedDate;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Traits\ClearsBookingCache;

class OwnerScheduleController extends Controller
{
    use ClearsBookingCache;
    use \App\Traits\ResolvesOwnerTenant;

    /**
     * Halaman manajemen jadwal.
     */
    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $idtenant = $tenant->id;

        // Navigasi minggu
        $offsetminggu = (int) $request->input('minggu', 0);
        $awalminggu = Carbon::now()->startOfWeek()->addWeeks($offsetminggu);
        $akhirminggu = $awalminggu->copy()->endOfWeek();

        // Daftar hari dalam minggu
        $daftarhari = [];
        for ($i = 0; $i < 7; $i++) {
            $daftarhari[] = $awalminggu->copy()->addDays($i);
        }

        // Slot jadwal minggu ini
        $jadwalminggu = Schedule::where('idtenant', $idtenant)
            ->whereBetween('tanggal', [$awalminggu->format('Y-m-d'), $akhirminggu->format('Y-m-d')])
            ->with(['layanan', 'bookings'])
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get()
            ->groupBy(function ($jadwal) {
                return $jadwal->tanggal->format('Y-m-d');
            });

        // Statistik
        $totalslot = Schedule::where('idtenant', $idtenant)
            ->whereBetween('tanggal', [$awalminggu->format('Y-m-d'), $akhirminggu->format('Y-m-d')])
            ->count();

        $slotterboking = Schedule::where('schedules.idtenant', $idtenant)
            ->whereBetween('tanggal', [$awalminggu->format('Y-m-d'), $akhirminggu->format('Y-m-d')])
            ->whereHas('bookings', fn($q) => $q->whereIn('status', ['paid', 'completed']))
            ->count();

        $rasioterboking = $totalslot > 0 ? round(($slotterboking / $totalslot) * 100) : 0;

        $estimasirevenue = Booking::where('bookings.idtenant', $idtenant)
            ->whereBetween('tanggalbooking', [$awalminggu->format('Y-m-d'), $akhirminggu->format('Y-m-d')])
            ->whereIn('status', ['paid', 'completed'])
            ->with('layanan')
            ->get()
            ->sum(fn($b) => $b->layanan->harga ?? 0);

        $bookingberikutnya = Booking::where('idtenant', $idtenant)
            ->where('tanggalbooking', '>=', Carbon::today()->format('Y-m-d'))
            ->where('status', 'paid')
            ->orderBy('tanggalbooking')
            ->orderBy('jam')
            ->first();

        // Aktivitas terbaru
        $aktivitasjadwal = Booking::where('bookings.idtenant', $idtenant)
            ->with('layanan')
            ->orderByDesc('bookings.created_at')
            ->limit(5)
            ->get();

        // Daftar layanan untuk referensi harga
        $daftarlayanan = Service::where('idtenant', $idtenant)->get();

        $blockedDates = OwnerBlockedDate::where('idtenant', $idtenant)
            ->orderByDesc('tanggal')
            ->limit(10)
            ->get();

        return view('owner.owner-schedule', compact(
            'tenant',
            'daftarhari',
            'jadwalminggu',
            'awalminggu',
            'akhirminggu',
            'offsetminggu',
            'totalslot',
            'rasioterboking',
            'estimasirevenue',
            'bookingberikutnya',
            'aktivitasjadwal',
            'daftarlayanan',
            'blockedDates',
        ));
    }

    /**
     * Simpan bulk slots jadwal.
     */
    public function bulkStore(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $datavalid = $request->validate([
            'jenisslot' => 'required|in:harian,rentang',
            'idlayanan' => [
                'required',
                Rule::exists('services', 'id')->where('idtenant', $tenant->id),
            ],
            'tanggal' => 'nullable|date',
            'tanggalmulai' => 'nullable|date',
            'tanggalselesai' => 'nullable|date|after_or_equal:tanggalmulai',
            'jammulai' => 'required|date_format:H:i',
            'jamselesai' => 'required|date_format:H:i|after:jammulai',
            'intervalslot' => 'required|integer|min:5|max:480',
        ]);

        // Validasi silang: intervalslot harus sama dengan durasi layanan
        $layanan = Service::where('idtenant', $tenant->id)->find($datavalid['idlayanan']);

        if (!$layanan) {
            return redirect('/owner/schedule')->withErrors(['idlayanan' => 'Program tidak ditemukan.']);
        }

        if ((int) $datavalid['intervalslot'] !== (int) $layanan->durasi) {
            return redirect('/owner/schedule')->withErrors([
                'intervalslot' => 'Durasi slot harus sesuai dengan durasi program (' . $layanan->durasi . ' menit).',
            ]);
        }

        // Tentukan daftar tanggal
        $daftartanggal = [];
        if ($datavalid['jenisslot'] === 'harian') {
            $daftartanggal[] = $datavalid['tanggal'];
        } else {
            $tanggalcursor = Carbon::parse($datavalid['tanggalmulai']);
            $tanggalakhir = Carbon::parse($datavalid['tanggalselesai']);
            while ($tanggalcursor->lte($tanggalakhir)) {
                $daftartanggal[] = $tanggalcursor->format('Y-m-d');
                $tanggalcursor->addDay();
            }
        }

        $blocked = OwnerBlockedDate::where('idtenant', $tenant->id)
            ->whereIn('tanggal', $daftartanggal)
            ->pluck('tanggal')
            ->map(fn($tanggal) => Carbon::parse($tanggal)->format('Y-m-d'))
            ->toArray();

        $daftartanggal = array_values(array_filter($daftartanggal, fn($tanggal) => !in_array($tanggal, $blocked, true)));

        // Generate slots per tanggal
        $jumlahslot = 0;
        $intervalslot = (int) $datavalid['intervalslot'];

        foreach ($daftartanggal as $tanggalnya) {
            $jamcursor = Carbon::parse($tanggalnya . ' ' . $datavalid['jammulai']);
            $jamakhir = Carbon::parse($tanggalnya . ' ' . $datavalid['jamselesai']);

            $hargaOverride = null;
            if ($layanan && Carbon::parse($tanggalnya)->isWeekend()) {
                if ($tenant->weekend_price_type === 'multiplier' && $tenant->weekend_price_value) {
                    $hargaOverride = $layanan->harga * $tenant->weekend_price_value;
                } elseif ($tenant->weekend_price_type === 'fixed' && $tenant->weekend_price_value) {
                    $hargaOverride = $tenant->weekend_price_value;
                }
            }

            while ($jamcursor->copy()->addMinutes($intervalslot)->lte($jamakhir)) {
                $jammulainya = $jamcursor->format('H:i:s');
                // Subtract 1 minute to meet the requirement (e.g. 11:00 - 11:59 instead of 12:00)
                $jamselesainya = $jamcursor->copy()->addMinutes($intervalslot)->subMinute()->format('H:i:s');

                Schedule::create([
                    'idtenant' => $tenant->id,
                    'idlayanan' => $datavalid['idlayanan'],
                    'tanggal' => $tanggalnya,
                    'jam_mulai' => $jammulainya,
                    'jam_selesai' => $jamselesainya,
                    'harga_override' => $hargaOverride,
                    'status' => 'tersedia',
                ]);

                $jumlahslot++;
                $jamcursor->addMinutes($intervalslot);
            }
        }

        if ($jumlahslot > 0) {
            $this->clearScheduleCache($tenant->id, $datavalid['idlayanan'], $daftartanggal);
        }

        return redirect('/owner/schedule')->with('sukses', $jumlahslot . ' slot jadwal berhasil dibuat untuk ' . count($daftartanggal) . ' hari!');
    }

    /**
     * Hapus slot jadwal.
     */
    public function destroy(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $slot = Schedule::where('idtenant', $tenant->id)->findOrFail($id);

        $adaBooking = $slot->bookings()->where('status', '!=', 'cancelled')->exists();
        if ($adaBooking) {
            abort(403, 'Slot memiliki booking aktif.');
        }

        $idlayanan = $slot->idlayanan;
        $tanggal = $slot->tanggal->format('Y-m-d');
        $slot->delete();

        $this->clearScheduleCache($tenant->id, $idlayanan, [$tanggal]);

        return redirect('/owner/schedule')->with('sukses', 'Slot berhasil dihapus.');
    }

    public function updateDefaultPricing(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $data = $request->validate([
            'idlayanan' => [
                'required',
                Rule::exists('services', 'id')->where('idtenant', $tenant->id),
            ],
            'harga' => 'required|numeric|min:0',
        ]);

        Service::where('idtenant', $tenant->id)
            ->where('id', $data['idlayanan'])
            ->update(['harga' => $data['harga']]);

        $this->clearServiceCache($tenant->id, $data['idlayanan']);

        return redirect('/owner/schedule')->with('sukses', 'Harga default berhasil diperbarui.');
    }

    public function updateAvailability(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $data = $request->validate([
            'tanggal_block' => 'nullable|date',
            'alasan' => 'nullable|string|max:200',
            'weekend_price_type' => 'required|in:none,multiplier,fixed',
            'weekend_price_value' => 'nullable|numeric|min:0',
        ]);

        if (!empty($data['tanggal_block'])) {
            OwnerBlockedDate::updateOrCreate(
                ['idtenant' => $tenant->id, 'tanggal' => $data['tanggal_block']],
                ['alasan' => $data['alasan'] ?? null]
            );
        }

        $tenant->weekend_price_type = $data['weekend_price_type'];
        $tenant->weekend_price_value = $data['weekend_price_type'] === 'none'
            ? null
            : ($data['weekend_price_value'] ?? null);
        $tenant->save();

        \Illuminate\Support\Facades\Cache::forget("tenant:slug:{$tenant->slug}");

        return redirect('/owner/schedule')->with('sukses', 'Pengaturan availability berhasil disimpan.');
    }

    public function deleteBlockedDate(int $blockedDate)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        OwnerBlockedDate::where('idtenant', $tenant->id)
            ->where('id', $blockedDate)
            ->delete();

        return redirect('/owner/schedule')->with('sukses', 'Tanggal berhasil dibuka.');
    }
}

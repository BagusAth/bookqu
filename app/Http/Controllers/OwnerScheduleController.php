<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerScheduleController extends Controller
{
    /**
     * Halaman manajemen jadwal.
     */
    public function index(Request $request)
    {
        $iduser = Auth::id();
        $tenant = Tenant::with('user')->where('iduser', $iduser)->first();
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
        ));
    }

    /**
     * Simpan bulk slots jadwal.
     */
    public function bulkStore(Request $request)
    {
        $iduser = Auth::id();
        $tenant = Tenant::where('iduser', $iduser)->first();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $datavalid = $request->validate([
            'jenisslot' => 'required|in:harian,rentang',
            'idlayanan' => 'required|exists:services,id',
            'tanggal' => 'nullable|date',
            'tanggalmulai' => 'nullable|date',
            'tanggalselesai' => 'nullable|date|after_or_equal:tanggalmulai',
            'jammulai' => 'required|date_format:H:i',
            'jamselesai' => 'required|date_format:H:i|after:jammulai',
            'intervalslot' => 'required|integer|in:30,45,60,90,120',
        ]);

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

        // Generate slots per tanggal
        $jumlahslot = 0;
        $intervalslot = (int) $datavalid['intervalslot'];

        foreach ($daftartanggal as $tanggalnya) {
            $jamcursor = Carbon::parse($tanggalnya . ' ' . $datavalid['jammulai']);
            $jamakhir = Carbon::parse($tanggalnya . ' ' . $datavalid['jamselesai']);

            while ($jamcursor->copy()->addMinutes($intervalslot)->lte($jamakhir)) {
                $jammulainya = $jamcursor->format('H:i:s');
                $jamselesainya = $jamcursor->copy()->addMinutes($intervalslot)->format('H:i:s');

                Schedule::create([
                    'idtenant' => $tenant->id,
                    'idlayanan' => $datavalid['idlayanan'],
                    'tanggal' => $tanggalnya,
                    'jam_mulai' => $jammulainya,
                    'jam_selesai' => $jamselesainya,
                    'status' => 'tersedia',
                ]);

                $jumlahslot++;
                $jamcursor->addMinutes($intervalslot);
            }
        }

        return redirect('/owner/schedule')->with('sukses', $jumlahslot . ' slot jadwal berhasil dibuat untuk ' . count($daftartanggal) . ' hari!');
    }
}

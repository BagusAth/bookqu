<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OwnerProgramController extends Controller
{
    /**
     * Halaman daftar program/layanan.
     */
    public function index(Request $request)
    {
        $tenant = Tenant::with('user')->first();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $idtenant = $tenant->id;
        $katakunci = $request->input('katakunci', '');

        $daftarlayanan = Service::where('idtenant', $idtenant)
            ->when($katakunci, function ($query) use ($katakunci) {
                $query->where('namalayanan', 'like', '%' . $katakunci . '%');
            })
            ->withCount('bookings')
            ->orderByDesc('created_at')
            ->paginate(12);

        // Statistik program
        $totallayanan = Service::where('idtenant', $idtenant)->count();
        $ratarataharga = Service::where('idtenant', $idtenant)->avg('harga') ?? 0;
        $totalbookinglayanan = Booking::where('idtenant', $idtenant)
            ->whereMonth('tanggalbooking', Carbon::now()->month)
            ->count();
        $pendapatanlayanan = Booking::where('idtenant', $idtenant)
            ->whereHas('payment', fn($q) => $q->where('status', 'sukses'))
            ->whereMonth('tanggalbooking', Carbon::now()->month)
            ->with('payment')
            ->get()
            ->sum(fn($b) => $b->payment->jumlah ?? 0);

        return view('owner.owner-programs', compact(
            'tenant',
            'daftarlayanan',
            'totallayanan',
            'ratarataharga',
            'totalbookinglayanan',
            'pendapatanlayanan',
            'katakunci',
        ));
    }

    /**
     * Simpan program baru.
     */
    public function store(Request $request)
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $datavalid = $request->validate([
            'namalayanan' => 'required|string|max:255',
            'harga' => 'required|numeric|min:0',
            'durasi' => 'required|integer|min:5|max:480',
            'deskripsi' => 'nullable|string|max:1000',
        ]);

        Service::create([
            'idtenant' => $tenant->id,
            'namalayanan' => $datavalid['namalayanan'],
            'harga' => $datavalid['harga'],
            'durasi' => $datavalid['durasi'],
            'deskripsi' => $datavalid['deskripsi'] ?? null,
        ]);

        return redirect('/owner/programs')->with('sukses', 'Program "' . $datavalid['namalayanan'] . '" berhasil ditambahkan!');
    }
}

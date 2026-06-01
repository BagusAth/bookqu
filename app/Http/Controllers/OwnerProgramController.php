<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OwnerProgramController extends Controller
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
     * Halaman daftar program/layanan.
     */
    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
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
        $tenant = $this->resolveTenant();
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

    /**
     * Update program.
     */
    public function update(Request $request, int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $layanan = Service::where('idtenant', $tenant->id)->findOrFail($id);

        $datavalid = $request->validate([
            'namalayanan' => 'required|string|max:255',
            'harga' => 'required|numeric|min:0',
            'durasi' => 'required|integer|min:5|max:480',
            'deskripsi' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ]);

        $layanan->update([
            'namalayanan' => $datavalid['namalayanan'],
            'harga' => $datavalid['harga'],
            'durasi' => $datavalid['durasi'],
            'deskripsi' => $datavalid['deskripsi'] ?? null,
            'is_active' => (bool) $datavalid['is_active'],
        ]);

        return redirect('/owner/programs')->with('sukses', 'Program "' . $datavalid['namalayanan'] . '" berhasil diperbarui!');
    }

    /**
     * Hapus program.
     */
    public function destroy(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $layanan = Service::where('idtenant', $tenant->id)->findOrFail($id);
        $namalayanan = $layanan->namalayanan;
        $layanan->delete();

        return redirect('/owner/programs')->with('sukses', 'Program "' . $namalayanan . '" berhasil dihapus!');
    }
}

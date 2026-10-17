<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Schedule\BulkCreateSchedules;
use App\Actions\Schedule\DeleteBlockedDate;
use App\Actions\Schedule\DeleteSchedule;
use App\Actions\Schedule\UpdateScheduleAvailability;
use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\BulkCreateScheduleRequest;
use App\Http\Requests\Schedule\UpdateAvailabilityRequest;
use App\Models\Booking;
use App\Models\OwnerBlockedDate;
use App\Models\Schedule;
use App\Models\Service;
use App\Services\Schedule\ScheduleAvailabilityCache;
use App\Traits\ResolvesOwnerTenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OwnerScheduleController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * Halaman manajemen jadwal.
     */
    public function index(Request $request): View
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
            ->with(['layanan', 'bookings.layanan'])
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

        return view('owner.schedule', compact(
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
    public function bulkStore(BulkCreateScheduleRequest $request, BulkCreateSchedules $bulkCreateSchedules): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $result = $bulkCreateSchedules->execute($tenant, $request->validated());

        if (!$result['success']) {
            return redirect('/owner/schedule')->withErrors([
                $result['error_field'] ?? 'idlayanan' => $result['error'],
            ]);
        }

        return redirect('/owner/schedule')->with(
            'sukses',
            $result['created_count'] . ' slot jadwal berhasil dibuat untuk ' . $result['days_count'] . ' hari!'
        );
    }

    /**
     * Hapus slot jadwal.
     */
    public function destroy($id, DeleteSchedule $deleteSchedule): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $result = $deleteSchedule->execute($tenant, $id);

        if (!$result['success']) {
            abort($result['code'] ?? 403, $result['error'] ?? 'Tidak dapat menghapus slot.');
        }

        return redirect('/owner/schedule')->with('sukses', 'Slot berhasil dihapus.');
    }

    /**
     * Perbarui harga default layanan.
     */
    public function updateDefaultPricing(Request $request, ScheduleAvailabilityCache $cacheService): RedirectResponse
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

        $cacheService->clearServiceCache((int) $tenant->id, (int) $data['idlayanan']);

        return redirect('/owner/schedule')->with('sukses', 'Harga default berhasil diperbarui.');
    }

    /**
     * Perbarui pengaturan ketersediaan dan tanggal blokir.
     */
    public function updateAvailability(
        UpdateAvailabilityRequest $request,
        UpdateScheduleAvailability $updateScheduleAvailability
    ): RedirectResponse {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $updateScheduleAvailability->execute($tenant, $request->validated());

        return redirect('/owner/schedule')->with('sukses', 'Pengaturan availability berhasil disimpan.');
    }

    /**
     * Hapus tanggal blokir.
     */
    public function deleteBlockedDate($blockedDate, DeleteBlockedDate $deleteBlockedDate): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $result = $deleteBlockedDate->execute($tenant, $blockedDate);

        if (!$result['success']) {
            abort($result['code'] ?? 404, $result['error'] ?? 'Tanggal blokir tidak ditemukan.');
        }

        return redirect('/owner/schedule')->with('sukses', 'Tanggal blokir berhasil dihapus.');
    }
}

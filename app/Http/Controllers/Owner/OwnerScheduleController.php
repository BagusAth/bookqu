<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Schedule\BulkCreateSchedules;
use App\Actions\Schedule\DeleteBlockedDate;
use App\Actions\Schedule\DeleteSchedule;
use App\Actions\Schedule\GetSlotRunway;
use App\Actions\Schedule\PriceSlots;
use App\Actions\Schedule\UpdateScheduleAvailability;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingTimeRange;
use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\BulkCreateScheduleRequest;
use App\Http\Requests\Schedule\UpdateAvailabilityRequest;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\PeakHourRule;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\SpecialDatePrice;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use App\Traits\ResolvesOwnerTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
    public function index(Request $request, GetSlotRunway $getSlotRunway, PriceSlots $priceSlots): View
    {
        $tenant = $this->resolveTenant() ?? abort(404);
        $tabs = ['hours' => 'Jam Buka', 'prices' => 'Harga', 'closed' => 'Libur & Tutup', 'slots' => 'Slot'];
        $requestedTab = $request->string('tab')->toString();
        $tab = isset($tabs[$requestedTab]) ? $requestedTab : 'hours';
        if ($request->session()->has('blocked_date_conflicts')) {
            $tab = 'closed';
        }

        $weekdays = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $runway = $getSlotRunway->execute($tenant);
        $daftarlayanan = Service::where('idtenant', $tenant->id)->orderBy('namalayanan')->get();
        $data = compact('tenant', 'tabs', 'tab', 'weekdays', 'runway', 'daftarlayanan');

        if ($tab === 'hours') {
            $data['openingHours'] = OpeningHour::where('idtenant', $tenant->id)->orderBy('opens_at')->get()->groupBy('day_of_week')
                ->map(fn ($intervals) => $intervals->map(fn ($interval) => [
                    'opens_at' => substr((string) $interval->opens_at, 0, 5),
                    'closes_at' => substr((string) $interval->closes_at, 0, 5),
                ])->values());
        } elseif ($tab === 'prices') {
            $data['peakRules'] = PeakHourRule::where('idtenant', $tenant->id)->with('services')->orderBy('name')->get();
            $data['specialDates'] = SpecialDatePrice::where('idtenant', $tenant->id)->with('service')->orderBy('tanggal')->get();
        } elseif ($tab === 'closed') {
            $data['blockedDates'] = $tenant->blockedDates()->orderByDesc('tanggal')->get();
        } else {
            $data += $this->slotPage($request, $tenant, $priceSlots);
        }

        return view('owner.schedule', $data);
    }

    /** @return array<string, mixed> */
    private function slotPage(Request $request, Tenant $tenant, PriceSlots $prices): array
    {
        $offsetminggu = max(-520, min(520, $request->integer('minggu')));
        $awalminggu = Carbon::now()->startOfWeek()->addWeeks($offsetminggu);
        $akhirminggu = $awalminggu->copy()->endOfWeek();
        $selectedService = $request->integer('layanan');
        $slots = Schedule::where('idtenant', $tenant->id)
            ->whereBetween('tanggal', [$awalminggu->toDateString(), $akhirminggu->toDateString()])
            ->when($selectedService > 0, fn (Builder $query) => $query->where('idlayanan', $selectedService))
            ->with('layanan')
            ->withExists(['bookings as occupied' => fn (Builder $query) => BookingRules::applyOccupiesSlotCondition($query)])
            ->orderBy('tanggal')->orderBy('jam_mulai')->get();
        $slotPrices = $prices->execute($tenant, $slots);
        $slotTimes = $slots->mapWithKeys(fn (Schedule $slot): array => [
            $slot->id => BookingTimeRange::forSlot(Carbon::parse($slot->tanggal)->toDateString(), (string) $slot->jam_mulai, (string) $slot->jam_selesai)->label(),
        ]);
        $slotLabels = $slots->mapWithKeys(fn (Schedule $slot): array => [
            $slot->id => $slot->getAttribute('occupied') ? 'Terisi' : ($slot->status === 'tersedia' ? 'Terbuka' : 'Tutup'),
        ]);

        return compact('slots', 'slotPrices', 'slotTimes', 'slotLabels', 'selectedService', 'offsetminggu', 'awalminggu', 'akhirminggu');
    }

    /**
     * Simpan bulk slots jadwal.
     */
    public function bulkStore(BulkCreateScheduleRequest $request, BulkCreateSchedules $bulkCreateSchedules): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $result = $bulkCreateSchedules->execute($tenant, $request->validated());

        $destination = $request->query('tab') === 'slots' ? route('owner.schedule', ['tab' => 'slots']) : '/owner/schedule';
        if (! $result['success']) {
            return redirect($destination)->withErrors([
                $result['error_field'] ?? 'idlayanan' => $result['error'],
            ]);
        }

        return redirect($destination)->with(
            'sukses',
            $result['created_count'].' slot jadwal berhasil dibuat untuk '.$result['days_count'].' hari!'
        );
    }

    /**
     * Hapus slot jadwal.
     */
    public function destroy(Request $request, $id, DeleteSchedule $deleteSchedule): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $result = $deleteSchedule->execute($tenant, $id);

        if (! $result['success']) {
            abort($result['code'] ?? 403, $result['error'] ?? 'Tidak dapat menghapus slot.');
        }

        $destination = match ($request->input('return_to')) {
            'calendar' => '/owner/calendar',
            'slots' => route('owner.schedule', ['tab' => 'slots']),
            default => '/owner/schedule',
        };

        return redirect($destination)->with('sukses', 'Slot berhasil dihapus.');
    }

    /**
     * Perbarui harga default layanan.
     */
    public function updateDefaultPricing(Request $request, ScheduleAvailabilityCache $cacheService): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $data = $request->validate([
            'idlayanan' => [
                'required',
                Rule::exists('services', 'id')->where('idtenant', $tenant->id),
            ],
            'harga' => 'required|numeric|min:0',
        ]);

        Service::where('idtenant', $tenant->id)->findOrFail($data['idlayanan'])
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
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $result = $updateScheduleAvailability->execute($tenant, $request->validated());
        $tab = $request->query('tab');
        $destination = in_array($tab, ['prices', 'closed'], true) ? route('owner.schedule', ['tab' => $tab]) : '/owner/schedule';

        if (! $result['success']) {
            return redirect($destination)
                ->withErrors(['tanggal_block' => $result['error']])
                ->withInput()
                ->with('blocked_date_conflicts', [
                    'tanggal' => $result['blocked_date_value'],
                    'bookings' => $result['conflicting_bookings']->map(fn (Booking $booking): array => [
                        'booking_code' => (string) $booking->booking_code,
                        'namapelanggan' => (string) $booking->namapelanggan,
                        'jam' => substr((string) $booking->jam, 0, 5),
                        'layanan' => $booking->layanan instanceof Service ? $booking->layanan->namalayanan : '-',
                    ])->values()->all(),
                ]);
        }

        return redirect($destination)->with('sukses', 'Pengaturan jadwal berhasil disimpan.');
    }

    /**
     * Hapus tanggal blokir.
     */
    public function deleteBlockedDate(Request $request, $blockedDate, DeleteBlockedDate $deleteBlockedDate): RedirectResponse
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $result = $deleteBlockedDate->execute($tenant, $blockedDate);

        if (! $result['success']) {
            abort($result['code'] ?? 404, $result['error'] ?? 'Tanggal blokir tidak ditemukan.');
        }

        $destination = $request->query('tab') === 'closed' ? route('owner.schedule', ['tab' => 'closed']) : '/owner/schedule';

        return redirect($destination)->with('sukses', 'Tanggal dibuka kembali.');
    }
}

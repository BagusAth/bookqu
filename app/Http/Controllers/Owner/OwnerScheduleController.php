<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Schedule\BulkCreateSchedules;
use App\Actions\Schedule\ChangeSelectedSlots;
use App\Actions\Schedule\DeleteBlockedDate;
use App\Actions\Schedule\DeleteSchedule;
use App\Actions\Schedule\GetScheduleOverview;
use App\Actions\Schedule\GetSlotRunway;
use App\Actions\Schedule\PriceSlots;
use App\Actions\Schedule\UpdateScheduleAvailability;
use App\Actions\Schedule\UpdateSlot;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingTimeRange;
use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\BulkCreateScheduleRequest;
use App\Http\Requests\Schedule\ChangeSelectedSlotsRequest;
use App\Http\Requests\Schedule\UpdateAvailabilityRequest;
use App\Http\Requests\Schedule\UpdateSlotRequest;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\PeakHourRule;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\SpecialDatePrice;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use App\Support\Rupiah;
use App\Traits\ResolvesOwnerTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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
    public function index(Request $request, GetSlotRunway $getSlotRunway, GetScheduleOverview $getScheduleOverview, PriceSlots $priceSlots): View
    {
        $tenant = $this->resolveTenant() ?? abort(404);
        $tabs = ['hours' => 'Jam buka', 'prices' => 'Harga', 'closed' => 'Libur & tutup', 'slots' => 'Slot'];
        $requestedTab = $request->string('tab')->toString();
        $tab = isset($tabs[$requestedTab]) ? $requestedTab : 'hours';
        if ($request->session()->has('blocked_date_conflicts')) {
            $tab = 'closed';
        }

        $weekdays = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $runway = $getSlotRunway->execute($tenant);
        $overview = $getScheduleOverview->execute($tenant, $runway);
        $daftarlayanan = Service::where('idtenant', $tenant->id)->orderBy('namalayanan')->get();
        $maxDaysAhead = BookingRules::maximumAdvanceDays($tenant);
        $data = compact('tenant', 'tabs', 'tab', 'weekdays', 'runway', 'overview', 'daftarlayanan', 'maxDaysAhead');

        if ($tab === 'hours') {
            $data['openingHours'] = $this->openingHours($tenant);
        } elseif ($tab === 'prices') {
            $data['peakRules'] = PeakHourRule::where('idtenant', $tenant->id)->with('services')->orderBy('name')->get();
            $data['specialDates'] = SpecialDatePrice::where('idtenant', $tenant->id)->with('service')->orderBy('tanggal')->get();
            $data['peakRows'] = $data['peakRules']->map(fn (PeakHourRule $rule): array => [
                'rule' => $rule,
                'days' => self::dayRange((array) $rule->days),
                'time' => str_replace(':', '.', substr((string) $rule->starts_at, 0, 5)).'–'.str_replace(':', '.', substr((string) $rule->ends_at, 0, 5)),
                'extra' => '+'.Rupiah::format($rule->extra_amount),
                'services' => $rule->services->isEmpty() ? 'Semua layanan' : ($rule->services->count() === 1 ? (string) $rule->services->first()->namalayanan : $rule->services->count().' layanan'),
                'services_title' => $rule->services->pluck('namalayanan')->join(', '),
            ])->all();
        } elseif ($tab === 'closed') {
            $data['blockedDates'] = $tenant->blockedDates()->orderByDesc('tanggal')->get();
            // Weekly days off come from the opening hours (prototype: "Minggu, setiap minggu").
            $openDays = array_keys($this->openingHours($tenant));
            $data['weeklyClosed'] = $openDays === [] ? [] : array_values(array_diff_key($weekdays, array_flip($openDays)));
        } else {
            $data += $this->slotPage($request, $tenant, $priceSlots);
            $data['openingHours'] = $this->openingHours($tenant);
        }

        return view('owner.schedule', $data);
    }

    /**
     * Short day label for a set of ISO weekdays: "Sen–Jum", "Sab–Min", "Sen, Rab".
     *
     * @param  array<int, int|string>  $days
     */
    public static function dayRange(array $days): string
    {
        $names = [1 => 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        $days = collect($days)->map(fn ($day): int => (int) $day)->filter(fn (int $day): bool => isset($names[$day]))->unique()->sort()->values();
        if ($days->count() === 7) {
            return 'Setiap hari';
        }
        $groups = [];
        foreach ($days as $day) {
            $last = array_key_last($groups);
            if ($last !== null && end($groups[$last]) === $day - 1) {
                $groups[$last][] = $day;
            } else {
                $groups[] = [$day];
            }
        }

        return collect($groups)->map(fn (array $group): string => count($group) > 2
            ? $names[$group[0]].'–'.$names[end($group)]
            : collect($group)->map(fn (int $day): string => $names[$day])->join(', '))->join(', ');
    }

    /** @return array<int, list<array{opens_at: string, closes_at: string}>> */
    private function openingHours(Tenant $tenant): array
    {
        return OpeningHour::where('idtenant', $tenant->id)->orderBy('opens_at')->get()->groupBy('day_of_week')
            ->map(fn ($intervals) => $intervals->map(fn (OpeningHour $interval): array => [
                'opens_at' => substr((string) $interval->opens_at, 0, 5),
                'closes_at' => substr((string) $interval->closes_at, 0, 5),
            ])->values()->all())->all();
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
            ->with(['bookings' => fn ($query) => BookingRules::applyOccupiesSlotCondition($query->getQuery())->select(['id', 'idschedule', 'booking_code', 'namapelanggan'])])
            ->orderBy('tanggal')->orderBy('jam_mulai')->get();
        $slotPriceNotes = $prices->explain($tenant, $slots);
        $slotPrices = array_map(fn (array $price): int => $price['price'], $slotPriceNotes);
        $now = Carbon::now('Asia/Jakarta');
        $slotStates = $slots->mapWithKeys(function (Schedule $slot) use ($now): array {
            $date = Carbon::parse($slot->tanggal)->toDateString();
            $ended = Carbon::parse($date.' '.$slot->jam_selesai, 'Asia/Jakarta')->lt($now);
            $names = $slot->bookings->pluck('namapelanggan')->filter()->unique()->join(', ');

            return [$slot->id => match (true) {
                (bool) $slot->getAttribute('occupied') => ['label' => 'Terisi'.($names !== '' ? ' · '.$names : ''), 'tone' => 'brand', 'state' => 'booked'],
                $ended => ['label' => 'Lewat', 'tone' => null, 'state' => 'past'],
                $slot->status !== 'tersedia' => ['label' => 'Ditutup', 'tone' => 'danger', 'state' => 'closed'],
                default => ['label' => 'Kosong', 'tone' => null, 'state' => 'open'],
            }];
        });
        $today = $now->toDateString();
        $tomorrow = $now->copy()->addDay()->toDateString();
        $dayHeadings = $slots->map(fn (Schedule $slot): string => Carbon::parse($slot->tanggal)->toDateString())->unique()
            ->mapWithKeys(fn (string $date): array => [$date => ($date === $today ? 'Hari ini · ' : ($date === $tomorrow ? 'Besok · ' : ''))
                .Carbon::parse($date)->locale('id')->translatedFormat('l, j M')])->all();
        $weekLabel = $awalminggu->month === $akhirminggu->month
            ? $awalminggu->format('j').'–'.$akhirminggu->locale('id')->translatedFormat('j M')
            : $awalminggu->locale('id')->translatedFormat('j M').' – '.$akhirminggu->locale('id')->translatedFormat('j M');
        $slotTimes = $slots->mapWithKeys(fn (Schedule $slot): array => [
            $slot->id => BookingTimeRange::forSlot(Carbon::parse($slot->tanggal)->toDateString(), (string) $slot->jam_mulai, (string) $slot->jam_selesai)->label(),
        ]);
        $slotLabels = $slots->mapWithKeys(fn (Schedule $slot): array => [
            $slot->id => $slot->getAttribute('occupied') ? 'Terisi' : ($slot->status === 'tersedia' ? 'Terbuka' : 'Tutup'),
        ]);

        return compact('slots', 'slotPrices', 'slotPriceNotes', 'slotTimes', 'slotLabels', 'slotStates', 'dayHeadings', 'weekLabel', 'selectedService', 'offsetminggu', 'awalminggu', 'akhirminggu');
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

    public function updateSlot(UpdateSlotRequest $request, int $slot, UpdateSlot $updateSlot): RedirectResponse|JsonResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404);
        $price = $request->validated('harga_override');
        $updateSlot->execute($tenant, $slot, (string) $request->validated('operation'), $price === null ? null : (int) $price);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Slot berhasil diperbarui.']);
        }

        return back()->with('sukses', 'Slot berhasil diperbarui.');
    }

    public function changeSelectedSlots(ChangeSelectedSlotsRequest $request, ChangeSelectedSlots $change): RedirectResponse
    {
        $tenant = $this->resolveTenant() ?? abort(404);
        $change->execute($tenant, $request->validated('slot_ids'), (string) $request->validated('operation'));

        return back()->with('sukses', 'Slot yang dipilih berhasil diperbarui.');
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

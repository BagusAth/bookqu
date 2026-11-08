<?php

declare(strict_types=1);

namespace App\Actions\Schedule;

use App\Domain\Schedule\ScheduleConflictRules;
use App\Domain\Subscription\EntitlementRules;
use App\Models\OwnerBlockedDate;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Schedule\ScheduleAvailabilityCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BulkCreateSchedules
{
    /**
     * Longest `rentang` span, in days after the start date (BQ-107, NFR-PERF-002).
     */
    public const MAX_RANGE_DAYS = 90;

    public function __construct(
        protected ScheduleAvailabilityCache $availabilityCache
    ) {}

    /**
     * Bulk create schedule slots for a tenant service.
     *
     * @param array{
     *     jenisslot: string,
     *     idlayanan: int,
     *     tanggal?: string|null,
     *     tanggalmulai?: string|null,
     *     tanggalselesai?: string|null,
     *     jammulai: string,
     *     jamselesai: string,
     *     intervalslot: int
     * } $data
     * @return array{
     *     success: bool,
     *     created_count: int,
     *     days_count: int,
     *     error?: string,
     *     error_field?: string
     * }
     */
    public function execute(Tenant $tenant, array $data): array
    {
        $plan = $this->plan($tenant, $data);
        if (isset($plan['error'])) {
            return ['success' => false, 'created_count' => 0, 'days_count' => 0, 'error' => $plan['error'], 'error_field' => $plan['error_field']];
        }

        $createdCount = DB::transaction(function () use ($plan, $tenant) {
            ClaimSlots::lockTenant((int) $tenant->id);
            $subscription = Subscription::with('plan')->where('idtenant', $tenant->id)->latest('id')->first();
            if (! EntitlementRules::hasEffectivePlan($subscription)) {
                throw ValidationException::withMessages(['error' => 'Paket berakhir. Perpanjang paket untuk membuka slot baru.']);
            }

            $count = 0;
            $timestamp = now();
            foreach ($plan['dates'] as $dateStr) {
                // Read inside the lock, so a concurrent writer cannot cause a duplicate (AC-JBH-01).
                // Prices are not stored: SlotPricing applies the tenant's rules when read (FR-SCHEDULE-012).
                $rows = array_map(
                    fn (array $slot): array => (new Schedule([
                        'idtenant' => $tenant->id,
                        'idlayanan' => $plan['service']->id,
                        'tanggal' => $dateStr,
                        'jam_mulai' => $slot['jam_mulai'],
                        'jam_selesai' => $slot['jam_selesai'],
                        'harga_override' => null,
                        'status' => 'tersedia',
                    ]))->getAttributes() + ['created_at' => $timestamp, 'updated_at' => $timestamp],
                    $this->slotsOn($tenant, $plan, $dateStr)['new'],
                );

                if ($rows !== []) {
                    // scope-bypass: every row carries the authenticated tenant's idtenant
                    Schedule::withoutGlobalScopes()->insert($rows);
                    $count += count($rows);
                }
            }

            return $count;
        });

        if ($createdCount > 0) {
            $this->availabilityCache->clearScheduleCache($tenant->id, (int) $plan['service']->id, $plan['dates']);
        }

        return [
            'success' => true,
            'created_count' => $createdCount,
            'days_count' => count($plan['dates']),
        ];
    }

    /**
     * How many slots a bulk request would create and how many already exist, without saving
     * (FR-SCHEDULE-014, AC-JBH-01).
     *
     * @param  array<string, mixed>  $data  same shape as execute()
     * @return array{success: bool, to_create: int, already_exist: int, days_count: int, error?: string, error_field?: string}
     */
    public function preview(Tenant $tenant, array $data): array
    {
        $plan = $this->plan($tenant, $data);
        if (isset($plan['error'])) {
            return ['success' => false, 'to_create' => 0, 'already_exist' => 0, 'days_count' => 0, 'error' => $plan['error'], 'error_field' => $plan['error_field']];
        }

        $toCreate = 0;
        $existing = 0;
        foreach ($plan['dates'] as $dateStr) {
            $day = $this->slotsOn($tenant, $plan, $dateStr);
            $toCreate += count($day['new']);
            $existing += $day['existing'];
        }

        return ['success' => true, 'to_create' => $toCreate, 'already_exist' => $existing, 'days_count' => count($plan['dates'])];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{service: Service, dates: list<string>, interval: int, start: string, end: string}|array{error: string, error_field: string}
     */
    private function plan(Tenant $tenant, array $data): array
    {
        // scope-bypass: filtered by the authenticated tenant
        $service = Service::withoutGlobalScopes()->where('idtenant', $tenant->id)->find($data['idlayanan']);
        if (! $service) {
            return ['error' => 'Program tidak ditemukan.', 'error_field' => 'idlayanan'];
        }

        $dates = [];
        if ($data['jenisslot'] === 'harian') {
            if (! empty($data['tanggal'])) {
                $dates[] = Carbon::parse($data['tanggal'])->format('Y-m-d');
            }
        } else {
            $cursor = Carbon::parse($data['tanggalmulai']);
            $end = Carbon::parse($data['tanggalselesai']);
            while ($cursor->lte($end)) {
                $dates[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }
        }

        // How far ahead slots may be opened (FR-SCHEDULE-011)
        if ($tenant->slot_open_days_ahead !== null && $dates !== []) {
            $limit = Carbon::today('Asia/Jakarta')->addDays((int) $tenant->slot_open_days_ahead)->toDateString();
            if (max($dates) > $limit) {
                return [
                    'error' => 'Slot hanya bisa dibuka sampai '.$tenant->slot_open_days_ahead.' hari ke depan.',
                    'error_field' => $data['jenisslot'] === 'harian' ? 'tanggal' : 'tanggalselesai',
                ];
            }
        }

        // scope-bypass: filtered by the authenticated tenant
        $blocked = OwnerBlockedDate::withoutGlobalScopes()->where('idtenant', $tenant->id)
            ->whereIn('tanggal', $dates)
            ->pluck('tanggal')
            ->map(fn ($t) => Carbon::parse($t)->format('Y-m-d'))
            ->toArray();

        return [
            'service' => $service,
            'dates' => array_values(array_filter($dates, fn ($t) => ! in_array($t, $blocked, true))),
            'interval' => (int) $data['intervalslot'],
            'start' => (string) $data['jammulai'],
            'end' => (string) $data['jamselesai'],
        ];
    }

    /**
     * Candidate slots of one day, split into new ones and ones that overlap an existing slot.
     *
     * @param  array{service: Service, dates: list<string>, interval: int, start: string, end: string}  $plan
     * @return array{new: list<array{jam_mulai: string, jam_selesai: string}>, existing: int}
     */
    private function slotsOn(Tenant $tenant, array $plan, string $dateStr): array
    {
        // One read of the day's existing slots instead of one conflict query per slot (BQ-107).
        $existingSlots = ScheduleConflictRules::slotTimesOnDate((int) $tenant->id, (int) $plan['service']->id, $dateStr);
        $cursor = Carbon::parse($dateStr.' '.$plan['start']);
        $end = Carbon::parse($dateStr.' '.$plan['end']);
        $new = [];
        $existing = 0;

        while ($cursor->copy()->addMinutes($plan['interval'])->lte($end)) {
            $jamMulai = $cursor->format('H:i:s');
            // Inclusive end: one minute before the next slot starts (e.g. 11:00:00 - 11:59:00)
            $jamSelesai = $cursor->copy()->addMinutes($plan['interval'])->subMinute()->format('H:i:s');

            if (ScheduleConflictRules::overlapsAny($existingSlots, $jamMulai, $jamSelesai)) {
                $existing++;
            } else {
                $new[] = ['jam_mulai' => $jamMulai, 'jam_selesai' => $jamSelesai];
            }

            $cursor->addMinutes($plan['interval']);
        }

        return ['new' => $new, 'existing' => $existing];
    }
}

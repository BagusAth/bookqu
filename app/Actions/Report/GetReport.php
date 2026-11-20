<?php

declare(strict_types=1);

namespace App\Actions\Report;

use App\Actions\Customer\GetCustomerPanel;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\Booking\UnitUtilisation;
use App\Domain\Customer\CustomerIdentity;
use App\Domain\Modules\ModuleRelease;
use App\Domain\Payment\PaymentState;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Subscription\PlanCapability;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The figures of Laporan (BQ-147, owner-portal.md §8.13, FR-ANALYTICS-007) for one period.
 *
 * Definitions, shared by every tab and the exports so a period shows the same figures everywhere
 * (AC-LAP-04):
 * - **Booking:** a booking row by its service date (`tanggalbooking`); the slots of one checkout share
 *   one payment and count as one booking (AC-LAP-02).
 * - **Revenue:** successful booking payments created in the period minus the refunds recorded in the
 *   period on successful booking payments (AC-LAP-01). A refund of money paid twice (`double-payment`)
 *   returns money that was never revenue and is left out. Walk-in means a payment recorded by the
 *   business (`provider = manual`); every other provider is online.
 * - **Customer:** the one customer identity (`CustomerIdentity`); new means the first booking was made in
 *   the period, returning means an earlier booking exists.
 * - **Occupancy:** seats held over total slot capacity (`BookingRules`).
 */
class GetReport
{
    public const TABS = [
        'ringkasan' => 'Ringkasan',
        'pendapatan' => 'Pendapatan',
        'okupansi' => 'Okupansi',
        'layanan' => 'Layanan',
        'pelanggan' => 'Pelanggan',
    ];

    public const DAYS = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];

    private const NO_SERVICE = 'Tanpa layanan';

    /**
     * @return array{
     *     tab: string,
     *     tabs: array<string, string>,
     *     period: array{key: string, label: string, from: string, to: string, query: array<string, string>},
     *     locked: bool,
     *     conclusion: ?string,
     *     comparison: array<string, mixed>,
     *     summary: ?array<string, mixed>,
     *     revenue: ?array<string, mixed>,
     *     occupancy: ?array<string, mixed>,
     *     services: ?list<array{name: string, bookings: int, revenue: float}>,
     *     customers: ?array<string, mixed>
     * }
     */
    public function execute(Tenant $tenant, ReportPeriod $period, ?string $tab): array
    {
        $tab = $tab !== null && array_key_exists($tab, self::TABS) ? $tab : 'ringkasan';
        $subscription = self::subscription($tenant);
        $locked = ! EntitlementRules::canAccessFeature($subscription, PlanCapability::FEATURE_ANALYTICS)['allowed'];

        $report = [
            'tab' => $tab,
            'tabs' => self::TABS,
            'period' => [
                'key' => $period->key,
                'label' => $period->label(),
                'from' => $period->start->format('Y-m-d'),
                'to' => $period->end->format('Y-m-d'),
                'query' => $period->query(),
            ],
            'locked' => $locked,
            'conclusion' => null,
            'comparison' => [],
            'summary' => null,
            'revenue' => null,
            'occupancy' => null,
            'services' => null,
            'customers' => null,
        ];

        // AC-LAP-03: below Medium the page is a preview; no figure of the business is read.
        if ($locked) {
            return $report;
        }

        $tenantId = (int) $tenant->id;
        $bookingsPerService = $this->bookingsPerService($tenantId, $period);
        $revenue = $this->revenue($tenantId, $period);
        $customers = $this->customers($tenantId, $period);

        $showUnits = ModuleRelease::state('units') === 'released'
            && EntitlementRules::canAccessFeature($subscription, 'units')['allowed'];
        $slots = $tab === 'okupansi' || $showUnits ? $this->reportSlots($tenantId, $period) : new Collection;
        $units = $showUnits ? $this->unitUtilisation($tenantId, $slots) : null;
        $report['summary'] = $this->summary($tenantId, $period, $bookingsPerService, $revenue, $customers)
            + ['showUnits' => $showUnits, 'topUnit' => ($units[0]['usedMinutes'] ?? 0) > 0 ? $units[0]['name'] : null];
        if ($tab === 'pelanggan') {
            $customers['promos'] = ModuleRelease::state('promos') === 'released'
                && EntitlementRules::canAccessFeature($subscription, 'promos')['allowed']
                ? $this->promoUsage($tenantId, $period)
                : null;
            $customers['unpaid'] = $this->unpaidBookings($tenantId, $period);
            $customers['advanceDays'] = $this->averageAdvanceDays($tenantId, $period);
        }

        $report = match ($tab) {
            'pendapatan' => ['revenue' => $revenue] + $report,
            'okupansi' => ['occupancy' => $this->occupancy($slots) + ['units' => $units]] + $report,
            'layanan' => ['services' => $this->services($bookingsPerService, $revenue)] + $report,
            'pelanggan' => ['customers' => $customers] + $report,
            default => $report,
        };

        // T-10: a period that runs past today is compared up to today against an equal stretch.
        $compared = $period->toDate();
        $previous = $compared->previous();
        $report['comparison'] = [
            'label' => $previous->start->locale('id')->translatedFormat('j M Y').' – '.$previous->end->locale('id')->translatedFormat('j M Y'),
            'current' => $compared === $period ? [] : $this->comparisonToDate($tenantId, $compared, $tab),
        ] + $this->comparison($tenantId, $previous, $tab);
        $report['conclusion'] = $this->conclusion($report);

        return $report;
    }

    /**
     * The figures of the part of a running period up to today that differ from the whole period.
     * Money and customers count payments, refunds and bookings by when they were made, which is
     * never after today, so only session-dated figures (bookings, occupancy) need their own query.
     *
     * @return array<string, int|float>
     */
    private function comparisonToDate(int $tenantId, ReportPeriod $toDate, string $tab): array
    {
        return match ($tab) {
            'okupansi' => $this->comparison($tenantId, $toDate, $tab),
            'ringkasan' => ['bookings' => array_sum($this->bookingsPerService($tenantId, $toDate))],
            default => [],
        };
    }

    /** @return array<string, int|float> */
    private function comparison(int $tenantId, ReportPeriod $period, string $tab): array
    {
        if ($tab === 'okupansi') {
            $occupancy = $this->occupancy($this->reportSlots($tenantId, $period));

            return ['rate' => $occupancy['rate'], 'booked' => $occupancy['booked']];
        }
        if ($tab === 'pelanggan') {
            $customers = $this->customers($tenantId, $period);

            return ['new' => $customers['new'], 'returning' => $customers['returning']];
        }

        [$payments, $refunds] = $this->revenueRows($tenantId, $period);
        $total = DB::query()->fromSub(
            $payments->selectRaw('payments.jumlah AS gross, 0 AS refund')
                ->unionAll($refunds->selectRaw('0 AS gross, refunds.jumlah AS refund')),
            'previous_money',
        )->selectRaw('SUM(gross) AS gross, SUM(refund) AS refund')->first();
        $gross = (float) ($total->gross ?? 0);
        $revenue = ['net' => round($gross - (float) ($total->refund ?? 0), 2), 'gross' => $gross];

        return $tab === 'pendapatan' ? $revenue : $revenue + [
            'bookings' => array_sum($this->bookingsPerService($tenantId, $period)),
        ];
    }

    /** @param array<string, mixed> $report */
    private function conclusion(array $report): string
    {
        $previous = $report['comparison'];
        $current = $previous['current'];
        $summary = $report['summary'];

        return match ($report['tab']) {
            'pendapatan' => 'Pendapatan bersih '.$this->trend($current['net'] ?? $report['revenue']['net'], $previous['net']).' dibanding periode sebelumnya.',
            'okupansi' => $report['occupancy']['busiestDay'] === null
                ? 'Belum ada slot terisi pada periode ini.'
                : 'Slot paling sering terisi pada hari '.$report['occupancy']['busiestDay'].', dan jam tersibuk adalah '.$report['occupancy']['busiestHour'].'.',
            'layanan' => $summary['topService'] === null
                ? 'Belum ada booking layanan pada periode ini.'
                : $summary['topService'].' menerima booking terbanyak pada periode ini.',
            'pelanggan' => number_format($report['customers']['new'], 0, ',', '.').' pelanggan baru dan '
                .number_format($report['customers']['returning'], 0, ',', '.').' pelanggan kembali lagi.',
            default => 'Pendapatan '.$this->trend($current['net'] ?? $summary['revenue'], $previous['net']).' dan jumlah booking '
                .$this->trend($current['bookings'] ?? $summary['bookings'], $previous['bookings']).' dibanding periode sebelumnya.',
        };
    }

    private function trend(float|int $current, float|int $previous): string
    {
        return match ($current <=> $previous) {
            -1 => 'turun',
            1 => 'naik',
            default => 'tetap',
        };
    }

    /**
     * Laporan and its exports need Medium or Pro (INV-SUB-01: decided by EntitlementRules only).
     */
    public static function isEntitled(Tenant $tenant): bool
    {
        return EntitlementRules::canAccessFeature(self::subscription($tenant), PlanCapability::FEATURE_ANALYTICS)['allowed'];
    }

    private static function subscription(Tenant $tenant): ?Subscription
    {
        return Subscription::with('plan')->where('idtenant', $tenant->id)->latest('id')->first();
    }

    /**
     * The successful booking payments of the period, one row per payment however many slots it paid
     * (AC-LAP-02), for the Pendapatan export.
     *
     * @return iterable<int, array{date: string, code: string, customer: string, service: string, amount: float, walkIn: bool, slots: int}>
     */
    public function paymentRows(Tenant $tenant, ReportPeriod $period): iterable
    {
        $tenantId = (int) $tenant->id;
        $names = $this->serviceNames($tenantId);
        $bookings = DB::table('bookings')
            ->select('idpayment')
            ->selectRaw('MIN(idlayanan) AS idlayanan, MIN(booking_code) AS booking_code, MAX(namapelanggan) AS customer, COUNT(*) AS slots')
            ->where('idtenant', $tenantId)
            ->whereNotNull('idpayment')
            ->groupBy('idpayment');

        $rows = DB::table('payments')
            ->leftJoinSub($bookings, 'payment_bookings', 'payment_bookings.idpayment', '=', 'payments.id')
            ->where('payments.idtenant', $tenantId)
            ->where('payments.tipe', PaymentState::TIPE_BOOKING)
            ->where('payments.status', PaymentState::STATUS_SUKSES)
            ->whereBetween('payments.created_at', $this->bounds($period))
            ->orderBy('payments.created_at')
            ->orderBy('payments.id')
            ->select(['payments.created_at', 'payments.jumlah', 'payments.provider', 'payments.nama_pembayar', 'payment_bookings.idlayanan', 'payment_bookings.booking_code', 'payment_bookings.customer', 'payment_bookings.slots'])
            ->cursor();

        foreach ($rows as $row) {
            yield [
                'date' => CarbonImmutable::parse((string) $row->created_at)->format('Y-m-d H:i'),
                'code' => (string) ($row->booking_code ?? ''),
                'customer' => (string) ($row->customer ?? $row->nama_pembayar ?? ''),
                'service' => $row->idlayanan !== null ? ($names[(int) $row->idlayanan] ?? self::NO_SERVICE) : self::NO_SERVICE,
                'amount' => (float) $row->jumlah,
                'walkIn' => $row->provider === 'manual',
                'slots' => (int) ($row->slots ?? 0),
            ];
        }
    }

    /**
     * Customers of the period, newest first, for the Pelanggan export.
     *
     * @return list<array{name: string, bookings: int, returning: bool}>
     */
    public function customerRows(Tenant $tenant, ReportPeriod $period): array
    {
        return $this->customerQuery((int) $tenant->id, $period)
            ->orderByDesc('period_bookings')
            ->orderBy('name')
            ->get()
            ->map(fn (object $row): array => [
                'name' => (string) $row->name,
                'bookings' => (int) $row->period_bookings,
                'returning' => (bool) $row->is_returning,
            ])
            ->all();
    }

    /**
     * @param  array<string, int>  $bookingsPerService
     * @param  array<string, mixed>  $revenue
     * @param  array<string, mixed>  $customers
     * @return array<string, mixed>
     */
    private function summary(int $tenantId, ReportPeriod $period, array $bookingsPerService, array $revenue, array $customers): array
    {
        $bookings = array_sum($bookingsPerService);
        $byStatus = $this->groupedBookings($tenantId, $period)
            ->selectRaw('status, COUNT(DISTINCT group_key) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
        $rate = fn (string $status): float => $bookings > 0 ? round(($byStatus[$status] ?? 0) / $bookings * 100, 1) : 0.0;

        $rating = DB::table('reviews')
            ->where('idtenant', $tenantId)
            ->whereBetween('created_at', $this->bounds($period))
            ->selectRaw('AVG(rating) AS average, COUNT(*) AS total')
            ->first();

        arsort($bookingsPerService);
        $topService = $bookings > 0 ? (string) array_key_first($bookingsPerService) : null;

        return [
            'revenue' => $revenue['net'],
            'bookings' => $bookings,
            'newCustomers' => $customers['new'],
            'cancellationRate' => $rate(BookingState::STATUS_CANCELLED),
            'completionRate' => $rate(BookingState::STATUS_COMPLETED),
            // ADR-0018 (BQ-153): the no-show rate is shown once the no-show module is released.
            'noShowRate' => ModuleRelease::state('no-show') === 'released' ? $rate(BookingState::STATUS_NO_SHOW) : null,
            'averageRating' => ($rating->total ?? 0) > 0 ? round((float) $rating->average, 1) : null,
            'reviewCount' => (int) ($rating->total ?? 0),
            'topService' => $topService,
        ];
    }

    /**
     * Bookings of the period per service name, counting a multi-slot booking once.
     *
     * @return array<string, int>
     */
    private function bookingsPerService(int $tenantId, ReportPeriod $period): array
    {
        $names = $this->serviceNames($tenantId);

        $counts = [];
        $rows = $this->groupedBookings($tenantId, $period)
            ->selectRaw('idlayanan, COUNT(DISTINCT group_key) AS total')
            ->groupBy('idlayanan')
            ->get();
        foreach ($rows as $row) {
            $name = $names[(int) $row->idlayanan] ?? self::NO_SERVICE;
            $counts[$name] = ($counts[$name] ?? 0) + (int) $row->total;
        }

        return $counts;
    }

    /**
     * Booking rows of the period with the key of the checkout they belong to.
     */
    private function groupedBookings(int $tenantId, ReportPeriod $period): Builder
    {
        $rows = DB::table('bookings')
            ->select(['idlayanan', 'status'])
            ->selectRaw("CASE WHEN idpayment IS NULL THEN CONCAT('b', id) ELSE CONCAT('p', idpayment) END AS group_key")
            ->where('idtenant', $tenantId)
            ->whereBetween('tanggalbooking', $this->bounds($period));

        return DB::query()->fromSub($rows, 'period_bookings');
    }

    /**
     * Gross, refunds and net revenue with the series, the channel split and the per-service rows.
     *
     * @return array{gross: float, refunds: float, net: float, online: float, walkIn: float, daily: bool, series: list<array{label: string, gross: float, refunds: float, net: float}>, perService: list<array{name: string, gross: float, refunds: float, net: float}>}
     */
    private function revenue(int $tenantId, ReportPeriod $period): array
    {
        $names = $this->serviceNames($tenantId);
        $bucket = $this->bucketExpression($period);

        [$payments, $refunds] = $this->revenueRows($tenantId, $period);
        $services = $this->paymentServices($tenantId);
        $payments->leftJoinSub($services, 'payment_services', 'payment_services.idpayment', '=', 'payments.id')
            ->selectRaw("'gross' AS kind, payment_services.idlayanan AS idlayanan, CASE WHEN payments.provider = 'manual' THEN 1 ELSE 0 END AS walk_in, {$bucket('payments.created_at')} AS bucket, payments.jumlah AS amount");
        $refunds->leftJoinSub($services, 'payment_services', 'payment_services.idpayment', '=', 'payments.id')
            ->selectRaw("'refunds' AS kind, payment_services.idlayanan AS idlayanan, 0 AS walk_in, {$bucket('refunds.created_at')} AS bucket, refunds.jumlah AS amount");

        $series = [];
        foreach ($this->buckets($period) as $key => $label) {
            $series[$key] = ['label' => $label, 'gross' => 0.0, 'refunds' => 0.0, 'net' => 0.0];
        }
        $perService = [];
        $online = 0.0;
        $walkIn = 0.0;

        $grouped = DB::query()->fromSub($payments->unionAll($refunds), 'money')
            ->selectRaw('kind, idlayanan, walk_in, bucket, SUM(amount) AS total')
            ->groupBy('kind', 'idlayanan', 'walk_in', 'bucket')
            ->get();
        foreach ($grouped as $row) {
            $kind = $row->kind === 'gross' ? 'gross' : 'refunds';
            $amount = round((float) $row->total, 2);
            $name = $row->idlayanan !== null ? ($names[(int) $row->idlayanan] ?? self::NO_SERVICE) : self::NO_SERVICE;
            $perService[$name] ??= ['name' => $name, 'gross' => 0.0, 'refunds' => 0.0, 'net' => 0.0];
            $perService[$name][$kind] += $amount;
            if (isset($series[(string) $row->bucket])) {
                $series[(string) $row->bucket][$kind] += $amount;
            }
            if ($kind === 'gross' && (int) $row->walk_in === 1) {
                $walkIn += $amount;
            } elseif ($kind === 'gross') {
                $online += $amount;
            }
        }

        $series = array_map(fn (array $point): array => ['net' => $point['gross'] - $point['refunds']] + $point, array_values($series));
        $perService = array_map(fn (array $row): array => ['net' => $row['gross'] - $row['refunds']] + $row, array_values($perService));
        usort($perService, fn (array $a, array $b): int => [$b['net'], $a['name']] <=> [$a['net'], $b['name']]);
        $gross = $online + $walkIn;
        $refunded = array_sum(array_column($perService, 'refunds'));

        return [
            'gross' => $gross,
            'refunds' => $refunded,
            'net' => $gross - $refunded,
            'online' => $online,
            'walkIn' => $walkIn,
            'daily' => $period->isDaily(),
            'series' => $series,
            'perService' => $perService,
        ];
    }

    /** @return array{Builder, Builder} */
    private function revenueRows(int $tenantId, ReportPeriod $period): array
    {
        $payments = DB::table('payments')
            ->where('payments.idtenant', $tenantId)
            ->where('payments.tipe', PaymentState::TIPE_BOOKING)
            ->where('payments.status', PaymentState::STATUS_SUKSES)
            ->whereBetween('payments.created_at', $this->bounds($period));

        $refunds = DB::table('refunds')
            ->join('payments', 'payments.id', '=', 'refunds.payment_id')
            ->where('payments.idtenant', $tenantId)
            ->where('payments.tipe', PaymentState::TIPE_BOOKING)
            ->where('payments.status', PaymentState::STATUS_SUKSES)
            ->where('refunds.status', '!=', 'failed')
            ->where(fn (Builder $query) => $query->whereNull('refunds.cause')->orWhere('refunds.cause', '!=', 'double-payment'))
            ->whereBetween('refunds.created_at', $this->bounds($period));

        return [$payments, $refunds];
    }

    /**
     * The service of each payment's booking (the slots of one checkout share a service).
     */
    private function paymentServices(int $tenantId): Builder
    {
        return DB::table('bookings')
            ->select('idpayment')
            ->selectRaw('MIN(idlayanan) AS idlayanan')
            ->where('idtenant', $tenantId)
            ->whereNotNull('idpayment')
            ->groupBy('idpayment');
    }

    /** @return list<array{name: string, service: string, date: string, whatsAppUrl: ?string}> */
    private function unpaidBookings(int $tenantId, ReportPeriod $period): array
    {
        return DB::table('bookings as unpaid')
            ->join('payments', fn ($join) => $join->on('payments.id', '=', 'unpaid.idpayment')->on('payments.idtenant', '=', 'unpaid.idtenant'))
            ->leftJoin('services', fn ($join) => $join->on('services.id', '=', 'unpaid.idlayanan')->on('services.idtenant', '=', 'unpaid.idtenant'))
            ->where('unpaid.idtenant', $tenantId)
            ->whereBetween('unpaid.tanggalbooking', $this->bounds($period))
            ->where('payments.tipe', PaymentState::TIPE_BOOKING)
            ->where('payments.status', PaymentState::STATUS_GAGAL)
            ->whereExists(fn (Builder $logs) => $logs->from('booking_logs')
                ->whereColumn('booking_id', 'unpaid.id')->where('event', 'cancelled')->where('description', 'Pembayaran ditutup.'))
            ->whereNotExists(fn (Builder $logs) => $logs->from('booking_logs')
                ->whereColumn('booking_id', 'unpaid.id')->where('event', 'cancelled')->where('metadata->actor', 'owner'))
            ->whereNotExists(fn (Builder $later) => $later->from('bookings as later')
                ->whereColumn('later.idtenant', 'unpaid.idtenant')
                ->whereIn('later.status', [BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED])
                ->whereColumn('later.created_at', '>', 'unpaid.created_at')
                ->whereRaw(CustomerIdentity::sql('later').' = '.CustomerIdentity::sql('unpaid')))
            ->orderByDesc('unpaid.created_at')
            ->orderByDesc('unpaid.id')
            ->get(['unpaid.idpayment', 'unpaid.namapelanggan', 'unpaid.nomorhp', 'unpaid.tanggalbooking', 'services.namalayanan'])
            ->unique('idpayment')
            ->map(fn (object $row): array => [
                'name' => (string) $row->namapelanggan,
                'service' => (string) ($row->namalayanan ?? self::NO_SERVICE),
                'date' => CarbonImmutable::parse((string) $row->tanggalbooking)->locale('id')->translatedFormat('j M Y'),
                'whatsAppUrl' => GetCustomerPanel::whatsAppUrl((string) $row->nomorhp),
            ])->values()->all();
    }

    private function averageAdvanceDays(int $tenantId, ReportPeriod $period): ?float
    {
        $bookings = DB::table('bookings')
            ->selectRaw("CASE WHEN idpayment IS NULL THEN CONCAT('b', id) ELSE CONCAT('p', idpayment) END AS group_key")
            ->selectRaw('MIN(tanggalbooking) AS session_date, MIN(created_at) AS booked_at')
            ->where('idtenant', $tenantId)
            ->whereBetween('tanggalbooking', $this->bounds($period))
            ->groupBy('group_key');

        $days = DB::connection()->getDriverName() === 'sqlite'
            ? 'JULIANDAY(DATE(session_date)) - JULIANDAY(DATE(booked_at))'
            : 'DATEDIFF(session_date, booked_at)';
        $average = DB::query()->fromSub($bookings, 'lead_times')
            ->selectRaw("AVG(CASE WHEN ($days) >= 0 THEN ($days) END) AS average_days")
            ->value('average_days');

        return $average === null ? null : round((float) $average, 1);
    }

    /** @return Collection<int, Schedule> */
    private function reportSlots(int $tenantId, ReportPeriod $period): Collection
    {
        [$taken, $bindings] = BookingRules::seatsTakenSql();

        return Schedule::query()
            ->join('services', fn ($join) => $join->on('services.id', '=', 'schedules.idlayanan')->on('services.idtenant', '=', 'schedules.idtenant'))
            ->where('schedules.idtenant', $tenantId)
            ->whereBetween('schedules.tanggal', $this->bounds($period))
            ->select(['schedules.id', 'schedules.idlayanan', 'schedules.tanggal', 'schedules.jam_mulai', 'schedules.jam_selesai', 'schedules.status', 'services.kapasitas'])
            ->selectRaw($taken.' AS seats_taken', $bindings)
            ->get();
    }

    /**
     * @param  Collection<int, Schedule>  $slots
     * @return array<string, mixed>
     */
    private function occupancy(Collection $slots): array
    {
        $isOccupied = fn (Schedule $slot): bool => (int) $slot->getAttribute('seats_taken') > 0;
        $total = $slots->count();
        $booked = $slots->filter($isOccupied)->count();
        $free = $slots->filter(fn (Schedule $slot): bool => ! $isOccupied($slot) && $slot->status === 'tersedia')->count();
        $seats = $slots->sum(fn (Schedule $slot): int => (int) $slot->getAttribute('seats_taken'));
        $capacity = $slots->sum(fn (Schedule $slot): int => (int) $slot->getAttribute('kapasitas'));

        $hours = $slots->map(fn (Schedule $slot): int => (int) substr((string) $slot->jam_mulai, 0, 2))->unique()->sort()->values()->all();
        $cells = [];
        foreach (array_keys(self::DAYS) as $day) {
            $cells[$day] = array_fill_keys($hours, 0);
        }
        foreach ($slots->filter($isOccupied) as $slot) {
            $day = CarbonImmutable::parse($slot->tanggal)->dayOfWeekIso;
            $cells[$day][(int) substr((string) $slot->jam_mulai, 0, 2)]++;
        }

        $perDay = array_map('array_sum', $cells);
        $perHour = array_fill_keys($hours, 0);
        foreach ($cells as $row) {
            foreach ($row as $hour => $count) {
                $perHour[$hour] += $count;
            }
        }
        // FR-VOUCHER-006: the quietest day and hour among those that had slots, to suggest a promo.
        $slotDays = $slots->map(fn (Schedule $slot): int => CarbonImmutable::parse($slot->tanggal)->dayOfWeekIso)->unique()->all();
        $quietDays = array_intersect_key($perDay, array_flip($slotDays));
        asort($quietDays);
        $quietHours = $perHour;
        asort($quietHours);
        arsort($perDay);
        arsort($perHour);

        return [
            'total' => $total,
            'booked' => $booked,
            'free' => $free,
            'seats' => $seats,
            'capacity' => $capacity,
            'rate' => $capacity > 0 ? round($seats / $capacity * 100, 1) : 0.0,
            'heatmap' => [
                'days' => self::DAYS,
                'hours' => $hours,
                'cells' => $cells,
                'max' => max([0, ...array_map(fn (array $row): int => $row === [] ? 0 : max($row), $cells)]),
            ],
            'busiestDay' => $booked > 0 ? self::DAYS[array_key_first($perDay)] : null,
            'busiestHour' => $booked > 0 ? sprintf('%02d.00', array_key_first($perHour)) : null,
            'quietestDay' => $quietDays !== [] ? self::DAYS[array_key_first($quietDays)] : null,
            'quietestHour' => $quietHours !== [] ? sprintf('%02d.00', array_key_first($quietHours)) : null,
        ];
    }

    /**
     * Current active unit links applied to the selected period. Merge overlapping slots so a shared
     * unit has one minute of capacity per minute, including where multiple services offer it.
     *
     * @param  Collection<int, Schedule>  $slots
     * @return list<array{name: string, offeredMinutes: int, usedMinutes: int, rate: float}>
     */
    private function unitUtilisation(int $tenantId, Collection $slots): array
    {
        $slotsByService = $slots->groupBy('idlayanan');
        $rows = [];
        $units = Unit::where('idtenant', $tenantId)->where('is_active', true)
            ->with(['services' => fn ($query) => $query->where('services.idtenant', $tenantId)->select('services.id')])
            ->get();
        foreach ($units as $unit) {
            $unitSlots = $unit->services->flatMap(fn ($service) => $slotsByService->get($service->getKey(), []));
            $rows[] = ['name' => $unit->name] + UnitUtilisation::forSlots($unitSlots);
        }
        usort($rows, fn (array $a, array $b): int => [$b['usedMinutes'], $a['name']] <=> [$a['usedMinutes'], $b['name']]);

        return $rows;
    }

    /** @return list<array{customer: string, code: string, uses: int, discount: int}> */
    private function promoUsage(int $tenantId, ReportPeriod $period): array
    {
        $customers = DB::table('bookings')->where('idtenant', $tenantId)->whereNotNull('idpayment')
            ->select('idpayment')->selectRaw('MAX(namapelanggan) AS name')
            ->selectRaw('MIN('.CustomerIdentity::sql().') AS identifier')->groupBy('idpayment');
        $payments = Payment::query()
            ->leftJoinSub($customers, 'payment_customers', 'payment_customers.idpayment', '=', 'payments.id')
            ->where('payments.idtenant', $tenantId)->where('payments.tipe', PaymentState::TIPE_BOOKING)
            ->where('payments.status', PaymentState::STATUS_SUKSES)->whereBetween('payments.created_at', $this->bounds($period))
            ->whereNotNull('payments.price_breakdown')
            ->select(['payments.id', 'payments.nama_pembayar', 'payments.price_breakdown', 'payment_customers.name AS customer_name', 'payment_customers.identifier AS customer_identifier'])
            ->lazyById(column: 'payments.id', alias: 'id');
        $promos = [];
        foreach ($payments as $payment) {
            $promo = $payment->price_breakdown['promo'] ?? null;
            if ($promo === null) {
                continue;
            }

            $code = (string) $promo['code'];
            $identifier = (string) ($payment->getAttribute('customer_identifier') ?? 'payment-'.$payment->id);
            $name = (string) ($payment->getAttribute('customer_name') ?? $payment->nama_pembayar ?? 'Pelanggan');
            $key = $identifier.'|'.$code;
            $promos[$key] ??= ['customer' => $name, 'code' => $code, 'uses' => 0, 'discount' => 0];
            $promos[$key]['uses']++;
            $promos[$key]['discount'] += (int) $promo['discount'];
        }
        usort($promos, fn (array $a, array $b): int => [$a['customer'], $a['code']] <=> [$b['customer'], $b['code']]);

        return $promos;
    }

    /**
     * Services ranked by bookings, then revenue.
     *
     * @param  array<string, int>  $bookingsPerService
     * @param  array<string, mixed>  $revenue
     * @return list<array{name: string, bookings: int, revenue: float}>
     */
    private function services(array $bookingsPerService, array $revenue): array
    {
        $rows = [];
        foreach ($bookingsPerService as $name => $bookings) {
            $rows[$name] = ['name' => (string) $name, 'bookings' => $bookings, 'revenue' => 0.0];
        }
        foreach ($revenue['perService'] as $row) {
            $rows[$row['name']] ??= ['name' => $row['name'], 'bookings' => 0, 'revenue' => 0.0];
            $rows[$row['name']]['revenue'] = $row['net'];
        }

        $rows = array_values($rows);
        usort($rows, fn (array $a, array $b): int => [$b['bookings'], $b['revenue'], $a['name']] <=> [$a['bookings'], $a['revenue'], $b['name']]);

        return $rows;
    }

    /**
     * Customers who booked in the period, split into new and returning.
     *
     * @return array{active: int, new: int, returning: int}
     */
    private function customers(int $tenantId, ReportPeriod $period): array
    {
        $totals = DB::query()
            ->fromSub($this->customerQuery($tenantId, $period), 'period_customers')
            ->selectRaw('COUNT(*) AS active, SUM(is_returning) AS returning_customers')
            ->first();
        $active = (int) ($totals->active ?? 0);
        $returning = (int) ($totals->returning_customers ?? 0);

        return ['active' => $active, 'new' => $active - $returning, 'returning' => $returning];
    }

    /**
     * One row per customer identity with a booking made in the period. The identity is computed in a
     * derived table because MariaDB's ONLY_FULL_GROUP_BY rejects grouping by a repeated expression.
     */
    private function customerQuery(int $tenantId, ReportPeriod $period): Builder
    {
        [$from, $to] = $this->bounds($period);
        $bookings = DB::table('bookings')
            ->select(['namapelanggan', 'created_at'])
            ->selectRaw("CASE WHEN idpayment IS NULL THEN CONCAT('b', id) ELSE CONCAT('p', idpayment) END AS group_key")
            ->selectRaw(CustomerIdentity::sql().' AS identifier')
            ->where('idtenant', $tenantId)
            ->where('created_at', '<=', $to);

        return DB::query()
            ->fromSub($bookings, 'customer_bookings')
            ->select('identifier')
            ->selectRaw('MAX(namapelanggan) AS name')
            ->selectRaw('COUNT(DISTINCT CASE WHEN created_at >= ? THEN group_key END) AS period_bookings', [$from])
            ->selectRaw('CASE WHEN MIN(created_at) < ? THEN 1 ELSE 0 END AS is_returning', [$from])
            ->groupBy('identifier')
            ->havingRaw('MAX(created_at) >= ?', [$from]);
    }

    /**
     * @return array<int, string>
     */
    private function serviceNames(int $tenantId): array
    {
        return DB::table('services')->where('idtenant', $tenantId)->pluck('namalayanan', 'id')
            ->mapWithKeys(fn (mixed $name, mixed $id): array => [(int) $id => (string) $name])
            ->all();
    }

    /**
     * Inclusive bounds that work for date and datetime columns on SQLite and MySQL.
     *
     * @return array{0: string, 1: string}
     */
    private function bounds(ReportPeriod $period): array
    {
        return [$period->start->format('Y-m-d 00:00:00'), $period->end->format('Y-m-d 23:59:59')];
    }

    /**
     * @return callable(string): string
     */
    private function bucketExpression(ReportPeriod $period): callable
    {
        $format = $period->isDaily() ? '%Y-%m-%d' : '%Y-%m';
        $sqlite = DB::connection()->getDriverName() === 'sqlite';

        return fn (string $column): string => $sqlite ? "strftime('{$format}', {$column})" : "DATE_FORMAT({$column}, '{$format}')";
    }

    /**
     * The days or months of the period, keyed like the bucket expression.
     *
     * @return array<string, string>
     */
    private function buckets(ReportPeriod $period): array
    {
        $buckets = [];
        if ($period->isDaily()) {
            for ($day = $period->start->startOfDay(); $day->lte($period->end); $day = $day->addDay()) {
                $buckets[$day->format('Y-m-d')] = $day->locale('id')->translatedFormat('j M');
            }

            return $buckets;
        }

        for ($month = $period->start->startOfMonth(); $month->lte($period->end); $month = $month->addMonthNoOverflow()) {
            $buckets[$month->format('Y-m')] = $month->locale('id')->translatedFormat('M Y');
        }

        return $buckets;
    }
}

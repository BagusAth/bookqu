<?php

declare(strict_types=1);

namespace App\Actions\Report;

use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\Customer\CustomerIdentity;
use App\Domain\Modules\ModuleRelease;
use App\Domain\Payment\PaymentState;
use App\Domain\Subscription\EntitlementRules;
use App\Domain\Subscription\PlanCapability;
use App\Models\Schedule;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
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
 * - **Occupancy:** slots of the period holding an occupying booking (`BookingRules`).
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
        $locked = ! self::isEntitled($tenant);

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

        $report['summary'] = $this->summary($tenantId, $period, $bookingsPerService, $revenue, $customers);

        return match ($tab) {
            'pendapatan' => ['revenue' => $revenue] + $report,
            'okupansi' => ['occupancy' => $this->occupancy($tenantId, $period)] + $report,
            'layanan' => ['services' => $this->services($bookingsPerService, $revenue)] + $report,
            'pelanggan' => ['customers' => $customers] + $report,
            default => $report,
        };
    }

    /**
     * Laporan and its exports need Medium or Pro (INV-SUB-01: decided by EntitlementRules only).
     */
    public static function isEntitled(Tenant $tenant): bool
    {
        $subscription = Subscription::with('plan')->where('idtenant', $tenant->id)->latest('id')->first();

        return EntitlementRules::canAccessFeature($subscription, PlanCapability::FEATURE_ANALYTICS)['allowed'];
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
            // The no-show state arrives with ADR-0018 (BQ-153); its rate is shown once that module is released.
            'noShowRate' => ModuleRelease::state('no-show') === 'released' ? $rate('no_show') : null,
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

        $payments = DB::table('payments')
            ->leftJoinSub($this->paymentServices($tenantId), 'payment_services', 'payment_services.idpayment', '=', 'payments.id')
            ->where('payments.idtenant', $tenantId)
            ->where('payments.tipe', PaymentState::TIPE_BOOKING)
            ->where('payments.status', PaymentState::STATUS_SUKSES)
            ->whereBetween('payments.created_at', $this->bounds($period))
            ->selectRaw("payment_services.idlayanan AS idlayanan, CASE WHEN payments.provider = 'manual' THEN 1 ELSE 0 END AS walk_in, {$bucket('payments.created_at')} AS bucket, payments.jumlah AS amount");

        $refunds = DB::table('refunds')
            ->join('payments', 'payments.id', '=', 'refunds.payment_id')
            ->leftJoinSub($this->paymentServices($tenantId), 'payment_services', 'payment_services.idpayment', '=', 'payments.id')
            ->where('payments.idtenant', $tenantId)
            ->where('payments.tipe', PaymentState::TIPE_BOOKING)
            ->where('payments.status', PaymentState::STATUS_SUKSES)
            ->where('refunds.status', '!=', 'failed')
            ->where(fn (Builder $query) => $query->whereNull('refunds.cause')->orWhere('refunds.cause', '!=', 'double-payment'))
            ->whereBetween('refunds.created_at', $this->bounds($period))
            ->selectRaw("payment_services.idlayanan AS idlayanan, 0 AS walk_in, {$bucket('refunds.created_at')} AS bucket, refunds.jumlah AS amount");

        $series = [];
        foreach ($this->buckets($period) as $key => $label) {
            $series[$key] = ['label' => $label, 'gross' => 0.0, 'refunds' => 0.0, 'net' => 0.0];
        }
        $perService = [];
        $online = 0.0;
        $walkIn = 0.0;

        foreach (['gross' => $payments, 'refunds' => $refunds] as $kind => $rows) {
            $grouped = DB::query()->fromSub($rows, 'money')
                ->selectRaw('idlayanan, walk_in, bucket, SUM(amount) AS total')
                ->groupBy('idlayanan', 'walk_in', 'bucket')
                ->get();
            foreach ($grouped as $row) {
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

    /**
     * Slots of the period: rate, booked and free slots, and booked slots per weekday and hour.
     *
     * @return array<string, mixed>
     */
    private function occupancy(int $tenantId, ReportPeriod $period): array
    {
        [$from, $to] = $this->bounds($period);
        $slots = Schedule::query()
            ->where('idtenant', $tenantId)
            ->whereBetween('tanggal', [$from, $to])
            ->withExists(['bookings as occupied' => fn (EloquentBuilder $query) => BookingRules::applyOccupiesSlotCondition($query, 'bookings.')])
            ->get(['id', 'tanggal', 'jam_mulai', 'status']);

        $isOccupied = fn (Schedule $slot): bool => (bool) $slot->getAttribute('occupied');
        $total = $slots->count();
        $booked = $slots->filter($isOccupied)->count();
        $free = $slots->filter(fn (Schedule $slot): bool => ! $isOccupied($slot) && $slot->status === 'tersedia')->count();

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
        arsort($perDay);
        arsort($perHour);

        return [
            'total' => $total,
            'booked' => $booked,
            'free' => $free,
            'rate' => $total > 0 ? round($booked / $total * 100, 1) : 0.0,
            'heatmap' => [
                'days' => self::DAYS,
                'hours' => $hours,
                'cells' => $cells,
                'max' => max([0, ...array_map(fn (array $row): int => $row === [] ? 0 : max($row), $cells)]),
            ],
            'busiestDay' => $booked > 0 ? self::DAYS[array_key_first($perDay)] : null,
            'busiestHour' => $booked > 0 ? sprintf('%02d.00', array_key_first($perHour)) : null,
        ];
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
                $buckets[$day->format('Y-m-d')] = $day->translatedFormat('j M');
            }

            return $buckets;
        }

        for ($month = $period->start->startOfMonth(); $month->lte($period->end); $month = $month->addMonthNoOverflow()) {
            $buckets[$month->format('Y-m')] = $month->translatedFormat('M Y');
        }

        return $buckets;
    }
}

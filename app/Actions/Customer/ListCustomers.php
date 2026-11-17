<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Domain\Booking\BookingState;
use App\Domain\Customer\CustomerIdentity;
use App\Models\Tenant;
use App\Support\CustomerBookingRoutes;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The Pelanggan directory (owner-portal.md §8.5): one row per customer identity of the business,
 * the summary and the quick filters. Customers are derived from bookings (no table of their own).
 */
class ListCustomers
{
    public const FILTERS = ['new', 'regular', 'dormant'];

    /** A regular customer has at least this many bookings. */
    public const REGULAR_MIN_BOOKINGS = 3;

    /** A customer is dormant after this many days without a booking. */
    public const DORMANT_AFTER_DAYS = 60;

    public function __construct(private SumCustomerPayments $sumCustomerPayments) {}

    /**
     * @return array{
     *     customers: LengthAwarePaginator<int, object>,
     *     summary: array{total: int, new: int, returning: int},
     *     counts: array<string, int>,
     *     search: string,
     *     filter: string|null,
     *     sort: string,
     *     sortOptions: array<string, string>,
     * }
     */
    public function execute(Tenant $tenant, string $search, ?string $filter, bool $canViewRevenue, string $requestedSort = ''): array
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $search = trim($search);
        $filter = in_array($filter, self::FILTERS, true) ? $filter : null;
        $monthStart = Carbon::now()->startOfMonth()->toDateTimeString();
        $dormantBefore = Carbon::today()->subDays(self::DORMANT_AFTER_DAYS)->toDateString();

        $sortOptions = ['recent' => 'Booking terakhir', 'bookings' => 'Booking terbanyak'];
        if ($canViewRevenue) {
            $sortOptions['spent'] = 'Pembayaran tertinggi';
        }
        $sort = isset($sortOptions[$requestedSort]) ? $requestedSort : 'recent';

        $customers = DB::query()
            ->fromSub($this->grouped((int) $tenant->id, $search), 'customers')
            ->select('customers.*')
            ->when($filter === 'new', fn (Builder $query) => $query->where('first_seen', '>=', $monthStart))
            ->when($filter === 'regular', fn (Builder $query) => $query->where('total_bookings', '>=', self::REGULAR_MIN_BOOKINGS))
            ->when($filter === 'dormant', fn (Builder $query) => $query->where('last_booking_date', '<', $dormantBefore))
            ->when($sort === 'bookings', fn (Builder $query) => $query->orderByDesc('total_bookings'))
            ->when($sort === 'spent', fn (Builder $query) => $query
                ->leftJoinSub($this->sumCustomerPayments->query((int) $tenant->id), 'spending', 'spending.identifier', '=', 'customers.identifier')
                ->orderByRaw('COALESCE(spending.total_spent, 0) DESC'))
            ->orderByDesc('last_booking_date')
            ->orderBy('customers.identifier')
            ->paginate(20)
            ->withQueryString();

        $identifiers = collect($customers->items())->map(fn (object $customer): string => (string) $customer->identifier)->values()->all();
        $spent = $canViewRevenue ? $this->sumCustomerPayments->execute((int) $tenant->id, $identifiers) : [];
        $upcoming = $this->upcoming((int) $tenant->id, $identifiers);

        $storefront = $tenant->slug ? CustomerBookingRoutes::url('customer.booking.program', $tenant->slug) : null;
        $customers->getCollection()->transform(function (object $customer) use ($spent, $upcoming, $canViewRevenue, $monthStart, $dormantBefore, $tenant, $storefront): object {
            $identifier = (string) $customer->identifier;
            $customer->total_bookings = (int) $customer->total_bookings;
            $customer->total_spent = $canViewRevenue ? ($spent[$identifier] ?? 0.0) : null;
            $customer->formatted_spent = $canViewRevenue ? 'Rp '.number_format($spent[$identifier] ?? 0, 0, ',', '.') : null;
            $customer->last_booking = $customer->last_booking_date ? Carbon::parse((string) $customer->last_booking_date)->format('d M Y') : '-';
            $customer->upcoming_booking = $upcoming[$identifier] ?? null;
            $customer->panel_url = route('owner.customers.panel', ['identifier' => $identifier]);
            $customer->whatsapp_url = GetCustomerPanel::whatsAppUrl((string) $customer->phone);
            $customer->segment = match (true) {
                $customer->last_booking_date && $customer->last_booking_date < $dormantBefore => 'Lama tidak datang',
                $customer->first_seen >= $monthStart => 'Baru',
                default => null,
            };
            $firstName = explode(' ', trim((string) $customer->name), 2)[0];
            $customer->greeting = $customer->whatsapp_url === null ? null
                : 'Halo Kak '.$firstName.', sudah lama tidak mampir ke '.$tenant->namabisnis.'. Mau kami bantu pilih jadwal?'
                    .($storefront ? ' Booking di '.$storefront : '');

            return $customer;
        });

        $summary = DB::query()
            ->fromSub($this->grouped((int) $tenant->id, ''), 'customers')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN first_seen >= ? THEN 1 ELSE 0 END) AS new_customers', [$monthStart])
            ->selectRaw('SUM(CASE WHEN total_bookings >= 2 THEN 1 ELSE 0 END) AS returning_customers')
            ->selectRaw('SUM(CASE WHEN total_bookings >= ? THEN 1 ELSE 0 END) AS regular_customers', [self::REGULAR_MIN_BOOKINGS])
            ->selectRaw('SUM(CASE WHEN last_booking_date < ? THEN 1 ELSE 0 END) AS dormant_customers', [$dormantBefore])
            ->first();

        return [
            'customers' => $customers,
            'summary' => [
                'total' => (int) ($summary->total ?? 0),
                'new' => (int) ($summary->new_customers ?? 0),
                'returning' => (int) ($summary->returning_customers ?? 0),
            ],
            'counts' => [
                '' => (int) ($summary->total ?? 0),
                'new' => (int) ($summary->new_customers ?? 0),
                'regular' => (int) ($summary->regular_customers ?? 0),
                'dormant' => (int) ($summary->dormant_customers ?? 0),
            ],
            'search' => $search,
            'filter' => $filter,
            'sort' => $sort,
            'sortOptions' => $sortOptions,
        ];
    }

    /**
     * One row per customer identity. The identity is computed in a derived table because MariaDB's
     * ONLY_FULL_GROUP_BY rejects selecting an expression that is only repeated in GROUP BY.
     */
    private function grouped(int $tenantId, string $search): Builder
    {
        $bookings = DB::table('bookings')
            ->select(['namapelanggan', 'email', 'nomorhp', 'tanggalbooking', 'created_at'])
            ->selectRaw(CustomerIdentity::sql().' AS identifier')
            ->where('idtenant', $tenantId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('namapelanggan', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('nomorhp', 'like', $like);
                });
            });

        return DB::query()
            ->fromSub($bookings, 'customer_bookings')
            ->select([
                'identifier',
                DB::raw('MAX(namapelanggan) AS name'),
                DB::raw('MAX(email) AS email'),
                DB::raw('MAX(nomorhp) AS phone'),
                DB::raw('COUNT(*) AS total_bookings'),
                DB::raw('MAX(tanggalbooking) AS last_booking_date'),
                DB::raw('MIN(created_at) AS first_seen'),
            ])
            ->groupBy('identifier');
    }

    /**
     * The next pending or paid booking of each customer, as "d M Y H:i".
     *
     * @param  list<string>  $identifiers
     * @return array<string, string>
     */
    private function upcoming(int $tenantId, array $identifiers): array
    {
        if ($identifiers === []) {
            return [];
        }

        $rows = DB::table('bookings')
            ->select(['tanggalbooking', 'jam'])
            ->selectRaw(CustomerIdentity::sql().' AS identifier')
            ->where('idtenant', $tenantId)
            ->where('tanggalbooking', '>=', Carbon::today()->toDateString())
            ->whereIn('status', [BookingState::STATUS_PENDING, BookingState::STATUS_PAID])
            ->whereIn(DB::raw(CustomerIdentity::sql()), $identifiers)
            ->orderBy('tanggalbooking')
            ->orderBy('jam')
            ->get();

        $upcoming = [];
        foreach ($rows as $row) {
            $upcoming[(string) $row->identifier] ??= Carbon::parse((string) $row->tanggalbooking)->format('d M Y').' '.substr((string) $row->jam, 0, 5);
        }

        return $upcoming;
    }
}

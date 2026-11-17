<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingPricing;
use App\Domain\Booking\BookingTimeRange;
use App\Domain\Payment\PaymentState;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use stdClass;

final class ListOwnerBookings
{
    /** @param array<string, mixed> $filters
     * @return Builder<Booking>
     */
    public function query(?Tenant $tenant, array $filters, bool $streaming = false): Builder
    {
        $keyword = trim((string) ($filters['katakunci'] ?? ''));
        $query = Booking::query()->with([
            'layanan' => fn (BelongsTo $service) => $service->where('services.idtenant', $tenant?->id),
            'payment' => fn (BelongsTo $payment) => $payment->where('payments.idtenant', $tenant?->id),
        ]);
        if ($tenant === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($streaming) {
            // scope-bypass: the stream runs after middleware clears context, and every row is filtered to the authenticated tenant below.
            $query->withoutGlobalScope(TenantScope::class);
        }

        return $query->where('bookings.idtenant', $tenant->id)
            ->when($keyword !== '', fn (Builder $rows) => $rows->where(function (Builder $search) use ($keyword): void {
                foreach (['booking_code', 'namapelanggan', 'email', 'nomorhp'] as $column) {
                    $search->orWhereLike($column, '%'.$keyword.'%');
                }
            }))
            ->when($filters['from'] ?? null, fn (Builder $rows, string $from) => $rows->whereDate('tanggalbooking', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $rows, string $to) => $rows->whereDate('tanggalbooking', '<=', $to))
            ->when($filters['service'] ?? null, fn (Builder $rows, int $service) => $rows->where('idlayanan', $service))
            ->when(($filters['source'] ?? null) === 'walkin', fn (Builder $rows) => $rows->whereHas('payment', fn (Builder $payment) => $payment->where('payments.idtenant', $tenant->id)->whereLike('order_id', 'WLK-%')))
            ->when(($filters['source'] ?? null) === 'online', fn (Builder $rows) => $rows->whereDoesntHave('payment', fn (Builder $payment) => $payment->where('payments.idtenant', $tenant->id)->whereLike('order_id', 'WLK-%')));
    }

    /** @param Builder<Booking> $query
     * @return Builder<Booking>
     */
    public function tab(Builder $query, string $tab, bool $canReview): Builder
    {
        $now = Carbon::now('Asia/Jakarta');
        $date = $now->toDateString();
        $time = $now->format('H:i:s');

        return match ($tab) {
            'action' => $query->where(function (Builder $rows) use ($canReview): void {
                foreach ($this->actionQueries($canReview) as $reason) {
                    $rows->orWhereExists($reason);
                }
            }),
            'upcoming' => $query->where('status', 'paid')->where(fn (Builder $rows) => $rows->whereDate('tanggalbooking', '>', $date)
                ->orWhere(fn (Builder $today) => $today->whereDate('tanggalbooking', $date)->where('jam', '>=', $time))),
            'completed' => $query->whereIn('status', ['completed', 'no_show']),
            'today' => $query->whereDate('tanggalbooking', $date),
            'pending', 'paid', 'cancelled' => $query->where('status', $tab),
            default => $query,
        };
    }

    /** @return array<string, QueryBuilder> */
    private function actionQueries(bool $canReview): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $date = $now->toDateString();
        $time = $now->format('H:i:s');
        $ended = DB::query()->selectRaw('1')->from('bookings', 'ended')
            ->whereColumn('ended.id', 'bookings.id')->where('ended.status', 'paid')
            ->where(function (QueryBuilder $past) use ($date, $time): void {
                $past->whereDate('ended.tanggalbooking', '<', $date)
                    ->orWhere(fn (QueryBuilder $sameDay) => $sameDay->whereDate('ended.tanggalbooking', $date)
                        ->whereExists(DB::table('schedules')->selectRaw('1')
                            ->whereColumn('schedules.idtenant', 'ended.idtenant')->whereColumn('schedules.id', 'ended.idschedule')
                            ->where('jam_selesai', '<', $time)));
            });
        $queries = ['needs_outcome' => $ended];
        if ($canReview) {
            $queries['needs_refund'] = DB::table('refund_requests')->selectRaw('1')
                ->whereColumn('refund_requests.idtenant', 'bookings.idtenant')
                ->whereColumn('refund_requests.idpayment', 'bookings.idpayment')->where('refund_requests.status', 'open');
            $queries['needs_cancellation'] = DB::table('booking_cancellation_requests')->selectRaw('1')
                ->whereColumn('booking_cancellation_requests.idtenant', 'bookings.idtenant')
                ->whereColumn('booking_cancellation_requests.booking_id', 'bookings.id')->where('booking_cancellation_requests.status', 'pending');
        }

        return $queries;
    }

    /**
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function withActionReasons(Builder $query, bool $canReview): Builder
    {
        return $query->select('bookings.*')->addSelect(array_map(
            fn (QueryBuilder $reason): QueryBuilder => $reason->limit(1),
            $this->actionQueries($canReview),
        ));
    }

    /** @return list<string> */
    public function reasons(Booking $booking): array
    {
        return array_values(array_filter([
            $booking->getAttribute('needs_refund') ? 'Permintaan refund menunggu keputusan' : null,
            $booking->getAttribute('needs_cancellation') ? 'Permintaan pembatalan dari Kasir' : null,
            $booking->getAttribute('needs_outcome') ? 'Sesi berakhir, hasil belum ditandai' : null,
        ]));
    }

    /** @return LengthAwarePaginator<int, stdClass> */
    public function cancellationRequests(Tenant $tenant): LengthAwarePaginator
    {
        return DB::table('booking_cancellation_requests as requests')
            ->join('bookings', fn ($join) => $join->on('bookings.id', '=', 'requests.booking_id')->on('bookings.idtenant', '=', 'requests.idtenant'))
            ->leftJoin('schedules', fn ($join) => $join->on('schedules.id', '=', 'bookings.idschedule')->on('schedules.idtenant', '=', 'requests.idtenant'))
            ->leftJoin('tenant_members', fn ($join) => $join->on('tenant_members.iduser', '=', 'requests.requested_by')->on('tenant_members.idtenant', '=', 'requests.idtenant'))
            ->leftJoin('users', 'users.id', '=', 'tenant_members.iduser')
            ->where('requests.idtenant', $tenant->id)->where('requests.status', 'pending')->orderBy('requests.id')
            ->paginate(10, ['requests.id', 'requests.booking_id', 'requests.reason', 'bookings.booking_code', 'bookings.namapelanggan',
                'bookings.tanggalbooking', 'bookings.jam', 'schedules.jam_mulai', 'schedules.jam_selesai', 'users.namalengkap as requester'],
                pageName: 'permintaan')
            ->withQueryString()
            ->through(function (stdClass $item): stdClass {
                $date = Carbon::parse($item->tanggalbooking);
                $range = $item->jam_mulai !== null && $item->jam_selesai !== null
                    ? BookingTimeRange::forSlot($date->toDateString(), $item->jam_mulai, $item->jam_selesai)
                    : BookingTimeRange::withoutSlot($date->toDateString(), (string) $item->jam);
                $item->session = $date->format('d/m/Y').' · '.$range->label().' WIB';

                return $item;
            });
    }

    public function paidAmount(Booking $booking): ?int
    {
        $payment = $booking->payment;
        if (! $payment instanceof Payment || $payment->status !== PaymentState::STATUS_SUKSES) {
            return null;
        }

        return BookingPricing::frozenSlotAmount($payment, (int) $booking->idschedule) ?? (int) $payment->jumlah;
    }
}

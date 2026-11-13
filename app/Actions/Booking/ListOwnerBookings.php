<?php

namespace App\Actions\Booking;

use App\Domain\Booking\BookingPricing;
use App\Domain\Payment\PaymentState;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

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
            'action' => $query->where(function (Builder $rows) use ($date, $time, $canReview): void {
                $rows->where(fn (Builder $paid) => $paid->where('status', 'paid')->where(function (Builder $past) use ($date, $time): void {
                    $past->whereDate('tanggalbooking', '<', $date)
                        ->orWhere(fn (Builder $sameDay) => $sameDay->whereDate('tanggalbooking', $date)
                            ->whereExists(fn (QueryBuilder $slot) => $slot->selectRaw('1')->from('schedules')
                                ->whereColumn('schedules.idtenant', 'bookings.idtenant')->whereColumn('schedules.id', 'bookings.idschedule')
                                ->where('jam_selesai', '<', $time)));
                }));
                if ($canReview) {
                    $rows->orWhereExists(fn (QueryBuilder $refund) => $refund->selectRaw('1')->from('refund_requests')
                        ->whereColumn('refund_requests.idtenant', 'bookings.idtenant')->whereColumn('refund_requests.idpayment', 'bookings.idpayment')->where('refund_requests.status', 'open'))
                        ->orWhereExists(fn (QueryBuilder $cancel) => $cancel->selectRaw('1')->from('booking_cancellation_requests')
                            ->whereColumn('booking_cancellation_requests.idtenant', 'bookings.idtenant')->whereColumn('booking_id', 'bookings.id')->where('booking_cancellation_requests.status', 'pending'));
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

    public function paidAmount(Booking $booking): ?int
    {
        $payment = $booking->payment;
        if (! $payment instanceof Payment || $payment->status !== PaymentState::STATUS_SUKSES) {
            return null;
        }

        return BookingPricing::frozenSlotAmount($payment, (int) $booking->idschedule) ?? (int) $payment->jumlah;
    }
}

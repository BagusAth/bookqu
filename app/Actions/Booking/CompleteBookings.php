<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Actions\Schedule\ClaimSlots;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\Booking\BookingTimeRange;
use App\Models\Booking;
use App\Models\Tenant;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Completes several ended sessions at once: bulk completion by a team member (FR-BOOKING-025)
 * and automatic completion by the scheduler (FR-BOOKING-024). Every booking goes through
 * `UpdateBookingStatus`, so the state machine and payment rules stay in one place; this
 * Action only decides which bookings are eligible and reports what it skipped.
 */
final class CompleteBookings
{
    /** Hours after the session ends before the system completes a booking (D-026). */
    public const AUTO_COMPLETE_AFTER_HOURS = 6;

    public const BATCH_SIZE = 200;

    public const REQUEST_LIMIT = 500;

    public function __construct(private UpdateBookingStatus $updateBookingStatus) {}

    /**
     * @param  list<int>  $bookingIds  ids from the request; anything outside the tenant is skipped
     * @return array{completed: int, skipped: int}
     */
    public function execute(Tenant $tenant, array $bookingIds, string $actor, ?Carbon $endedBefore = null): array
    {
        return app(TenantContext::class)->run((int) $tenant->id, fn (): array => $this->completeIds($tenant, $bookingIds, $actor, $endedBefore));
    }

    /**
     * @param  list<int>  $bookingIds
     * @return array{completed: int, skipped: int}
     */
    private function completeIds(Tenant $tenant, array $bookingIds, string $actor, ?Carbon $endedBefore): array
    {
        $endedBefore ??= Carbon::now('Asia/Jakarta');
        $ids = array_values(array_unique(array_map('intval', $bookingIds)));
        $completed = 0;

        $bookings = Booking::query()->where('idtenant', $tenant->id)->whereIn('id', $ids)->get();

        foreach ($bookings as $booking) {
            $done = DB::transaction(function () use ($tenant, $booking, $actor, $endedBefore): bool {
                ClaimSlots::lockTenant((int) $tenant->id);
                $booking->refresh();
                $booking->load('schedule');

                if (! $this->isEligible($booking, $endedBefore)) {
                    return false;
                }

                return $this->updateBookingStatus
                    ->execute($booking, BookingState::STATUS_COMPLETED, $actor, notify: false)['success'];
            });

            $completed += $done ? 1 : 0;
        }

        return ['completed' => $completed, 'skipped' => count($ids) - $completed];
    }

    /**
     * Paid bookings of the tenant whose session ended before the cutoff, oldest first.
     *
     * @return list<int>
     */
    public function endedIds(Tenant $tenant, Carbon $endedBefore): array
    {
        return app(TenantContext::class)->run((int) $tenant->id, fn (): array => $this->endedQuery($tenant, $endedBefore)
            ->orderBy('tanggalbooking')->pluck('id')->map(fn ($id): int => (int) $id)->all());
    }

    public function endedCount(Tenant $tenant, Carbon $endedBefore): int
    {
        return app(TenantContext::class)->run((int) $tenant->id, fn (): int => $this->endedQuery($tenant, $endedBefore)->count());
    }

    /** @return array{completed: int, skipped: int} */
    public function completeEnded(Tenant $tenant, string $actor, ?Carbon $endedBefore = null, ?Carbon $endedSince = null, ?int $limit = null): array
    {
        return app(TenantContext::class)->run((int) $tenant->id, fn (): array => $this->completeAllEnded($tenant, $actor, $endedBefore, $endedSince, $limit));
    }

    /** @return array{completed: int, skipped: int} */
    private function completeAllEnded(Tenant $tenant, string $actor, ?Carbon $endedBefore, ?Carbon $endedSince, ?int $limit): array
    {
        $endedBefore ??= Carbon::now('Asia/Jakarta');
        $query = $this->endedQuery($tenant, $endedBefore, $endedSince);
        $result = ['completed' => 0, 'skipped' => 0];
        $lastId = (clone $query)->max('id');
        $query->where('id', '<=', $lastId ?? 0)->select('bookings.id')
            ->when($limit !== null, fn (Builder $query) => $query->limit($limit))
            ->chunkById(self::BATCH_SIZE, function ($bookings) use ($tenant, $actor, $endedBefore, &$result): void {
                $batch = $this->execute($tenant, $bookings->modelKeys(), $actor, $endedBefore);
                $result['completed'] += $batch['completed'];
                $result['skipped'] += $batch['skipped'];
            });

        return $result;
    }

    /** @return Builder<Booking> */
    private function endedQuery(Tenant $tenant, Carbon $endedBefore, ?Carbon $endedSince = null): Builder
    {
        $end = BookingTimeRange::slotEndSql(DB::connection()->getDriverName(), 'bookings.tanggalbooking', 'schedules.jam_mulai', 'schedules.jam_selesai');

        return Booking::query()
            ->where('idtenant', $tenant->id)
            ->where('status', BookingState::STATUS_PAID)
            ->whereDate('tanggalbooking', '<=', $endedBefore->toDateString())
            ->whereExists(fn ($slots) => $slots->selectRaw('1')->from('schedules')
                ->whereColumn('schedules.id', 'bookings.idschedule')
                ->whereColumn('schedules.idtenant', 'bookings.idtenant')
                ->whereRaw($end.' <= ?', [$endedBefore->toDateTimeString()])
                ->when($endedSince !== null, fn ($query) => $query->whereRaw($end.' >= ?', [$endedSince?->toDateTimeString()])));
    }

    private function isEligible(Booking $booking, Carbon $endedBefore): bool
    {
        if ($booking->status !== BookingState::STATUS_PAID || ! BookingRules::sessionHasEnded([$booking], $endedBefore)) {
            return false;
        }

        $openRefund = $booking->idpayment !== null && DB::table('refund_requests')
            ->where('idtenant', $booking->idtenant)->where('idpayment', $booking->idpayment)->where('status', 'open')->exists();
        $pendingCancellation = DB::table('booking_cancellation_requests')
            ->where('idtenant', $booking->idtenant)->where('booking_id', $booking->id)->where('status', 'pending')->exists();

        return ! $openRefund && ! $pendingCancellation;
    }
}

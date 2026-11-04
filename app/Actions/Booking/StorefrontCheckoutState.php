<?php

namespace App\Actions\Booking;

use App\Actions\CustomerAccess\CustomerAccessLinks;
use App\Domain\Booking\BookingRules;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Storefront checkout state that lives in the customer's session: a fingerprint of what the
 * checkout page showed, and the customer's own unfinished payment for the "resume" banner.
 */
final class StorefrontCheckoutState
{
    private const PENDING = 'booking_checkout';

    /**
     * Fingerprint of service, date, slots and slot prices shown on the checkout page. A
     * different value at submit means another tab changed the selection or a price changed.
     *
     * @param  iterable<Schedule>  $schedules
     */
    public function fingerprint(Service $service, string $date, iterable $schedules): string
    {
        $schedules = collect($schedules)->sortBy('id')->values()->all();
        $prices = BookingRules::calculateSlotPrices($schedules, $service)['slot_prices'];
        $parts = [(int) $service->id, Carbon::parse($date)->toDateString()];
        foreach ($schedules as $index => $schedule) {
            $parts[] = (int) $schedule->id.'@'.number_format($prices[$index], 2, '.', '');
        }

        return hash_hmac('sha256', implode('|', $parts), (string) config('app.key'));
    }

    /**
     * @param  list<int>  $scheduleIds
     */
    public function fingerprintFor(Tenant $tenant, Service $service, string $date, array $scheduleIds): ?string
    {
        $schedules = Schedule::query()
            ->where('idtenant', $tenant->id)
            ->where('idlayanan', $service->id)
            ->whereDate('tanggal', $date)
            ->whereIn('id', array_map('intval', $scheduleIds))
            ->get();

        return $schedules->count() === count(array_unique($scheduleIds)) ? $this->fingerprint($service, $date, $schedules) : null;
    }

    public function remember(Payment $payment, ?string $fingerprint = null): void
    {
        session()->put(self::PENDING, ['tenant_id' => (int) $payment->idtenant, 'payment_id' => (int) $payment->id, 'fingerprint' => $fingerprint]);
    }

    /**
     * The customer's unpaid checkout at this tenant, if it can still be paid.
     *
     * @return array{payment: Payment, url: string, service: string, date: string, times: string, deadline: string}|null
     */
    public function pending(Tenant $tenant): ?array
    {
        $state = session(self::PENDING);
        if (! is_array($state) || (int) ($state['tenant_id'] ?? 0) !== (int) $tenant->id) {
            return null;
        }

        $payment = Payment::query()->where('idtenant', $tenant->id)->with(['bookings.layanan', 'tenant'])->find((int) ($state['payment_id'] ?? 0));
        if (! $payment instanceof Payment || ! $payment->isPending() || $payment->isExpired() || $payment->bookings->isEmpty()) {
            session()->forget(self::PENDING);

            return null;
        }

        /** @var Collection<int, Booking> $bookings */
        $bookings = $payment->bookings->sortBy('jam')->values();
        $first = $bookings->first();

        return [
            'payment' => $payment,
            'url' => app(CustomerAccessLinks::class)->payment($payment, 'payment'),
            'service' => $first->layanan instanceof Service ? (string) $first->layanan->namalayanan : '',
            'date' => Carbon::parse($first->getRawOriginal('tanggalbooking'))->locale('id')->translatedFormat('l, j F Y'),
            'times' => $bookings->map(fn (Booking $booking) => substr((string) $booking->jam, 0, 5))->implode(', '),
            'deadline' => $payment->expired_at ? Carbon::parse($payment->expired_at)->setTimezone('Asia/Jakarta')->format('H:i') : '',
        ];
    }
}

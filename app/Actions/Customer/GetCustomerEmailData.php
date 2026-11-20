<?php

namespace App\Actions\Customer;

use App\Actions\Booking\GetBookingTimeRanges;
use App\Domain\Booking\BookingPricing;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\CustomerBookingRoutes;
use App\Support\PaymentMethodLabel;
use App\Support\Rupiah;
use App\Support\StatusBadge;
use App\Support\StorefrontBranding;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use LogicException;

final class GetCustomerEmailData
{
    public function __construct(private GetBookingTimeRanges $ranges) {}

    /**
     * @param  iterable<Booking>  $bookings  Reservation records resolved by the mail delivery action.
     * @return array<string, mixed>
     */
    public function execute(iterable $bookings, ?string $manageUrl = null, ?Payment $groupPayment = null): array
    {
        $bookings = new Collection(collect($bookings)->values()->all());
        $booking = $bookings->firstOrFail();
        if ($bookings->contains(fn (Booking $row): bool => (int) $row->idtenant !== (int) $booking->idtenant)
            || ($groupPayment !== null && (int) $groupPayment->idtenant !== (int) $booking->idtenant)) {
            throw new LogicException('Customer email must belong to a single business.');
        }

        $bookings->loadMissing(['tenant', 'layanan', 'payment', 'refund', 'schedule']);
        $tenant = $booking->tenant;
        if (! $tenant instanceof Tenant) {
            throw new LogicException('Customer email requires its business.');
        }

        $ranges = $this->ranges->execute($bookings);
        $sessions = $bookings->sortBy(fn (Booking $row) => $ranges['sessionRanges'][$row->id]->start)
            ->values()->map(function (Booking $row) use ($ranges): array {
                $range = $ranges['sessionRanges'][$row->id];
                $service = $row->layanan;

                return [
                    'service' => $service instanceof Service ? $service->namalayanan : 'Layanan',
                    'date' => $range->start->locale('id')->translatedFormat('l, d M Y'),
                    'time' => $range->displayLabel().' WIB',
                ];
            })->all();
        $previous = null;
        if ($booking->rescheduled_from_date && $booking->rescheduled_from_time) {
            $old = clone $booking;
            $old->forceFill([
                'tanggalbooking' => $booking->rescheduled_from_date,
                'jam' => $booking->rescheduled_from_time,
                'idschedule' => $booking->rescheduled_from_schedule,
            ]);
            $range = $this->ranges->execute([$old])['timeRange'];
            $previous = $range->start->locale('id')->translatedFormat('l, d M Y').' · '.$range->displayLabel().' WIB';
        }
        $refund = $booking->refund;
        $address = trim((string) $tenant->alamat);
        $brand = StorefrontBranding::for($tenant);
        $payment = $booking->payment;
        $schedule = $booking->schedule;
        $service = $booking->layanan;
        $amount = $groupPayment->jumlah
            ?? ($payment instanceof Payment && ! $booking->isMultiSlot() ? $payment->jumlah : null)
            ?? BookingPricing::frozenSlotAmount($payment instanceof Payment ? $payment : null, (int) $booking->idschedule)
            ?? ($schedule instanceof Schedule ? $schedule->harga_override : null)
            ?? ($service instanceof Service ? $service->harga : 0);

        return [
            'businessName' => $tenant->namabisnis,
            'logoUrl' => $tenant->logo_path ? url(Storage::url($tenant->logo_path)) : null,
            'brandColor' => $brand['color'],
            'brandText' => $brand['onPrimary'],
            'customerName' => $booking->namapelanggan,
            'reference' => $groupPayment->order_id ?? $booking->booking_code,
            'referenceLabel' => $groupPayment ? 'Nomor pesanan' : 'Kode reservasi',
            'sessions' => $sessions,
            'previousSession' => $previous,
            'address' => $address,
            'mapUrl' => $address !== '' ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address) : null,
            'totalLabel' => Rupiah::format($amount),
            'paymentMethod' => PaymentMethodLabel::for($groupPayment?->metode),
            'refundLabel' => $refund instanceof Refund ? Rupiah::format($refund->jumlah) : null,
            'refundStatus' => $refund instanceof Refund ? StatusBadge::for('refund', $refund->status)[0] : null,
            'actionUrl' => $manageUrl ?? ($tenant->slug
                ? CustomerBookingRoutes::canonical('customer.booking.program', ['slug_usaha' => $tenant->slug]) : null),
            'actionLabel' => $manageUrl ? 'Lihat reservasi' : 'Lihat layanan',
        ];
    }
}

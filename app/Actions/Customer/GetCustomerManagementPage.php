<?php

namespace App\Actions\Customer;

use App\Actions\Booking\GetBookingTimeRanges;
use App\Actions\Payment\GetCustomerRefundForm;
use App\Domain\Booking\BookingRules;
use App\Domain\Booking\BookingState;
use App\Domain\CustomerAccess\AccessContext;
use App\Domain\Payment\PaymentState;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\CustomerBookingRoutes;
use App\Support\PaymentMethodLabel;
use App\Support\Rupiah;
use Carbon\Carbon;

final class GetCustomerManagementPage
{
    public function __construct(private GetBookingTimeRanges $ranges, private GetCustomerRefundForm $refundForm) {}

    /**
     * @param  iterable<Booking>  $bookings  Bookings resolved from the management grant.
     * @return array<string, mixed>
     */
    public function execute(AccessContext $access, Tenant $tenant, iterable $bookings, ?Payment $payment, bool $group): array
    {
        $bookings = collect($bookings)->values();
        $booking = $bookings->firstOrFail();
        $ranges = $this->ranges->execute($bookings);
        $bookingCode = $booking->booking_code;
        $isMulti = $bookings->count() > 1 || $booking->isMultiSlot();
        $paid = $payment?->status === PaymentState::STATUS_SUKSES;
        $allowsScheduleAction = ! $isMulti && (! $group || $paid);
        $canReschedule = $allowsScheduleAction && $booking->canBeRescheduled();
        $deadline = $allowsScheduleAction && $booking->status === BookingState::STATUS_PAID
            ? BookingRules::rescheduleDeadline($booking, $ranges['timeRange']) : null;
        $refund = $group ? null : $booking->refund;
        $status = $group && ! $paid
            ? PaymentState::formatLabel($payment->status ?? PaymentState::STATUS_PENDING, customer: true)
            : BookingState::formatLabel($booking->status);
        $date = $ranges['timeRange']->start->locale('id')->translatedFormat('l, d M Y');
        $address = trim((string) $tenant->alamat);
        $reference = $group ? $payment?->order_id : $bookingCode;
        $merchantWaUrl = GetCustomerPanel::whatsAppUrl((string) $tenant->nomorhp,
            "Halo {$tenant->namabisnis}, saya ingin bertanya mengenai reservasi saya ({$reference}).");
        $items = $bookings->sortBy(fn (Booking $item) => $ranges['sessionRanges'][$item->id]->start)
            ->values()->map(function (Booking $item) use ($ranges): array {
                $service = $item->layanan;

                return [
                    'service' => $service instanceof Service ? $service->namalayanan : 'Layanan',
                    'time' => $ranges['sessionRanges'][$item->id]->displayLabel(),
                    'minutes' => $ranges['sessionRanges'][$item->id]->durationMinutes(),
                ];
            })->all();
        $invoiceUrl = match (true) {
            ! $paid => null,
            $group => route('booking.manage.payment.invoice', ['order_id' => $payment->order_id]),
            in_array($booking->status, [BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED], true) => route('booking.manage.invoice', ['booking_code' => $bookingCode]),
            default => null,
        };

        return $this->refundForm->execute($access, $booking) + [
            'tenant' => $tenant,
            'booking' => $booking,
            'isMultiSlot' => $isMulti,
            'reference' => $reference,
            'referenceLabel' => $group ? 'Nomor pesanan' : 'Kode reservasi',
            'isGroupPage' => $group,
            'statusLabel' => $status,
            'statusTone' => match ($booking->status) {
                BookingState::STATUS_PAID, BookingState::STATUS_COMPLETED => 'success',
                BookingState::STATUS_CANCELLED, BookingState::STATUS_NO_SHOW => 'danger',
                default => 'warning',
            },
            'sessionDate' => $date,
            'sessions' => $items,
            'totalLabel' => $payment ? Rupiah::format($payment->jumlah) : null,
            'methodLabel' => PaymentMethodLabel::for($payment?->metode),
            'paymentLabel' => $payment ? PaymentState::formatLabel($payment->status, customer: true) : null,
            'refundTotal' => $refund instanceof Refund ? Rupiah::format($refund->jumlah) : null,
            'merchantWaUrl' => $merchantWaUrl,
            'storefront' => CustomerBookingRoutes::url('customer.booking.program', $tenant->slug),
            'rescheduleUrl' => $canReschedule && $bookingCode ? route('booking.manage.reschedule.show', ['booking_code' => $bookingCode]) : null,
            'rescheduleDeadline' => $deadline?->locale('id')->translatedFormat('d M Y, H.i'),
            'invoiceUrl' => $invoiceUrl,
            'mapsUrl' => $address !== '' ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address) : null,
            'history' => $this->history($bookings->flatMap(fn (Booking $row) => $row->logs)->whereInstanceOf(BookingLog::class)->values()->all()),
            'canReview' => ! $group && $booking->status === BookingState::STATUS_COMPLETED,
            'calendarUrl' => 'https://calendar.google.com/calendar/render?'.http_build_query([
                'action' => 'TEMPLATE', 'text' => 'Reservasi '.$tenant->namabisnis, 'location' => $address,
            ]).'&dates='.$ranges['timeRange']->googleCalendarDates(),
            'shareUrl' => 'https://api.whatsapp.com/send?text='.urlencode("Reservasi di {$tenant->namabisnis}\n{$date}\n".$ranges['timeRange']->label().' WIB'),
        ];
    }

    /** @param list<BookingLog> $logs */
    private function history(array $logs): array
    {
        $labels = [
            'created' => 'Reservasi dibuat', 'payment_pending' => 'Menunggu pembayaran',
            'payment_success' => 'Pembayaran diterima', 'paid' => 'Pembayaran diterima',
            'payment_failed' => 'Pembayaran gagal', 'cancelled' => 'Reservasi dibatalkan',
            'rescheduled' => 'Jadwal diubah', 'completed' => 'Reservasi selesai', 'no_show' => 'Tidak datang',
        ];

        return collect($logs)->filter(fn (BookingLog $log): bool => isset($labels[$log->event]))
            ->sortBy('created_at')->map(fn (BookingLog $log): array => [
                'label' => $labels[$log->event],
                'at' => Carbon::parse($log->created_at)->locale('id')->translatedFormat('d M Y, H.i'),
            ])->unique()->values()->all();
    }
}

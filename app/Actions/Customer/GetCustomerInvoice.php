<?php

namespace App\Actions\Customer;

use App\Actions\Booking\GetBookingTimeRanges;
use App\Actions\Booking\GetInvoiceAmounts;
use App\Domain\Payment\PaymentState;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\PaymentMethodLabel;
use App\Support\Rupiah;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

final class GetCustomerInvoice
{
    public function __construct(private GetBookingTimeRanges $ranges, private GetInvoiceAmounts $amounts) {}

    /**
     * @param  iterable<Booking>  $bookings  Bookings already authorized for this invoice.
     * @return array<string, mixed>
     */
    public function execute(Tenant $tenant, Payment $payment, iterable $bookings, ?string $backUrl = null, ?string $manageUrl = null): array
    {
        $bookings = collect($bookings)->values();
        $booking = $bookings->first();
        $ranges = $this->ranges->execute($bookings);
        $amounts = $this->amounts->execute($payment);
        $sessionDate = $ranges['timeRange']->start->locale('id')->translatedFormat('l, d M Y');
        $sessions = $bookings->map(function (Booking $row) use ($ranges, $amounts): array {
            $range = $ranges['sessionRanges'][$row->id];
            $service = $row->layanan;

            return [
                'service' => $service instanceof Service ? $service->namalayanan : 'Layanan',
                'time' => $range->displayLabel(),
                'minutes' => $range->durationMinutes(),
                'code' => $row->booking_code,
                'amount' => $amounts['invoiceSlotAmounts'][$row->id] ?? $amounts['invoiceSlotAmount'],
            ];
        })->all();
        $addOns = collect($payment->price_breakdown['add_ons'] ?? [])->map(fn (array $item): array => [
            'label' => $item['name'].' × '.$item['quantity'],
            'amount' => Rupiah::format($item['amount']),
        ])->all();
        $promo = $payment->price_breakdown['promo'] ?? null;
        if ($promo !== null) {
            $addOns[] = ['label' => 'Diskon promo', 'amount' => Rupiah::format(-$promo['discount'])];
        }

        $calendarUrl = 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => 'Reservasi '.$tenant->namabisnis,
            'details' => 'Nomor pesanan: '.$payment->order_id,
            'location' => $tenant->alamat,
            // Session times are WIB wall-clock times; without ctz Google reads them in the viewer's zone.
            'ctz' => 'Asia/Jakarta',
        ]).'&dates='.$ranges['timeRange']->googleCalendarDates();
        $sharedTimes = collect($ranges['sessionRanges'])->map(fn ($range): string => $range->label(' - ').' WIB')->implode("\n");
        $shareText = "Reservasi di {$tenant->namabisnis}\n{$sessionDate}\n{$sharedTimes}\nNomor pesanan: {$payment->order_id}";

        return [
            'tenant' => $tenant,
            'invoice' => [
                'number' => $payment->order_id,
                'date' => Carbon::parse($payment->updated_at ?? $payment->created_at)->timezone('Asia/Jakarta')->locale('id')->translatedFormat('l, d M Y'),
                'status' => $bookings->every(fn (Booking $row): bool => $row->status === 'cancelled')
                    ? 'Reservasi dibatalkan'
                    : PaymentState::formatLabel($payment->status, customer: true),
                'method' => PaymentMethodLabel::for($payment->metode) ?? 'Pembayaran online',
                'logo' => $tenant->logo_path ? Storage::url($tenant->logo_path) : null,
                'name' => $booking?->namapelanggan,
                'email' => $booking?->email,
                'phone' => $booking?->nomorhp,
                'sessionDate' => $sessionDate,
                'sessions' => $sessions,
                'extras' => $addOns,
                'amount' => $amounts['invoiceAmount'],
                'note' => $amounts['invoiceAmountNote'],
            ],
            'calendarUrl' => $calendarUrl,
            'shareUrl' => 'https://api.whatsapp.com/send?text='.urlencode($shareText),
            'mapsUrl' => filled($tenant->alamat) ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($tenant->alamat) : null,
            'invoiceBackUrl' => $backUrl,
            'manageUrl' => $manageUrl,
        ];
    }
}

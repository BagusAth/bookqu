<?php

declare(strict_types=1);

namespace App\Mail;

use App\Actions\Customer\GetCustomerEmailData;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BookingGroupInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $orderId;

    /**
     * @param  Collection<Booking>  $bookings
     */
    public function __construct(
        public Payment $payment,
        public Collection $bookings,
        public string $manageUrl
    ) {
        $this->orderId = $payment->order_id ?? ('PAY-'.$payment->id);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Konfirmasi reservasi dan bukti pembayaran — '.$this->orderId,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-group-invoice',
            with: app(GetCustomerEmailData::class)->execute($this->bookings, $this->manageUrl, $this->payment),
        );
    }
}

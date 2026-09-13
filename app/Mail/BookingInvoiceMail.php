<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $manageUrl;
    public string $priceLabel;
    public string $paymentDate;
    public string $paymentMethod;
    public string $orderId;

    public function __construct(public Booking $booking)
    {
        $this->manageUrl = $booking->getManageUrl();
        $this->priceLabel = $booking->priceLabel;

        $payment = $booking->payment;
        $this->paymentDate = $payment
            ? ($payment->updated_at ?? $payment->created_at)->format('d M Y, H:i') . ' WIB'
            : now()->format('d M Y, H:i') . ' WIB';
        $this->paymentMethod = $payment->metode ?? 'Gratis';
        $this->orderId = $payment->order_id ?? '-';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🧾 Invoice Pembayaran — ' . $this->booking->booking_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-invoice',
        );
    }
}

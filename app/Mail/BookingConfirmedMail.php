<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $manageUrl;
    public string $priceLabel;

    public function __construct(public Booking $booking)
    {
        $this->manageUrl  = $booking->getManageUrl();
        $this->priceLabel = $booking->priceLabel;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Booking Dikonfirmasi — ' . $this->booking->booking_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmed',
        );
    }
}

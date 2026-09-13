<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRescheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $manageUrl;

    public function __construct(public Booking $booking)
    {
        $this->manageUrl = $booking->getManageUrl();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📅 Jadwal Booking Diubah — ' . $this->booking->booking_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-rescheduled',
        );
    }
}

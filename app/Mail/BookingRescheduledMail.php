<?php

namespace App\Mail;

use App\Actions\Customer\GetCustomerEmailData;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRescheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking, public string $manageUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Jadwal reservasi diubah — '.$this->booking->booking_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-rescheduled',
            with: app(GetCustomerEmailData::class)->execute([$this->booking], $this->manageUrl),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Mail;

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

    public string $paymentDate;
    public string $paymentMethod;
    public string $orderId;
    public string $totalAmountFormatted;

    /**
     * @param Payment $payment
     * @param Collection<\App\Models\Booking> $bookings
     * @param string $manageUrl
     */
    public function __construct(
        public Payment $payment,
        public Collection $bookings,
        public string $manageUrl
    ) {
        $this->orderId = $payment->order_id ?? ('PAY-' . $payment->id);
        $this->paymentDate = ($payment->updated_at ?? $payment->created_at)->format('d M Y, H:i') . ' WIB';
        $this->paymentMethod = strtoupper($payment->metode ?: 'Midtrans');
        $this->totalAmountFormatted = 'Rp ' . number_format((float) $payment->jumlah, 0, ',', '.');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🧾 Konfirmasi Reservasi & Bukti Pembayaran — ' . $this->orderId,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-group-invoice',
        );
    }
}

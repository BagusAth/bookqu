<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Models\Booking;

class BookingPaidNotification extends Notification
{
    use Queueable;

    public Booking $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->booking->tenant;
        $service = $this->booking->service;
        
        // Generate the invoice URL
        $invoiceUrl = url('/' . $tenant->slug . '/booking/payment/' . $this->booking->payment->id . '/invoice');
        if ($tenant->custom_domain) {
            $invoiceUrl = 'http://' . $tenant->custom_domain . '/booking/payment/' . $this->booking->payment->id . '/invoice';
        }

        return (new MailMessage)
            ->subject('Pembayaran Booking Berhasil - ' . $tenant->namabisnis)
            ->greeting('Halo ' . $this->booking->namapelanggan . ',')
            ->line('Pembayaran untuk booking Anda telah berhasil kami terima. Berikut adalah detail booking Anda:')
            ->line('Layanan: ' . $service->namalayanan)
            ->line('Tanggal: ' . \Carbon\Carbon::parse($this->booking->tanggal)->translatedFormat('l, d F Y'))
            ->line('Waktu: ' . \Carbon\Carbon::parse($this->booking->waktu_mulai)->format('H:i') . ' - ' . \Carbon\Carbon::parse($this->booking->waktu_selesai)->format('H:i'))
            ->action('Lihat Invoice Anda', $invoiceUrl)
            ->line('Terima kasih telah menggunakan layanan dari ' . $tenant->namabisnis . '!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookingOwnerNotification extends Notification
{
    use Queueable;

    public Booking $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
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
        $booking  = $this->booking;
        $layanan  = $booking->layanan;
        $tenant   = $booking->tenant;
        $payment  = $booking->payment;

        $namaLayanan  = $layanan?->namalayanan ?? '-';
        $tanggal      = $booking->tanggalbooking?->translatedFormat('l, d F Y') ?? '-';
        $jam          = $booking->jam ?? '-';
        $harga        = 'Rp ' . number_format($payment?->jumlah ?? ($layanan?->harga ?? 0), 0, ',', '.');

        $dashboardUrl = url('/owner/bookings');

        return (new MailMessage)
            ->subject('📅 Booking Baru Masuk — ' . ($tenant->namabisnis ?? 'BookQu'))
            ->greeting('Halo ' . ($notifiable->namalengkap ?? 'Owner') . ',')
            ->line('Ada booking baru yang telah **berhasil dibayar**. Berikut detail booking:')
            ->line("**Pelanggan :** {$booking->namapelanggan}")
            ->line("**Layanan   :** {$namaLayanan}")
            ->line("**Tanggal   :** {$tanggal} pukul {$jam}")
            ->line("**Total     :** {$harga}")
            ->action('Lihat di Dashboard', $dashboardUrl)
            ->line('Pastikan Anda siap menyambut pelanggan pada jadwal tersebut.')
            ->salutation('Salam, Tim BookQu');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id'     => $this->booking->id,
            'namapelanggan'  => $this->booking->namapelanggan,
            'tanggalbooking' => $this->booking->tanggalbooking,
        ];
    }
}

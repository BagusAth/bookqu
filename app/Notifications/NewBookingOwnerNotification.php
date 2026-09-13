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
        return ['database', 'mail'];
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

        $namaLayanan  = $layanan?->namalayanan ?? 'Layanan';
        $tanggal      = $booking->tanggalbooking instanceof \Carbon\Carbon
            ? $booking->tanggalbooking->translatedFormat('l, d F Y')
            : ($booking->tanggalbooking ? \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('l, d F Y') : '-');
        $jam          = $booking->jam ?? '-';
        $nominal      = $payment?->jumlah ?? ($layanan?->harga ?? 0);
        $harga        = $nominal > 0 ? 'Rp ' . number_format($nominal, 0, ',', '.') : 'Gratis (Free)';

        $dashboardUrl = url('/owner/bookings?katakunci=' . urlencode($booking->booking_code ?? $booking->namapelanggan));

        $mail = (new MailMessage)
            ->subject('📅 Booking Baru Masuk — ' . ($tenant->namabisnis ?? 'BookQu'))
            ->greeting('Halo ' . ($notifiable->namalengkap ?? 'Owner') . ',')
            ->line('Ada booking baru yang telah **terkonfirmasi**. Berikut detail booking:')
            ->line("**Kode Booking:** " . ($booking->booking_code ?? '-'))
            ->line("**Pelanggan   :** {$booking->namapelanggan}")
            ->line("**Layanan     :** {$namaLayanan}")
            ->line("**Tanggal     :** {$tanggal} pukul {$jam}")
            ->line("**Total       :** {$harga}")
            ->action('Lihat di Dashboard', $dashboardUrl)
            ->line('Pastikan Anda siap menyambut pelanggan pada jadwal tersebut.')
            ->salutation('Salam, Tim BookQu');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $booking = $this->booking;
        $layanan = $booking->layanan;
        $payment = $booking->payment;

        $namaLayanan = $layanan?->namalayanan ?? 'Layanan';
        $tanggal = $booking->tanggalbooking instanceof \Carbon\Carbon
            ? $booking->tanggalbooking->translatedFormat('d M Y')
            : ($booking->tanggalbooking ? \Carbon\Carbon::parse($booking->tanggalbooking)->translatedFormat('d M Y') : '-');
        $nominal = $payment?->jumlah ?? ($layanan?->harga ?? 0);
        $harga = $nominal > 0 ? 'Rp ' . number_format($nominal, 0, ',', '.') : 'Gratis';

        return [
            'event_type'     => 'new_booking',
            'title'          => 'Booking Baru Masuk',
            'message'        => "{$booking->namapelanggan} memesan {$namaLayanan} untuk {$tanggal} pukul {$booking->jam}",
            'booking_id'     => $booking->id,
            'booking_code'   => $booking->booking_code,
            'customer_name'  => $booking->namapelanggan,
            'customer_email' => $booking->email,
            'customer_phone' => $booking->nomorhp,
            'service_name'   => $namaLayanan,
            'tanggal'        => $tanggal,
            'jam'            => $booking->jam,
            'amount'         => $harga,
            'status'         => $booking->status,
            'badge_color'    => 'emerald',
            'icon'           => 'calendar-plus',
            'url'            => url('/owner/bookings?katakunci=' . urlencode($booking->booking_code ?? $booking->namapelanggan)),
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingStatusChangedOwnerNotification extends Notification
{
    use Queueable;

    public Booking $booking;
    public string $action;
    public array $extra;

    /**
     * @param Booking $booking
     * @param string  $action ('cancelled', 'rescheduled', 'completed', 'status_updated')
     * @param array   $extra  Additional payload info, e.g. old_date, old_time, new_date, new_time, note
     */
    public function __construct(Booking $booking, string $action, array $extra = [])
    {
        $this->booking = $booking;
        $this->action = $action;
        $this->extra = $extra;
    }

    /**
     * Delivery channels: database and email
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Mail notification representation
     */
    public function toMail(object $notifiable): MailMessage
    {
        $booking  = $this->booking;
        $layanan  = $booking->layanan;
        $tenant   = $booking->tenant;
        $namaLayanan = $layanan?->namalayanan ?? 'Layanan';
        $bisnisName  = $tenant->namabisnis ?? 'BookQu';

        $dashboardUrl = url('/owner/bookings?katakunci=' . urlencode($booking->booking_code ?? $booking->namapelanggan));

        $mail = (new MailMessage)->greeting('Halo ' . ($notifiable->namalengkap ?? 'Owner') . ',');

        if ($this->action === 'cancelled') {
            $mail->subject("⚠️ Booking Dibatalkan — {$bisnisName}")
                ->line("Pemberitahuan: Booking oleh **{$booking->namapelanggan}** telah **dibatalkan**.")
                ->line("**Kode Booking:** " . ($booking->booking_code ?? '-'))
                ->line("**Layanan     :** {$namaLayanan}")
                ->line("**Jadwal Awal :** " . $this->formatDate($booking->tanggalbooking) . " pukul {$booking->jam}");

            if ($booking->payment && $booking->payment->status === 'sukses') {
                $mail->line('Status Refund: Otomatis diajukan (Pending verifikasi refund).');
            }

            $mail->line('Slot jadwal terkait kini telah otomatis tersedia kembali untuk pelanggan lain.');
        } elseif ($this->action === 'rescheduled') {
            $oldDate = isset($this->extra['old_date']) ? $this->formatDate($this->extra['old_date']) : '-';
            $oldTime = $this->extra['old_time'] ?? '-';
            $newDate = isset($this->extra['new_date']) ? $this->formatDate($this->extra['new_date']) : $this->formatDate($booking->tanggalbooking);
            $newTime = $this->extra['new_time'] ?? $booking->jam;

            $mail->subject("🗓️ Jadwal Booking Diubah (Rescheduled) — {$bisnisName}")
                ->line("Pelanggan **{$booking->namapelanggan}** telah mengubah jadwal booking:")
                ->line("**Kode Booking:** " . ($booking->booking_code ?? '-'))
                ->line("**Layanan     :** {$namaLayanan}")
                ->line("**Jadwal Lama :** {$oldDate} pukul {$oldTime}")
                ->line("**Jadwal Baru :** {$newDate} pukul {$newTime}")
                ->line('Slot lama telah dilepas dan slot baru telah berhasil dialokasikan.');
        } elseif ($this->action === 'completed') {
            $mail->subject("✅ Booking Selesai — {$bisnisName}")
                ->line("Booking atas nama **{$booking->namapelanggan}** telah ditandai sebagai **Selesai**.")
                ->line("**Kode Booking:** " . ($booking->booking_code ?? '-'))
                ->line("**Layanan     :** {$namaLayanan}")
                ->line("**Tanggal     :** " . $this->formatDate($booking->tanggalbooking) . " pukul {$booking->jam}");
        } else {
            $statusLabel = ucfirst($booking->status);
            $mail->subject("🔔 Perubahan Status Booking — {$bisnisName}")
                ->line("Status booking untuk **{$booking->namapelanggan}** telah diperbarui menjadi **{$statusLabel}**.")
                ->line("**Kode Booking:** " . ($booking->booking_code ?? '-'))
                ->line("**Layanan     :** {$namaLayanan}");
        }

        return $mail->action('Buka di Dashboard', $dashboardUrl)
            ->salutation('Salam, Tim BookQu');
    }

    /**
     * Database notification representation
     */
    public function toArray(object $notifiable): array
    {
        $booking = $this->booking;
        $layanan = $booking->layanan;
        $namaLayanan = $layanan?->namalayanan ?? 'Layanan';
        $tanggal = $this->formatDateShort($booking->tanggalbooking);

        $title = match ($this->action) {
            'cancelled'   => 'Booking Dibatalkan',
            'rescheduled' => 'Jadwal Booking Diubah',
            'completed'   => 'Booking Selesai',
            default       => 'Status Booking Berubah',
        };

        $message = match ($this->action) {
            'cancelled'   => "{$booking->namapelanggan} membatalkan reservasi {$namaLayanan} ({$tanggal})",
            'rescheduled' => "{$booking->namapelanggan} menjadwalkan ulang {$namaLayanan} untuk {$tanggal} pukul {$booking->jam}",
            'completed'   => "Booking {$namaLayanan} atas nama {$booking->namapelanggan} telah selesai",
            default       => "Status booking {$booking->namapelanggan} ({$namaLayanan}) diubah ke {$booking->status}",
        };

        $badgeColor = match ($this->action) {
            'cancelled'   => 'rose',
            'rescheduled' => 'amber',
            'completed'   => 'indigo',
            default       => 'blue',
        };

        $icon = match ($this->action) {
            'cancelled'   => 'x-circle',
            'rescheduled' => 'clock-rewind',
            'completed'   => 'check-circle-2',
            default       => 'refresh-cw',
        };

        return [
            'event_type'     => $this->action,
            'title'          => $title,
            'message'        => $message,
            'booking_id'     => $booking->id,
            'booking_code'   => $booking->booking_code,
            'customer_name'  => $booking->namapelanggan,
            'customer_email' => $booking->email,
            'customer_phone' => $booking->nomorhp,
            'service_name'   => $namaLayanan,
            'tanggal'        => $tanggal,
            'jam'            => $booking->jam,
            'status'         => $booking->status,
            'badge_color'    => $badgeColor,
            'icon'           => $icon,
            'extra'          => $this->extra,
            'url'            => url('/owner/bookings?katakunci=' . urlencode($booking->booking_code ?? $booking->namapelanggan)),
        ];
    }

    private function formatDate(mixed $date): string
    {
        if ($date instanceof Carbon) {
            return $date->translatedFormat('l, d F Y');
        }
        if (is_string($date) && !empty($date)) {
            try {
                return Carbon::parse($date)->translatedFormat('l, d F Y');
            } catch (\Exception) {}
        }
        return '-';
    }

    private function formatDateShort(mixed $date): string
    {
        if ($date instanceof Carbon) {
            return $date->translatedFormat('d M Y');
        }
        if (is_string($date) && !empty($date)) {
            try {
                return Carbon::parse($date)->translatedFormat('d M Y');
            } catch (\Exception) {}
        }
        return '-';
    }
}

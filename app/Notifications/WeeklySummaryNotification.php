<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Monday email for the Pemilik with last week's figures (FR-NOTIFICATION-008). */
class WeeklySummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{from: string, to: string, revenue: float, bookings: int, occupancy: float, openUntil: ?string, endedNotClosed: int, openRefunds: int}  $summary
     */
    public function __construct(public Tenant $tenant, public array $summary) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->summary;
        $mail = (new MailMessage)
            ->subject('Ringkasan minggu '.$s['from'].' – '.$s['to'].' · '.$this->tenant->namabisnis)
            ->greeting('Halo '.$notifiable->namalengkap.',')
            ->line('Ringkasan '.$this->tenant->namabisnis.' untuk '.$s['from'].' – '.$s['to'].':')
            ->line('Pendapatan bersih: Rp'.number_format($s['revenue'], 0, ',', '.'))
            ->line('Booking: '.$s['bookings'])
            ->line('Slot terisi: '.number_format($s['occupancy'], 1, ',', '.').'%')
            ->line($s['openUntil'] !== null ? 'Slot terbuka sampai: '.$s['openUntil'] : 'Belum ada slot terbuka ke depan.');

        $waiting = array_filter([
            $s['endedNotClosed'] > 0 ? $s['endedNotClosed'].' sesi lewat belum ditutup' : null,
            $s['openRefunds'] > 0 ? $s['openRefunds'].' permintaan refund menunggu keputusan' : null,
        ]);
        if ($waiting !== []) {
            $mail->line('Menunggu Anda: '.implode(', ', $waiting).'.');
        }

        return $mail->action('Buka Beranda', route('owner.dashboard'))
            ->line('Ringkasan ini bisa dimatikan di Pengaturan → Notifikasi.')
            ->salutation('Salam, Tim BookQu');
    }
}

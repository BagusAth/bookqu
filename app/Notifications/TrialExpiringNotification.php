<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiringNotification extends Notification
{
    use Queueable;

    public Subscription $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
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
        $namaBisnis = $this->subscription->tenant->namabisnis ?? 'bisnis Anda';
        $tanggalBerakhir = $this->subscription->trial_berakhir?->translatedFormat('l, d F Y') ?? '-';

        return (new MailMessage)
            ->subject('Masa Trial BookQu Anda Akan Berakhir Besok!')
            ->greeting('Halo ' . ($notifiable->namalengkap ?? 'Owner') . ',')
            ->line("Masa **Free Trial 7 Hari** untuk **{$namaBisnis}** akan berakhir pada **{$tanggalBerakhir}**.")
            ->line('Setelah masa trial habis, akses ke fitur premium akan dinonaktifkan secara otomatis.')
            ->action('Upgrade Sekarang', url('/owner/subscription'))
            ->line('Jangan lewatkan! Upgrade ke paket berbayar agar bisnis Anda terus berjalan tanpa gangguan.')
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
            'subscription_id' => $this->subscription->id,
            'trial_berakhir'  => $this->subscription->trial_berakhir,
        ];
    }
}

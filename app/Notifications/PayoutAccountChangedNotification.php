<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutAccountChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $actionType,
        public string $bankNama,
        public string $maskedNumber
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Perubahan Rekening Penarikan — BookQu')
            ->greeting('Halo '.($notifiable->namalengkap ?? 'Owner').',')
            ->line("Rekening penarikan {$this->actionType}: {$this->bankNama} {$this->maskedNumber}.")
            ->line('Jika ini bukan Anda, segera ganti password dan hubungi admin BookQu.')
            ->action('Cek Saldo & Rekening', url('/owner/settings/balance'));
    }
}

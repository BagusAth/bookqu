<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the previous address when an account's sign-in email changes (BQ-098).
 */
class AccountEmailChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $maskedNewEmail
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
            ->subject('Email Akun BookQu Anda Diubah')
            ->line("Email masuk akun BookQu Anda baru saja diubah menjadi {$this->maskedNewEmail}.")
            ->line('Jika ini bukan Anda, segera hubungi admin BookQu agar akun Anda dapat diamankan.');
    }
}

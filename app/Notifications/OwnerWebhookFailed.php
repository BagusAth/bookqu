<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

final class OwnerWebhookFailed extends Notification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Webhook gagal terkirim',
            'message' => 'Periksa riwayat pengiriman di Pengaturan API dan Webhook.',
            'url' => route('owner.api.index'),
        ];
    }
}

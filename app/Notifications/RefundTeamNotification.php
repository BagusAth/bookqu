<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RefundTeamNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $idtenant, public int $subjectId, public string $event)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->title())->line($this->title().' #'.$this->subjectId)
            ->action('Buka booking', route('owner.bookings'));
    }

    public function toArray(object $notifiable): array
    {
        return ['event_type' => $this->event, 'title' => $this->title(), 'message' => $this->title().' #'.$this->subjectId, 'url' => route('owner.bookings')];
    }

    private function title(): string
    {
        return match ($this->event) {
            'refund_request' => 'Permintaan refund baru',
            'refund_reminder' => 'Permintaan refund menunggu keputusan',
            'refund_processed' => 'Refund telah diproses',
            default => 'Transfer refund gagal',
        };
    }
}

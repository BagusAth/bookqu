<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscription $subscription, public int $daysBefore, public string $endsAt)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $when = $this->daysBefore === 0 ? 'hari ini' : 'dalam '.$this->daysBefore.' hari';

        return (new MailMessage)->subject('Paket BookQu berakhir '.$when)
            ->greeting('Halo '.$notifiable->namalengkap.',')->line('Paket bisnis Anda berakhir '.$when.' ('.$this->endsAt.' WIB).')
            ->line('Booking yang sudah ada tetap dapat dikelola. Perpanjang paket untuk menerima booking dan menambahkan data baru.')
            ->action('Kelola Paket & Tagihan', route('owner.subscription'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['event_type' => 'subscription_expiring', 'title' => 'Paket akan berakhir', 'message' => 'Paket berakhir '.$this->endsAt.' WIB.',
            'subscription_id' => $this->subscription->id, 'days_before' => $this->daysBefore, 'url' => route('owner.subscription')];
    }
}

<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Money arrived for a payment whose purchase can no longer be delivered (late
 * success after expiry or cancellation): a refund is owed (ADR-0004 decision 8).
 */
class PaymentRefundOwedOwnerNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public Refund $refund,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pembayaran diterima setelah dibatalkan — refund diperlukan')
            ->greeting('Halo '.($notifiable->namalengkap ?? 'Owner').',')
            ->line('Pembayaran untuk pesanan yang sudah dibatalkan atau kedaluwarsa ternyata berhasil diterima.')
            ->line('**Order ID:** '.$this->payment->order_id)
            ->line('**Pembayar :** '.($this->payment->nama_pembayar ?? '-'))
            ->line('**Jumlah   :** '.$this->refund->jumlah_formatted)
            ->line('Booking tetap dibatalkan. Kembalikan dana kepada pembayar, lalu tandai refund sebagai diproses.')
            ->salutation('Salam, Tim BookQu');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_refund_owed',
            'title' => 'Refund diperlukan',
            'message' => 'Pembayaran '.$this->payment->order_id.' diterima setelah pesanan dibatalkan. Kembalikan dana kepada pembayar.',
            'payment_id' => $this->payment->id,
            'refund_id' => $this->refund->id,
            'jumlah' => (float) $this->refund->jumlah,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Domain\Payout\PayoutState;
use App\Models\OwnerPayout;
use Illuminate\Notifications\Notification;

class OwnerPayoutStatusChangedNotification extends Notification
{
    public function __construct(public OwnerPayout $payout) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $status = (string) $this->payout->status;
        $amount = 'Rp '.number_format((float) $this->payout->jumlah, 0, ',', '.');
        $detail = match ($status) {
            PayoutState::SUCCESS => ' Ref: '.($this->payout->transfer_reference ?? '-'),
            PayoutState::REJECTED, PayoutState::FAILED => ' Alasan: '.($this->payout->rejection_reason ?? '-').'. Dana dikembalikan ke saldo.',
            default => '',
        };

        return [
            'event_type' => 'payout_status',
            'title' => 'Withdraw '.PayoutState::label($status),
            'message' => "Penarikan {$amount}: ".PayoutState::label($status).'.'.$detail,
            'payout_id' => $this->payout->id,
            'status' => $status,
            'badge_color' => in_array($status, [PayoutState::REJECTED, PayoutState::FAILED], true) ? 'red' : 'emerald',
            'icon' => 'wallet',
            'url' => url('/owner/settings/balance'),
        ];
    }
}

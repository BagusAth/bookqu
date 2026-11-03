<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\OwnerPayout;
use Illuminate\Notifications\Notification;

class NewPayoutRequestNotification extends Notification
{
    public function __construct(
        public OwnerPayout $payout,
        public ?string $tenantName = null
    ) {}

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
        $amountFormatted = 'Rp '.number_format((float) $this->payout->jumlah, 0, ',', '.');
        $tenantDisplay = $this->tenantName ?? ('Tenant #'.$this->payout->idtenant);

        return [
            'event_type' => 'new_payout_request',
            'title' => 'Pengajuan Withdraw Baru',
            'message' => "Tenant {$tenantDisplay} mengajukan withdraw sebesar {$amountFormatted}.",
            'payout_id' => $this->payout->id,
            'tenant_id' => $this->payout->idtenant,
            'jumlah' => (float) $this->payout->jumlah,
            'url' => url('/admin/payouts?status=pending'),
            'icon' => 'banknotes',
            'badge_color' => 'indigo',
        ];
    }
}

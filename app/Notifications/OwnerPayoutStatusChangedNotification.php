<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Domain\Payout\PayoutState;
use App\Models\OwnerPayout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OwnerPayoutStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public OwnerPayout $payout)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $status = (string) $this->payout->status;
        $channels = ['database'];

        if (in_array($status, [PayoutState::SUCCESS, PayoutState::REJECTED, PayoutState::FAILED], true)) {
            $channels[] = 'mail';
        }

        return $channels;
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
            PayoutState::APPROVED => ' (Disetujui, transfer diproses)',
            default => '',
        };

        return [
            'event_type' => 'payout_status',
            'title' => 'Withdraw '.PayoutState::ownerLabel($status),
            'message' => "Penarikan {$amount}: ".PayoutState::ownerLabel($status).$detail,
            'payout_id' => $this->payout->id,
            'status' => $status,
            'badge_color' => match (PayoutState::ownerTone($status)) {
                'danger' => 'red',
                'success' => 'emerald',
                default => 'amber',
            },
            'icon' => 'wallet',
            'url' => route('owner.payments', ['tab' => 'saldo']),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = (string) $this->payout->status;
        $amount = 'Rp '.number_format((float) $this->payout->jumlah, 0, ',', '.');
        $ownerLabel = PayoutState::ownerLabel($status);

        $number = (string) $this->payout->nomor_rekening;
        $masked = '••••'.(strlen($number) > 4 ? substr($number, -4) : $number);
        $bankAccount = "{$this->payout->bank_nama} ({$masked}) a.n. {$this->payout->nama_pemilik_rekening}";

        $subject = "Withdraw {$ownerLabel} — {$amount}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo!')
            ->line("Status pengajuan withdraw saldo Anda sebesar **{$amount}** telah diperbarui menjadi **{$ownerLabel}**.")
            ->line("**Rekening Tujuan:** {$bankAccount}");

        if ($status === PayoutState::SUCCESS) {
            if ($this->payout->transfer_reference) {
                $mail->line("**Referensi Transfer:** {$this->payout->transfer_reference}");
            }
            $mail->line('Dana telah berhasil ditransfer ke rekening Anda.');
        } elseif (in_array($status, [PayoutState::REJECTED, PayoutState::FAILED], true)) {
            if ($this->payout->rejection_reason) {
                $mail->line("**Alasan:** {$this->payout->rejection_reason}");
            }
            $mail->line('Dana penarikan telah dikembalikan ke saldo BookQu Anda.');
        }

        return $mail
            ->action('Lihat Saldo & Riwayat', route('owner.payments', ['tab' => 'saldo']))
            ->line('Terima kasih telah menggunakan BookQu!');
    }
}

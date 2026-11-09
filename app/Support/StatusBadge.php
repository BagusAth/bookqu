<?php

declare(strict_types=1);

namespace App\Support;

use App\Domain\Booking\BookingState;
use App\Domain\Payment\PaymentState;
use App\Domain\Payout\PayoutState;

/**
 * One label and one colour tone per state for every owner status badge (owner-portal.md §9,
 * BQ-129). Labels come from the domain formatters where they exist; refund labels live here
 * until the refund domain has its own formatter.
 */
final class StatusBadge
{
    public const TONES = ['neutral', 'info', 'success', 'warning', 'danger'];

    /**
     * @return array{0: string, 1: string} label and tone
     */
    public static function for(string $type, string $status): array
    {
        return match ($type) {
            'booking' => [BookingState::formatLabel($status), match ($status) {
                BookingState::STATUS_PENDING => 'warning',
                BookingState::STATUS_PAID => 'info',
                BookingState::STATUS_COMPLETED => 'success',
                'no_show' => 'danger',
                default => 'neutral',
            }],
            'payment' => self::known([PaymentState::STATUS_PENDING, PaymentState::STATUS_SUKSES, PaymentState::STATUS_GAGAL], $status)
                ? [PaymentState::formatLabel($status), match ($status) {
                    PaymentState::STATUS_PENDING => 'warning',
                    PaymentState::STATUS_SUKSES => 'success',
                    default => 'danger',
                }]
                : [$status, 'neutral'],
            'payout' => [PayoutState::ownerLabel($status), match ($status) {
                PayoutState::PENDING, PayoutState::APPROVED => 'warning',
                PayoutState::SUCCESS => 'success',
                PayoutState::FAILED, PayoutState::REJECTED => 'danger',
                default => 'neutral',
            }],
            'refund_request' => match ($status) {
                'open' => ['Menunggu keputusan', 'warning'],
                'accepted_full' => ['Diterima 100%', 'success'],
                'accepted_half' => ['Diterima 50%', 'success'],
                'rejected' => ['Ditolak', 'danger'],
                default => [$status, 'neutral'],
            },
            'refund' => match ($status) {
                'pending' => ['Menunggu transfer', 'warning'],
                'processed' => ['Sudah ditransfer', 'success'],
                'failed' => ['Transfer gagal', 'danger'],
                default => [$status, 'neutral'],
            },
            default => [$status, 'neutral'],
        };
    }

    /**
     * @param  list<string>  $statuses
     */
    private static function known(array $statuses, string $status): bool
    {
        return in_array($status, $statuses, true);
    }
}

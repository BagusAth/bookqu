<?php

declare(strict_types=1);

namespace App\Domain\Payout;

/**
 * Owner payout states (BQ-015): pending → approved → success | failed; pending → rejected.
 * Rejected and failed payouts return their amount to the tenant balance.
 */
final class PayoutState
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const SUCCESS = 'success';

    public const FAILED = 'failed';

    public const REJECTED = 'rejected';

    private const TRANSITIONS = [
        self::PENDING => [self::APPROVED, self::REJECTED],
        self::APPROVED => [self::SUCCESS, self::FAILED],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function returnsFunds(string $status): bool
    {
        return in_array($status, [self::REJECTED, self::FAILED], true);
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::PENDING => 'Menunggu Review',
            self::APPROVED => 'Disetujui, Diproses',
            self::SUCCESS => 'Berhasil Ditransfer',
            self::FAILED => 'Transfer Gagal',
            self::REJECTED => 'Ditolak',
            default => ucfirst($status),
        };
    }

    public static function ownerLabel(string $status): string
    {
        return match ($status) {
            self::PENDING, self::APPROVED => 'Pending',
            self::SUCCESS => 'Berhasil',
            self::FAILED, self::REJECTED => 'Gagal',
            default => ucfirst($status),
        };
    }

    public static function ownerNote(string $status): ?string
    {
        return match ($status) {
            self::APPROVED => 'Disetujui, transfer diproses',
            default => null,
        };
    }

    public static function ownerTone(string $status): string
    {
        return match ($status) {
            self::PENDING, self::APPROVED => 'warning',
            self::SUCCESS => 'success',
            self::FAILED, self::REJECTED => 'danger',
            default => 'neutral',
        };
    }
}

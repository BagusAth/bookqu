<?php

declare(strict_types=1);

namespace App\Domain\Payment;

class PaymentState
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUKSES  = 'sukses';
    public const STATUS_GAGAL   = 'gagal';

    public const TIPE_BOOKING      = 'booking';
    public const TIPE_SUBSCRIPTION = 'subscription';

    public const METODE_MIDTRANS = 'midtrans';
    public const METODE_GRATIS   = 'gratis';
    public const METODE_CASH     = 'cash';

    /**
     * Allowed status transitions mapped from current status to target statuses.
     */
    protected static array $allowedTransitions = [
        self::STATUS_PENDING => [
            self::STATUS_SUKSES,
            self::STATUS_GAGAL,
        ],
        self::STATUS_SUKSES => [],
        self::STATUS_GAGAL  => [],
    ];

    /**
     * Check if a transition between two statuses is permitted.
     */
    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        $allowed = self::$allowedTransitions[$from] ?? [];

        return in_array($to, $allowed, true);
    }

    /**
     * Check if status is a terminal state that cannot transition further.
     */
    public static function isTerminal(string $status): bool
    {
        return empty(self::$allowedTransitions[$status]);
    }

    /**
     * Check if a status string is valid in BookQu.
     */
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, [
            self::STATUS_PENDING,
            self::STATUS_SUKSES,
            self::STATUS_GAGAL,
        ], true);
    }

    /**
     * Map external Midtrans transaction_status and fraud_status to BookQu payment status.
     *
     * @param string|null $transactionStatus
     * @param string|null $fraudStatus
     * @return string One of 'sukses', 'pending', 'gagal', or 'unknown'
     */
    public static function mapTransactionStatus(?string $transactionStatus, ?string $fraudStatus = null): string
    {
        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'accept') {
                return self::STATUS_SUKSES;
            }

            return self::STATUS_PENDING;
        }

        if ($transactionStatus === 'settlement') {
            return self::STATUS_SUKSES;
        }

        if (in_array($transactionStatus, ['deny', 'expire', 'cancel'], true)) {
            return self::STATUS_GAGAL;
        }

        if ($transactionStatus === 'pending') {
            return self::STATUS_PENDING;
        }

        return 'unknown';
    }

    /**
     * Human-readable Indonesian label for payment status.
     */
    public static function formatLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_SUKSES  => 'Berhasil',
            self::STATUS_PENDING => 'Menunggu Pembayaran',
            self::STATUS_GAGAL   => 'Gagal / Dibatalkan',
            default              => ucfirst($status),
        };
    }
}

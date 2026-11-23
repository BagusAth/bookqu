<?php

namespace App\Domain\Booking;

class BookingState
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    /** The customer did not come to a paid session (ADR-0018). Terminal. */
    public const STATUS_NO_SHOW = 'no_show';

    /**
     * Allowed status transitions mapped from current status to target statuses.
     */
    protected static array $allowedTransitions = [
        self::STATUS_PENDING => [
            self::STATUS_PAID,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ],
        self::STATUS_PAID => [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_NO_SHOW,
        ],
        self::STATUS_CANCELLED => [],
        self::STATUS_COMPLETED => [],
        self::STATUS_NO_SHOW => [],
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
     * Get list of allowed target statuses from a given status.
     */
    public static function getAllowedTransitions(string $from): array
    {
        return self::$allowedTransitions[$from] ?? [];
    }

    /**
     * Check if status is a terminal state that cannot transition further.
     */
    public static function isTerminal(string $status): bool
    {
        return empty(self::$allowedTransitions[$status]);
    }

    /**
     * Human-readable Indonesian label for booking status, as listed in the owner-portal
     * contract (owner-portal.md §8).
     */
    public static function formatLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Menunggu bayar',
            self::STATUS_PAID => 'Terkonfirmasi',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_NO_SHOW => 'Tidak datang',
            default => $status,
        };
    }
}

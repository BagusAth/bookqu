<?php

namespace App\Domain\Booking;

use Carbon\Carbon;
use DateTimeInterface;

class BookingState
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

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
        ],
        self::STATUS_CANCELLED => [],
        self::STATUS_COMPLETED => [],
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
     * Determine if a booking with the given status and creation time occupies a schedule slot.
     */
    public static function occupiesSlot(string $status, ?DateTimeInterface $createdAt = null, int $pendingGraceMinutes = 15): bool
    {
        if (in_array($status, [self::STATUS_PAID, self::STATUS_COMPLETED], true)) {
            return true;
        }

        if ($status === self::STATUS_PENDING) {
            if ($createdAt === null) {
                return true;
            }

            $created = Carbon::instance($createdAt);
            return $created->greaterThanOrEqualTo(now()->subMinutes($pendingGraceMinutes));
        }

        return false;
    }

    /**
     * Human-readable Indonesian label for booking status.
     */
    public static function formatLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PAID      => 'lunas / dikonfirmasi',
            self::STATUS_COMPLETED => 'selesai',
            self::STATUS_CANCELLED => 'dibatalkan',
            self::STATUS_PENDING   => 'menunggu pembayaran',
            default                => $status,
        };
    }
}

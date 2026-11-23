<?php

declare(strict_types=1);

namespace App\Domain\Customer;

use App\Models\Booking;

/**
 * The one customer identity of a business (owner-portal.md §8.5 AC-PLG-01): customers are not stored
 * as rows of their own; a booking belongs to the customer keyed by its normalized email, else its
 * phone number, else the booking itself. The same key is `customer_notes.customer_identifier`.
 */
final class CustomerIdentity
{
    /**
     * SQL expression of the identity for a bookings row, optionally through a table alias.
     */
    public static function sql(string $table = ''): string
    {
        $column = static fn (string $name): string => $table === '' ? $name : $table.'.'.$name;

        return "LOWER(TRIM(COALESCE(NULLIF(TRIM({$column('email')}), ''), NULLIF(TRIM({$column('nomorhp')}), ''), CONCAT('guest-', {$column('id')}))))";
    }

    public static function normalize(string $identifier): string
    {
        return mb_strtolower(trim($identifier));
    }

    public static function forBooking(Booking $booking): string
    {
        $email = trim((string) $booking->email);
        $phone = trim((string) $booking->nomorhp);

        return self::normalize($email !== '' ? $email : ($phone !== '' ? $phone : 'guest-'.$booking->id));
    }
}

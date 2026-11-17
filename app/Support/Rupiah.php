<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Rupiah as the owner portal shows it (BQ-162, Papan Jadwal prototype): "Rp150.000", whole rupiah,
 * Indonesian thousands separator, no space after "Rp" (PUEBI). Customer pages and invoices keep their own format.
 */
final class Rupiah
{
    public static function format(int|float|string|null $amount): string
    {
        $value = (float) ($amount ?? 0);

        return ($value < 0 ? '−' : '').'Rp'.number_format(abs($value), 0, ',', '.');
    }
}

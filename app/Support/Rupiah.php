<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Rupiah as the owner portal shows it (BQ-162, Papan Jadwal prototype): "Rp150.000", whole rupiah,
 * Indonesian thousands separator, with no space after "Rp" (PUEBI).
 */
final class Rupiah
{
    public static function format(int|float|string|null $amount): string
    {
        $value = (float) ($amount ?? 0);

        return ($value < 0 ? '−' : '').'Rp'.number_format(abs($value), 0, ',', '.');
    }
}

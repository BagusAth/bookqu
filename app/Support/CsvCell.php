<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Formula-safe CSV cells (BQ-111, CWE-1236): a text cell that a spreadsheet would read as a
 * formula gets a leading apostrophe. Numbers and nulls pass through unchanged.
 */
final class CsvCell
{
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    public static function safe(mixed $value): mixed
    {
        // A lone "-" or "+" (the empty-value placeholder) is not a formula.
        if (! is_string($value) || $value === '' || $value === '-' || $value === '+') {
            return $value;
        }

        return in_array($value[0], self::FORMULA_TRIGGERS, true) ? "'".$value : $value;
    }

    /**
     * @param  array<int, mixed>  $cells
     * @return array<int, mixed>
     */
    public static function row(array $cells): array
    {
        return array_map(self::safe(...), $cells);
    }
}

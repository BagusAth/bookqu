<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The colour labels a category can carry (FR-CATEGORY-006). One palette for the owner page and the
 * storefront; an unknown stored value falls back to the first colour.
 */
final class CategoryColor
{
    /** @var array<string, array{label: string, hex: string}> */
    public const PALETTE = [
        'purple' => ['label' => 'Ungu', 'hex' => '#382186'],
        'lavender' => ['label' => 'Lavender', 'hex' => '#7a5af8'],
        'indigo' => ['label' => 'Nila', 'hex' => '#4f46e5'],
        'sky' => ['label' => 'Biru langit', 'hex' => '#0284c7'],
        'emerald' => ['label' => 'Hijau', 'hex' => '#059669'],
        'amber' => ['label' => 'Kuning', 'hex' => '#d97706'],
        'rose' => ['label' => 'Merah muda', 'hex' => '#e11d48'],
        'violet' => ['label' => 'Violet', 'hex' => '#7c3aed'],
    ];

    public const DEFAULT = 'purple';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::PALETTE);
    }

    public static function hex(?string $color): string
    {
        return self::PALETTE[$color ?? '']['hex'] ?? self::PALETTE[self::DEFAULT]['hex'];
    }
}

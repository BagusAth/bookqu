<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;

/**
 * The look of a business's public booking page (owner-portal.md §8.9 Tampilan, FR-PUBLIC-003, D-009).
 * One list of allowed values for the owner form and the storefront, and one normaliser for the
 * storefront: stored values reach a `<style>` block, so anything unknown falls back to the default
 * instead of being written into CSS.
 */
final class StorefrontBranding
{
    public const DEFAULT_COLOR = '#4F46E5';

    public const DEFAULT_FONT = 'Plus Jakarta Sans';

    /** @var array<string, array{label: string, radius: string}> */
    public const BUTTON_STYLES = [
        'pill' => ['label' => 'Pil', 'radius' => '9999px'],
        'rounded' => ['label' => 'Membulat', 'radius' => '0.75rem'],
        'square' => ['label' => 'Kotak', 'radius' => '0.25rem'],
    ];

    /** @var array<string, array{label: string, shadow: string, border: string}> */
    public const CARD_STYLES = [
        'elevated' => ['label' => 'Terangkat', 'shadow' => '0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.05)', 'border' => '1px solid #E2E8F0'],
        'outline' => ['label' => 'Garis tepi', 'shadow' => 'none', 'border' => '2px solid #CBD5E1'],
        'soft' => ['label' => 'Lembut', 'shadow' => 'none', 'border' => '1px solid #F1F5F9'],
    ];

    /** @var list<string> Google Fonts families offered to every plan. */
    public const FONTS = ['Plus Jakarta Sans', 'Inter', 'Poppins', 'Outfit', 'Nunito', 'Lora'];

    /** Values saved before the redesign map to the new names. */
    private const LEGACY_BUTTONS = ['rounded-full' => 'pill', 'rounded-md' => 'rounded', 'rounded-lg' => 'rounded', 'rounded-none' => 'square'];

    private const LEGACY_CARDS = ['bordered' => 'outline', 'flat' => 'soft', 'shadow' => 'elevated'];

    public const COLOR_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    /**
     * Safe values for the storefront `<style>` block.
     *
     * @return array{color: string, font: string, buttonStyle: string, buttonRadius: string, cardStyle: string, cardShadow: string, cardBorder: string}
     */
    public static function for(Tenant $tenant): array
    {
        $color = is_string($tenant->theme_color) && preg_match(self::COLOR_PATTERN, $tenant->theme_color) === 1 ? strtoupper($tenant->theme_color) : self::DEFAULT_COLOR;
        $font = in_array($tenant->font_family, self::FONTS, true) ? (string) $tenant->font_family : self::DEFAULT_FONT;
        $button = self::buttonStyle($tenant->button_style);
        $card = self::cardStyle($tenant->card_style);

        return [
            'color' => $color,
            'font' => $font,
            'buttonStyle' => $button,
            'buttonRadius' => self::BUTTON_STYLES[$button]['radius'],
            'cardStyle' => $card,
            'cardShadow' => self::CARD_STYLES[$card]['shadow'],
            'cardBorder' => self::CARD_STYLES[$card]['border'],
        ];
    }

    public static function buttonStyle(?string $value): string
    {
        $value = self::LEGACY_BUTTONS[$value ?? ''] ?? $value;

        return array_key_exists((string) $value, self::BUTTON_STYLES) ? (string) $value : 'rounded';
    }

    public static function cardStyle(?string $value): string
    {
        $value = self::LEGACY_CARDS[$value ?? ''] ?? $value;

        return array_key_exists((string) $value, self::CARD_STYLES) ? (string) $value : 'elevated';
    }
}

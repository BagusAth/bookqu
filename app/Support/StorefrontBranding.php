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
     * @return array{color: string, hover: string, light: string, border: string, onPrimary: string, font: string, buttonStyle: string, buttonRadius: string, cardStyle: string, cardShadow: string, cardBorder: string}
     */
    public static function for(Tenant $tenant): array
    {
        $stored = strtolower(trim($tenant->theme_color ?? ''));
        // Legacy names share the category palette. Unknown values keep the storefront default.
        $color = strtoupper(match (true) {
            preg_match(self::COLOR_PATTERN, $stored) === 1 => $stored,
            in_array($stored, CategoryColor::keys(), true) => CategoryColor::hex($stored),
            default => self::DEFAULT_COLOR,
        });
        [$red, $green, $blue] = self::rgb($color);
        $luminance = 0.2126 * self::linear($red) + 0.7152 * self::linear($green) + 0.0722 * self::linear($blue);
        $whiteContrast = 1.05 / ($luminance + 0.05);
        $darkContrast = ($luminance + 0.05) / 0.05;
        $useWhite = $whiteContrast >= $darkContrast;
        $font = in_array($tenant->font_family, self::FONTS, true) ? (string) $tenant->font_family : self::DEFAULT_FONT;
        $button = self::buttonStyle($tenant->button_style);
        $card = self::cardStyle($tenant->card_style);

        return [
            'color' => $color,
            'hover' => self::mix($color, $useWhite ? 0 : 255, 0.14),
            'light' => self::mix($color, 255, 0.90),
            'border' => self::mix($color, 255, 0.72),
            'onPrimary' => $useWhite ? '#FFFFFF' : '#000000',
            'font' => $font,
            'buttonStyle' => $button,
            'buttonRadius' => self::BUTTON_STYLES[$button]['radius'],
            'cardStyle' => $card,
            'cardShadow' => self::CARD_STYLES[$card]['shadow'],
            'cardBorder' => self::CARD_STYLES[$card]['border'],
        ];
    }

    /** @return array{int, int, int} */
    private static function rgb(string $hex): array
    {
        return [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
    }

    private static function linear(int $channel): float
    {
        $scaled = $channel / 255;

        return $scaled <= 0.04045 ? $scaled / 12.92 : (($scaled + 0.055) / 1.055) ** 2.4;
    }

    private static function mix(string $color, int $target, float $ratio): string
    {
        $mixed = array_map(fn (int $channel): int => (int) round($channel + ($target - $channel) * $ratio), self::rgb($color));

        return sprintf('#%02X%02X%02X', ...$mixed);
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

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

    /**
     * Ready-made looks in Halaman Booking → Tampilan (D-028, design A §3.4). A template only fills the three
     * settings; the brand colour stays the business's. Nothing new is stored: the current template is
     * recognised from the saved values.
     *
     * @var array<string, array{label: string, button_style: string, card_style: string, font_family: string}>
     */
    public const TEMPLATES = [
        'modern' => ['label' => 'Modern', 'button_style' => 'pill', 'card_style' => 'elevated', 'font_family' => 'Plus Jakarta Sans'],
        'rapi' => ['label' => 'Rapi', 'button_style' => 'rounded', 'card_style' => 'outline', 'font_family' => 'Inter'],
        'tegas' => ['label' => 'Tegas', 'button_style' => 'square', 'card_style' => 'soft', 'font_family' => 'Outfit'],
    ];

    public const CUSTOM_TEMPLATE = 'custom';

    /** @var list<string> Google Fonts families offered to every plan. */
    public const FONTS = ['Plus Jakarta Sans', 'Inter', 'Poppins', 'Outfit', 'Nunito', 'Lora'];

    /** Values saved before the redesign map to the new names. */
    private const LEGACY_BUTTONS = ['rounded-full' => 'pill', 'rounded-md' => 'rounded', 'rounded-lg' => 'rounded', 'rounded-none' => 'square'];

    private const LEGACY_CARDS = ['bordered' => 'outline', 'flat' => 'soft', 'shadow' => 'elevated'];

    public const COLOR_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    /** A brand lighter than this takes dark text and a darkened text colour (design A §2.1). */
    public const LIGHT_BRAND_LUMINANCE = 0.18;

    public const TEXT_ON_LIGHT = '#17151F';

    /**
     * Corner radii of customer controls and cards per button style (design A §3.1). Card corners follow the
     * button style so they match.
     *
     * @var array<string, array{control: string, tile: string, area: string, chip: string, card: string, cardSmall: string}>
     */
    public const SHAPES = [
        'pill' => ['control' => '9999px', 'tile' => '9999px', 'area' => '20px', 'chip' => '9999px', 'card' => '24px', 'cardSmall' => '18px'],
        'rounded' => ['control' => '12px', 'tile' => '14px', 'area' => '12px', 'chip' => '10px', 'card' => '18px', 'cardSmall' => '12px'],
        'square' => ['control' => '4px', 'tile' => '4px', 'area' => '4px', 'chip' => '4px', 'card' => '6px', 'cardSmall' => '4px'],
    ];

    /**
     * Shadows of customer surfaces per card style (design A §3.2). Outlines are drawn as a box-shadow ring so
     * switching style never shifts the layout.
     *
     * @var array<string, array{card: string, raised: string}>
     */
    public const SURFACES = [
        'elevated' => ['card' => '0 1px 2px rgba(23, 21, 31, 0.05), 0 6px 18px rgba(23, 21, 31, 0.06)', 'raised' => '0 16px 40px rgba(23, 21, 31, 0.14)'],
        'outline' => ['card' => '0 0 0 1.5px #D5D3DC', 'raised' => '0 0 0 1.5px #D5D3DC'],
        'soft' => ['card' => 'none', 'raised' => '0 1px 0 rgba(23, 21, 31, 0.04)'],
    ];

    /**
     * Safe values for the storefront `<style>` block. The derived colours, `shape` and `surface` feed the
     * customer `--sf-*` tokens (design A §2–§3); the other keys stay for the owner preview and emails.
     *
     * @return array{color: string, hover: string, light: string, border: string, onPrimary: string, onPrimarySoft: string, onPrimaryFaint: string, onPrimaryLine: string, primaryText: string, tint: string, tintStrong: string, isLight: bool, font: string, buttonStyle: string, buttonRadius: string, cardStyle: string, cardShadow: string, cardBorder: string, shape: array{control: string, tile: string, area: string, chip: string, card: string, cardSmall: string}, surface: array{card: string, raised: string}}
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
        $isLight = $luminance > self::LIGHT_BRAND_LUMINANCE;
        $useWhite = ! $isLight;
        $font = in_array($tenant->font_family, self::FONTS, true) ? (string) $tenant->font_family : self::DEFAULT_FONT;
        $button = self::buttonStyle($tenant->button_style);
        $card = self::cardStyle($tenant->card_style);

        return [
            'color' => $color,
            'hover' => self::mix($color, $useWhite ? 0 : 255, 0.14),
            'light' => self::mix($color, 255, 0.90),
            'border' => self::mix($color, 255, 0.72),
            'onPrimary' => $useWhite ? '#FFFFFF' : self::TEXT_ON_LIGHT,
            'onPrimarySoft' => $useWhite ? 'rgba(255, 255, 255, 0.86)' : 'rgba(23, 21, 31, 0.80)',
            'onPrimaryFaint' => $useWhite ? 'rgba(255, 255, 255, 0.14)' : 'rgba(23, 21, 31, 0.10)',
            'onPrimaryLine' => $useWhite ? 'rgba(255, 255, 255, 0.24)' : 'rgba(23, 21, 31, 0.18)',
            // A light brand is unreadable on white, so its text colour keeps 42% of the brand over black.
            'primaryText' => $isLight ? self::mix($color, 0, 0.58) : $color,
            'tint' => self::mix($color, 255, 0.91),
            'tintStrong' => self::mix($color, 255, 0.84),
            'isLight' => $isLight,
            'font' => $font,
            'buttonStyle' => $button,
            'buttonRadius' => self::BUTTON_STYLES[$button]['radius'],
            'cardStyle' => $card,
            'cardShadow' => self::CARD_STYLES[$card]['shadow'],
            'cardBorder' => self::CARD_STYLES[$card]['border'],
            'shape' => self::SHAPES[$button],
            'surface' => self::SURFACES[$card],
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

    /** The template matching the tenant's saved look, or `custom`. */
    public static function templateFor(Tenant $tenant): string
    {
        $font = in_array($tenant->font_family, self::FONTS, true) ? (string) $tenant->font_family : self::DEFAULT_FONT;

        return self::templateKey(self::buttonStyle($tenant->button_style), self::cardStyle($tenant->card_style), $font);
    }

    /** The template whose three settings equal these values, or `custom`. */
    public static function templateKey(?string $buttonStyle, ?string $cardStyle, ?string $fontFamily): string
    {
        foreach (self::TEMPLATES as $key => $template) {
            if ($template['button_style'] === $buttonStyle && $template['card_style'] === $cardStyle && $template['font_family'] === $fontFamily) {
                return $key;
            }
        }

        return self::CUSTOM_TEMPLATE;
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

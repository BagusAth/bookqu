<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Support\CategoryColor;
use App\Support\StorefrontBranding;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StorefrontBrandingTest extends TestCase
{
    public function test_brand_uses_readable_text_for_light_and_dark_colours(): void
    {
        $light = StorefrontBranding::for(new Tenant(['theme_color' => '#FDE047']));
        $dark = StorefrontBranding::for(new Tenant(['theme_color' => '#382186']));

        // Design A §2.1: dark text when the brand's luminance is above 0.18, white otherwise.
        $this->assertSame('#17151F', $light['onPrimary']);
        $this->assertSame('#FFFFFF', $dark['onPrimary']);
        $this->assertTrue($light['isLight']);
        $this->assertFalse($dark['isLight']);
        $this->assertStringStartsWith('rgba(23, 21, 31', $light['onPrimarySoft']);
        $this->assertStringStartsWith('rgba(255, 255, 255', $dark['onPrimaryFaint']);
        foreach ([$light, $dark] as $branding) {
            $this->assertNotSame($branding['color'], $branding['hover']);
            $this->assertNotSame($branding['light'], $branding['border']);
        }
    }

    public function test_amber_takes_dark_text_because_white_on_it_fails_contrast(): void
    {
        $amber = StorefrontBranding::for(new Tenant(['theme_color' => '#F59E0B']));

        $this->assertSame('#17151F', $amber['onPrimary']);
        $this->assertGreaterThanOrEqual(4.5, $this->contrast($amber['color'], $amber['onPrimary']));
    }

    public function test_primary_text_darkens_a_light_brand_so_it_reads_on_white(): void
    {
        $amber = StorefrontBranding::for(new Tenant(['theme_color' => '#F59E0B']));
        $dark = StorefrontBranding::for(new Tenant(['theme_color' => '#0F766E']));

        // 42% brand mixed with black.
        $this->assertSame('#674205', $amber['primaryText']);
        $this->assertGreaterThanOrEqual(4.5, $this->contrast($amber['primaryText'], '#FFFFFF'));
        $this->assertSame('#0F766E', $dark['primaryText']);
        $this->assertGreaterThanOrEqual(4.5, $this->contrast($dark['primaryText'], '#FFFFFF'));
    }

    public function test_tints_mix_the_brand_over_white(): void
    {
        $branding = StorefrontBranding::for(new Tenant(['theme_color' => '#382186']));

        $this->assertSame('#EDEBF4', $branding['tint']);
        $this->assertSame('#DFDBEC', $branding['tintStrong']);
    }

    /**
     * @param  array{control: string, tile: string, area: string, chip: string, card: string, cardSmall: string}  $shape
     */
    #[DataProvider('buttonStyles')]
    public function test_button_style_sets_every_control_and_card_radius(string $style, array $shape): void
    {
        $this->assertSame($shape, StorefrontBranding::for(new Tenant(['button_style' => $style]))['shape']);
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function buttonStyles(): array
    {
        return [
            'pill' => ['pill', ['control' => '9999px', 'tile' => '9999px', 'area' => '20px', 'chip' => '9999px', 'card' => '24px', 'cardSmall' => '18px']],
            'rounded' => ['rounded', ['control' => '12px', 'tile' => '14px', 'area' => '12px', 'chip' => '10px', 'card' => '18px', 'cardSmall' => '12px']],
            'square' => ['square', ['control' => '4px', 'tile' => '4px', 'area' => '4px', 'chip' => '4px', 'card' => '6px', 'cardSmall' => '4px']],
        ];
    }

    /**
     * @param  array{card: string, raised: string}  $surface
     */
    #[DataProvider('cardStyles')]
    public function test_card_style_sets_the_card_and_raised_surfaces(string $style, array $surface): void
    {
        $this->assertSame($surface, StorefrontBranding::for(new Tenant(['card_style' => $style]))['surface']);
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function cardStyles(): array
    {
        return [
            'elevated' => ['elevated', ['card' => '0 1px 2px rgba(23, 21, 31, 0.05), 0 6px 18px rgba(23, 21, 31, 0.06)', 'raised' => '0 16px 40px rgba(23, 21, 31, 0.14)']],
            'outline' => ['outline', ['card' => '0 0 0 1.5px #D5D3DC', 'raised' => '0 0 0 1.5px #D5D3DC']],
            'soft' => ['soft', ['card' => 'none', 'raised' => '0 1px 0 rgba(23, 21, 31, 0.04)']],
        ];
    }

    public function test_legacy_and_unknown_shapes_fall_back(): void
    {
        $this->assertSame(StorefrontBranding::SHAPES['pill'], StorefrontBranding::for(new Tenant(['button_style' => 'rounded-full']))['shape']);
        $this->assertSame(StorefrontBranding::SURFACES['outline'], StorefrontBranding::for(new Tenant(['card_style' => 'bordered']))['surface']);

        $unknown = StorefrontBranding::for(new Tenant(['button_style' => 'blob;}', 'card_style' => 'glass', 'font_family' => 'Comic"}']));
        $this->assertSame(StorefrontBranding::SHAPES['rounded'], $unknown['shape']);
        $this->assertSame(StorefrontBranding::SURFACES['elevated'], $unknown['surface']);
        $this->assertSame(StorefrontBranding::DEFAULT_FONT, $unknown['font']);
    }

    public function test_legacy_colours_share_the_category_palette_and_unknown_values_fall_back(): void
    {
        foreach (CategoryColor::keys() as $color) {
            $this->assertSame(strtoupper(CategoryColor::hex($color)), StorefrontBranding::for(new Tenant(['theme_color' => $color]))['color']);
        }

        $fallback = StorefrontBranding::for(new Tenant(['theme_color' => 'invalid']));
        $this->assertSame(StorefrontBranding::DEFAULT_COLOR, $fallback['color']);
        $this->assertSame(StorefrontBranding::DEFAULT_COLOR, $fallback['primaryText']);
    }

    public function test_each_template_is_recognised_from_the_three_saved_settings(): void
    {
        foreach (StorefrontBranding::TEMPLATES as $key => $template) {
            $tenant = new Tenant([
                'theme_color' => '#0F766E',
                'button_style' => $template['button_style'],
                'card_style' => $template['card_style'],
                'font_family' => $template['font_family'],
            ]);

            $this->assertSame($key, StorefrontBranding::templateFor($tenant));
        }

        $this->assertSame(['modern', 'rapi', 'tegas'], array_keys(StorefrontBranding::TEMPLATES));
    }

    public function test_a_mixed_or_legacy_look_is_custom_unless_it_normalises_to_a_template(): void
    {
        $this->assertSame('custom', StorefrontBranding::templateFor(new Tenant(['button_style' => 'pill', 'card_style' => 'outline', 'font_family' => 'Inter'])));
        $this->assertSame('custom', StorefrontBranding::templateFor(new Tenant(['button_style' => 'square', 'card_style' => 'soft', 'font_family' => 'Lora'])));
        // Legacy names normalise first: rounded-full = pill, shadow = elevated; an unknown font falls back to Plus Jakarta Sans.
        $this->assertSame('modern', StorefrontBranding::templateFor(new Tenant(['button_style' => 'rounded-full', 'card_style' => 'shadow', 'font_family' => 'Comic'])));
    }

    private function contrast(string $first, string $second): float
    {
        $luminance = function (string $hex): float {
            $channels = array_map(function (int $value): float {
                $scaled = $value / 255;

                return $scaled <= 0.04045 ? $scaled / 12.92 : (($scaled + 0.055) / 1.055) ** 2.4;
            }, [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))]);

            return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        };
        $values = [$luminance($first), $luminance($second)];

        return (max($values) + 0.05) / (min($values) + 0.05);
    }
}
